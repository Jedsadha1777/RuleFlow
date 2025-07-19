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
      const varName = formula.switch!.substring(1);
      switchValue = context[varName];
      
      if (switchValue === undefined) {
        throw new RuleFlowException(
          `Switch variable '${formula.switch}' not found. Available variables: ${Object.keys(context).join(', ')}`
        );
      }
    } else {
      switchValue = context[formula.switch!];
      
      if (switchValue === undefined) {
        throw new RuleFlowException(
          `Switch variable '${formula.switch}' not found. Available variables: ${Object.keys(context).join(', ')}`
        );
      }
    }

    let matched = false;

    for (const when of formula.when || []) {
      if (this.evaluateCondition(when.if, switchValue, context)) {
        const storeAs = formula.as || formula.id;
        
        //  Resolve variable references in result
        const resolvedResult = this.resolveValue(when.result, context);
        context[storeAs.replace('$', '')] = resolvedResult;
        
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
        
        const resolvedDefault = this.resolveValue(formula.default, context);
        context[storeAs.replace('$', '')] = resolvedDefault;
      }
      
      // Handle default set_vars
      if (formula.set_vars) {
        this.setVariables(formula.set_vars, context);
      }
    }
  }

  //  ปรับปรุง resolveValue() ให้ครอบคลุมทุกกรณี
  private resolveValue(value: any, context: Record<string, any>): any {
    // ถ้าไม่ใช่ string ก็ return ค่าเดิม
    if (typeof value !== 'string') {
      return value;
    }

    // ถ้าเป็น variable reference ($variable)
    if (value.startsWith('$')) {
      const varName = value.substring(1);
      if (context[varName] !== undefined) {
        return context[varName];
      } else {
        throw new RuleFlowException(`Variable reference '${value}' not found in context`);
      }
    }
    
    // ถ้าเป็น expression (มี operators หรือ functions)
    if (this.isExpression(value)) {
      try {
        this.evaluator.setVariables(context);
        return this.evaluator.evaluate(value);
      } catch (error: any) {
        throw new RuleFlowException(`Cannot evaluate expression '${value}': ${error.message}`);
      }
    }
    
    // ถ้าเป็น string literal ก็ลอง convert type
    return this.convertStringValue(value);
  }


  private isExpression(value: string): boolean {
    // Check for mathematical operators, variables, or function calls
    return /[\$\w]+\s*[+\-*/]\s*[\$\w\d.]+|[\$\w]+\s*[+\-*/]\s*\d+|\d+\s*[+\-*/]\s*[\$\w]+|[a-zA-Z_][a-zA-Z0-9_]*\s*\(/.test(value);
  }


  private setVariables(setVars: Record<string, any>, context: Record<string, any>): void {
    for (const [key, value] of Object.entries(setVars)) {
      const variableName = key.replace('$', '');
      
      // ใช้ resolveValue() เพื่อจัดการทุกประเภทของ value
      const resolvedValue = this.resolveValue(value, context);
      context[variableName] = resolvedValue;
    }
  }

  private convertStringValue(value: string): any {
    // Boolean conversion
    if (value.toLowerCase() === 'true') return true;
    if (value.toLowerCase() === 'false') return false;
    
    // Number conversion
    if (/^\d+$/.test(value)) {
      return parseInt(value, 10);
    }
    
    if (/^\d*\.\d+$/.test(value)) {
      return parseFloat(value);
    }
    
    // Return as string if no conversion possible
    return value;
  }

  private evaluateCondition(condition: any, switchValue: any, context: Record<string, any>): boolean {
    // Handle logical conditions (AND/OR)
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

    // Handle condition with var property
    if (condition.var) {
      const valueToCompare = context[condition.var];
      if (valueToCompare === undefined) {
        return false;
      }
      return this.compareValues(valueToCompare, condition.op, condition.value);
    }

    // Handle direct comparison with switchValue
    if (condition.op && 'value' in condition) {
      return this.compareValues(switchValue, condition.op, condition.value);
    }

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
}