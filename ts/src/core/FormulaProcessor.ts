import { Formula } from '../types.js';
import { ExpressionEvaluator } from './ExpressionEvaluator';
import { InputValidator } from '../validators/InputValidator';
import { ScoringProcessor } from './ScoringProcessor';
import { RuleFlowException } from '../exceptions/RuleFlowException';
import { FunctionRegistry } from '../functions/FunctionRegistry';

export class FormulaProcessor {
  private evaluator: ExpressionEvaluator;
  private inputValidator: InputValidator;
  private scoringProcessor: ScoringProcessor;

  constructor(functionRegistry?: FunctionRegistry) {
    const registry = functionRegistry || new FunctionRegistry();
    this.evaluator = new ExpressionEvaluator(registry);
    this.inputValidator = new InputValidator();
    this.scoringProcessor = new ScoringProcessor();
  }

  process(formulas: Formula[], inputs: Record<string, any>): Record<string, any> {
    const context = { ...inputs };

    for (const formula of formulas) {
      try {
        if (formula.formula) {
          this.processFormula(formula, context);
        } else if (formula.switch) {
          this.processSwitch(formula, context);
        } else if (formula.rules) {
          this.processAccumulativeScoring(formula, context);
        } else if (formula.scoring) {
          this.processAdvancedScoring(formula, context);
        } else {
          throw new RuleFlowException(`Formula '${formula.id}' must have formula, switch, rules, or scoring`);
        }
      } catch (error: any) {
        throw new RuleFlowException(`Error processing formula '${formula.id}': ${error.message}`);
      }
    }

    return context;
  }

  private processFormula(formula: Formula, context: Record<string, any>): void {
    if (formula.inputs) {
      this.inputValidator.validate(context, formula.inputs);
    }

    this.evaluator.setVariables(context);
    const result = this.evaluator.evaluate(formula.formula!);
    
    const storeAs = formula.as || formula.id;
    context[storeAs.replace('$', '')] = result;

    // Handle variable setting
    if (formula.set_vars) {
      this.setVariables(formula.set_vars, context);
    }
  }

  private processSwitch(formula: Formula, context: Record<string, any>): void {
    let switchValue: any;

     if (formula.switch!.startsWith('$')) {
      // ลบ $ prefix และหาตัวแปรใน context
      const varName = formula.switch!.substring(1);
      switchValue = context[varName];
      
      if (switchValue === undefined) {
        throw new RuleFlowException(
          `Switch variable '${formula.switch}' not found. Available variables: ${Object.keys(context).join(', ')}`
        );
      }
    } else {
      // ใช้ชื่อตัวแปรโดยตรง
      switchValue = context[formula.switch!];
      
      if (switchValue === undefined) {
        throw new RuleFlowException(
          `Switch variable '${formula.switch}' not found. Available variables: ${Object.keys(context).join(', ')}`
        );
      }
    }

    
    if (switchValue === undefined) {
      throw new RuleFlowException(`Switch variable '${formula.switch}' not found`);
    }

    let matched = false;

    for (const when of formula.when || []) {
      if (this.evaluateCondition(when.if, switchValue, context)) {
        const storeAs = formula.as || formula.id;
        context[storeAs.replace('$', '')] = when.result;
        
        // Handle variable setting in when condition
        if (when.set_vars) {
          this.setVariables(when.set_vars, context);
        }
        
        matched = true;
        break;
      }
    }

    // Use default if no condition matched
    if (!matched) {
      if (formula.default !== undefined) {
        const storeAs = formula.as || formula.id;
        context[storeAs.replace('$', '')] = formula.default;
      }
      
      // Handle default set_vars
      if (formula.set_vars) {
        this.setVariables(formula.set_vars, context);
      }
    }
  }
  private evaluateCondition(condition: any, switchValue: any, context: Record<string, any>): boolean {
    // Handle logical conditions (AND/OR) - รองรับ nested logic
    if (condition.and && Array.isArray(condition.and)) {
      return condition.and.every((subCondition: any) => 
        this.evaluateCondition(subCondition, switchValue, context)
      );
    }
    
    if (condition.or && Array.isArray(condition.or)) {
      return condition.or.some((subCondition: any) => 
        this.evaluateCondition(subCondition, switchValue, context)
      );
    }

    // Handle simple condition with var property
    if (condition.var) {
      const valueToCompare = context[condition.var];
      if (valueToCompare === undefined) {
        // Missing variable should return false for comparison
        return false;
      }
      return this.compareValues(valueToCompare, condition.op, condition.value);
    }

    // Handle direct comparison with switchValue
    if (condition.op && 'value' in condition) {
      return this.compareValues(switchValue, condition.op, condition.value);
    }

    // Fallback for boolean-like conditions
    return Boolean(condition);
  }

  private compareValues(leftValue: any, operator: string, rightValue: any): boolean {
    const left = typeof leftValue === 'string' ? parseFloat(leftValue) || leftValue : leftValue;
    const right = typeof rightValue === 'string' ? parseFloat(rightValue) || rightValue : rightValue;

    switch (operator) {
      case '<': return left < right;
      case '<=': return left <= right;
      case '>': return left > right;
      case '>=': return left >= right;
      case '==': case '=': return left == right;
      case '!=': case '<>': return left != right;
      default: throw new RuleFlowException(`Unknown operator: ${operator}`);
    }
  }

  private processAccumulativeScoring(formula: Formula, context: Record<string, any>): void {
    if (!formula.rules) {
      throw new RuleFlowException(`Accumulative scoring formula '${formula.id}' must have rules`);
    }

    const result = this.scoringProcessor.processAccumulativeScore(formula.rules, context);
    
    const storeAs = formula.as || formula.id;
    context[storeAs.replace('$', '')] = result.score;
    
    // Store additional metadata
    if (result.breakdown) {
      context[`${storeAs.replace('$', '')}_breakdown`] = result.breakdown;
    }
    
    if (result.decision) {
      context[`${storeAs.replace('$', '')}_decision`] = result.decision;
    }
    
    if (result.level) {
      context[`${storeAs.replace('$', '')}_level`] = result.level;
    }
  }

  private processAdvancedScoring(formula: Formula, context: Record<string, any>): void {
    if (!formula.scoring) {
      throw new RuleFlowException(`Advanced scoring formula '${formula.id}' must have scoring configuration`);
    }

    let result;
    
    if (formula.scoring.ifs) {
      // Multi-dimensional scoring
      result = this.scoringProcessor.processMultiDimensionalScore(formula.scoring, context);
    } else if (formula.scoring.if) {
      // Simple weighted scoring
      const storeAs = formula.as || formula.id;
      const value = context[storeAs.replace('$', '')];
      result = this.scoringProcessor.processSimpleScore(formula.scoring, value, context);
    } else {
      throw new RuleFlowException(`Invalid scoring configuration for formula '${formula.id}'`);
    }
    
    const storeAs = formula.as || formula.id;
    context[storeAs.replace('$', '')] = result.score;
    
    // Store additional metadata
    if (result.decision) {
      context[`${storeAs.replace('$', '')}_decision`] = result.decision;
    }
    
    if (result.level) {
      context[`${storeAs.replace('$', '')}_level`] = result.level;
    }
    
    if (result.breakdown) {
      context[`${storeAs.replace('$', '')}_breakdown`] = result.breakdown;
    }
  }

  private setVariables(setVars: Record<string, any>, context: Record<string, any>): void {
    for (const [key, value] of Object.entries(setVars)) {
      const variableName = key.replace('$', ''); // Remove $ prefix
      
      if (typeof value === 'string') {
        // Check if it's an expression that needs evaluation
        if (this.isExpression(value)) {
          // Evaluate the expression (now supports functions!)
          this.evaluator.setVariables(context);
          context[variableName] = this.evaluator.evaluate(value);
        } else {
          // Try to convert string to appropriate type
          context[variableName] = this.convertStringValue(value);
        }
      } else {
        // Direct assignment for non-string values
        context[variableName] = value;
      }
    }
  }

  private isExpression(value: string): boolean {
    // Check if string contains variables, mathematical operators, or function calls
    return /[\$\w]+\s*[+\-*/]\s*[\$\w]+|[\$\w]+\s*[+\-*/]\s*\d+|\d+\s*[+\-*/]\s*[\$\w]+|[a-zA-Z_][a-zA-Z0-9_]*\s*\(/.test(value);
  }

  private convertStringValue(value: string): any {
    // Try to convert string to appropriate type
    
    // Boolean conversion
    if (value.toLowerCase() === 'true') return true;
    if (value.toLowerCase() === 'false') return false;
    
    // Number conversion
    if (/^\d+$/.test(value)) {
      return parseInt(value, 10); // Integer
    }
    
    if (/^\d*\.\d+$/.test(value)) {
      return parseFloat(value); // Float
    }
    
    // Return as string if no conversion possible
    return value;
  }
}