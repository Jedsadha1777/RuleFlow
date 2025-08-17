import { 
  AccumulativeRule, 
  ScoringConfig, 
  ScoringResult,
  MultiDimensionalScoring,
  Condition,
  LogicalCondition 
} from '../types';
import { RuleFlowException } from '../exceptions/RuleFlowException';

export class ScoringProcessor {
  /**
   * Process accumulative scoring rules (PHP compatible)
   */
  processAccumulativeScore(rules: AccumulativeRule[], context: Record<string, any>): ScoringResult {
    let totalScore = 0;
    const breakdown: Record<string, number> = {};
    
    for (const rule of rules) {
      const value = context[rule.var];
      
      if (value === undefined || value === null) {
        continue;
      }
      
      let ruleScore = 0;
      
      // Handle ranges
      if (rule.ranges) {
        for (const range of rule.ranges) {
          if (this.evaluateCondition(range.if, value, context)) {
            // PHP uses 'score' as primary, 'result' as fallback
            ruleScore = range.score ?? range.result ?? 0;
            
            // Handle variable setting
            if (range.set_vars) {
              this.setVariables(range.set_vars, context);
            }
            
            break;
          }
        }
      }
      // Handle single condition
      else if (rule.if) {
        if (this.evaluateCondition(rule.if, value, context)) {
          // PHP uses 'score' as primary, 'result' as fallback
          ruleScore = rule.score ?? rule.result ?? 0;
          
          // Special PHP logic: If score is 0 and we have weight, use the variable value
          if (ruleScore === 0 && rule.weight !== undefined) {
            ruleScore = Number(value) || 0;
          }
          
          // Handle variable setting
          if (rule.set_vars) {
            this.setVariables(rule.set_vars, context);
          }
        }
      }
      // Handle direct score (no condition)
      else {
        // PHP uses 'score' as primary, 'result' as fallback
        ruleScore = rule.score ?? rule.result ?? 0;
        
        // If no explicit score, use the variable value itself
        if (ruleScore === 0) {
          ruleScore = Number(value) || 0;
        }
      }
      
      // Apply weight if specified
      if (rule.weight !== undefined && rule.weight !== 1) {
        ruleScore *= rule.weight;
      }
      
      totalScore += ruleScore;
      breakdown[rule.var] = ruleScore;
    }
    
    return {
      score: totalScore,
      breakdown
    };
  }

  /**
   * Process multi-dimensional scoring (PHP compatible)
   */
  processMultiDimensionalScore(scoring: ScoringConfig, context: Record<string, any>): ScoringResult {
    if (!scoring.ifs) {
      throw new RuleFlowException('Multi-dimensional scoring requires ifs configuration');
    }
    
    return this.evaluateMultiDimensionalScore(scoring.ifs, context);
  }
  
  private evaluateMultiDimensionalScore(config: MultiDimensionalScoring, context: Record<string, any>): any {
    const { vars, tree } = config;
    
    // Get values for the variables in the scoring matrix
    const values = vars.map(varName => {
      const normalizedVar = varName.replace('$', '');
      return context[normalizedVar];
    });
    
    // Evaluate the scoring tree
    for (const node of tree) {
      // Check if this node's condition matches the first variable
      if (this.evaluateCondition(node.if, values[0], context)) {
        // If there are ranges, evaluate the second dimension
        if (node.ranges && values.length > 1) {
          for (const range of node.ranges) {
            if (this.evaluateCondition(range.if, values[1], context)) {
              const result: any = {
                score: range.score ?? range.result ?? 0
              };
              
              if (range.decision) result.decision = range.decision;
              if (range.level) result.level = range.level;

              const additionalProps = this.extractAdditionalProperties(range);
              Object.assign(result, additionalProps);
              
              // Return matched_rule for FormulaProcessor to handle set_vars
              result.matched_rule = range;
              
              return result;
            }
          }
        } else {
          // Direct result from node
          const result: any = {
            score: node.score ?? node.result ?? 0
          };
          
          if (node.decision) result.decision = node.decision;
          if (node.level) result.level = node.level;

          const additionalProps = this.extractAdditionalProperties(node);
          Object.assign(result, additionalProps);
          
          // Add matched_rule for set_vars processing
          result.matched_rule = node;
          
          return result;
        }
      }
    }
    
    // No match found
    return { score: 0 };
  }

  // เพิ่ม method สำหรับดึง additional properties
  private extractAdditionalProperties(item: any): Record<string, any> {
    const excluded = [
      'if', 'score', 'result', 'decision', 'level', 'set_vars', 'ranges',
      'op', 'value', 'var', 'and', 'or', 'function', 'params'
    ];
    const additional: Record<string, any> = {};
    
    if (!item || typeof item !== 'object') {
      return additional;
    }
    
    for (const [key, value] of Object.entries(item)) {
      if (!excluded.includes(key) && value !== undefined && value !== null) {
        additional[key] = value;
      }
    }
    
    return additional;
  }

  /**
   * Process simple weighted scoring (PHP compatible)
   */
  processSimpleScore(scoring: ScoringConfig, value: any, context: Record<string, any>): ScoringResult {
    if (!scoring.if) {
      throw new RuleFlowException('Simple scoring requires if condition');
    }
    
    if (this.evaluateCondition(scoring.if, value, context)) {
      // PHP uses 'score' as primary, 'result' as fallback
      let score = scoring.score ?? scoring.result ?? 0;
      
      // Apply weight if specified
      if (scoring.weight !== undefined && scoring.weight !== 1) {
        score *= scoring.weight;
      }
      
      return { score };
    }
    
    return { score: 0 };
  }

  /**
   * Evaluate condition (same as FormulaProcessor but specialized for scoring)
   */
  private evaluateCondition(condition: Condition | LogicalCondition, value: any, context: Record<string, any>): boolean {
    // Handle logical conditions (AND/OR)
    if ('and' in condition) {
      return condition.and!.every(cond => this.evaluateCondition(cond, value, context));
    }
    
    if ('or' in condition) {
      return condition.or!.some(cond => this.evaluateCondition(cond, value, context));
    }

    // Handle simple condition
    const simpleCondition = condition as Condition;
    const valueToCompare = simpleCondition.var ? context[simpleCondition.var] : value;
    
    switch (simpleCondition.op) {
      case '==': return valueToCompare == simpleCondition.value;
      case '!=': return valueToCompare != simpleCondition.value;
      case '>': return valueToCompare > simpleCondition.value;
      case '<': return valueToCompare < simpleCondition.value;
      case '>=': return valueToCompare >= simpleCondition.value;
      case '<=': return valueToCompare <= simpleCondition.value;
      case 'between':
        const range = simpleCondition.value as [number, number];
        return valueToCompare >= range[0] && valueToCompare <= range[1];
      case 'in':
        const array = simpleCondition.value as any[];
        return array.includes(valueToCompare);
      
     case 'not_in':
      const notInArray = simpleCondition.value as any[];
      return !notInArray.includes(valueToCompare);
    case 'contains':
      return String(valueToCompare).includes(String(simpleCondition.value));
    case 'starts_with':
      return String(valueToCompare).startsWith(String(simpleCondition.value));
    case 'ends_with':
      return String(valueToCompare).endsWith(String(simpleCondition.value));
        
      default:
        throw new RuleFlowException(`Unknown operator: ${simpleCondition.op}`);
    }
  }

  /**
   * Set variables in context
   */
  private setVariables(setVars: Record<string, any>, context: Record<string, any>): void {
    for (const [key, value] of Object.entries(setVars)) {
      const variableName = key.replace('$', '');
      context[variableName] = value;
    }
  }
}