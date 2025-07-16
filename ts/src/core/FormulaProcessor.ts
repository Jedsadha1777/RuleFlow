import { Formula, Condition, LogicalCondition } from '../types.js';
import { ExpressionEvaluator } from './ExpressionEvaluator.js';
import { InputValidator } from '../validators/InputValidator.js';
import { RuleFlowException } from '../exceptions/RuleFlowException.js';
import { FunctionRegistry } from '../functions/FunctionRegistry.js';

export class FormulaProcessor {
  private evaluator: ExpressionEvaluator;
  private inputValidator: InputValidator;

  constructor(functionRegistry?: FunctionRegistry) {
    const registry = functionRegistry || new FunctionRegistry();
    this.evaluator = new ExpressionEvaluator(registry);
    this.inputValidator = new InputValidator();
  }

  process(formulas: Formula[], inputs: Record<string, any>): Record<string, any> {
    const context = { ...inputs };

    for (const formula of formulas) {
      try {
        if (formula.formula) {
          this.processFormula(formula, context);
        } else if (formula.switch) {
          this.processSwitch(formula, context);
        }
      } catch (error) {
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
    context[formula.id] = result;

    // Handle variable setting
    if (formula.set_vars) {
      this.setVariables(formula.set_vars, context);
    }
  }

  private processSwitch(formula: Formula, context: Record<string, any>): void {
    const switchValue = context[formula.switch!];
    
    if (switchValue === undefined) {
      throw new RuleFlowException(`Switch variable '${formula.switch}' not found`);
    }

    let matched = false;

    for (const when of formula.when || []) {
      if (this.evaluateCondition(when.if, switchValue, context)) {
        context[formula.id] = when.result;
        
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
        context[formula.id] = formula.default;
      }
      
      // Handle default set_vars
      if (formula.set_vars) {
        this.setVariables(formula.set_vars, context);
      }
    }
  }

  private evaluateCondition(condition: Condition | LogicalCondition, switchValue: any, context: Record<string, any>): boolean {
    // Handle logical conditions (AND/OR)
    if ('and' in condition) {
      return condition.and!.every(cond => this.evaluateCondition(cond, switchValue, context));
    }
    
    if ('or' in condition) {
      return condition.or!.some(cond => this.evaluateCondition(cond, switchValue, context));
    }

    // Handle simple condition
    const simpleCondition = condition as Condition;
    const valueToCompare = simpleCondition.var ? context[simpleCondition.var] : switchValue;
    
    switch (simpleCondition.op) {
      case '==': return valueToCompare == simpleCondition.value;
      case '!=': return valueToCompare != simpleCondition.value;
      case '>': return valueToCompare > simpleCondition.value;
      case '<': return valueToCompare < simpleCondition.value;
      case '>=': return valueToCompare >= simpleCondition.value;
      case '<=': return valueToCompare <= simpleCondition.value;
      default:
        throw new RuleFlowException(`Unknown operator: ${simpleCondition.op}`);
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