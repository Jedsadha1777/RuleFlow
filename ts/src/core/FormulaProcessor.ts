import { Formula, DefaultFunctionCall } from '../types.js';
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
        } else if (formula.function_call) {
          this.processFunctionCall(formula, context);
        } else if (formula.rules) {
          this.processAccumulativeScoring(formula, context);
        } else if (formula.scoring) {
          this.processAdvancedScoring(formula, context);
        } else {
          throw new RuleFlowException(`Formula '${formula.id}' must have formula, switch, function_call, rules, or scoring`);
        }
      } catch (error: any) {
        throw new RuleFlowException(`Error processing formula '${formula.id}': ${error.message}`);
      }
    }

    return context;
  }

  private processFunctionCall(formula: Formula, context: Record<string, any>): void {
    if (!formula.function_call) {
      throw new RuleFlowException(`Function call name is required for formula '${formula.id}'`);
    }

    const functionName = formula.function_call;
    const params = formula.params || [];

    // Resolve parameters from context
    const resolvedParams = params.map(param => {
      if (typeof param === 'string') {
        // Handle $ variable references
        if (param.startsWith('$')) {
          const varName = param.substring(1);
          if (context[varName] !== undefined) {
            return context[varName];
          } else {
            throw new RuleFlowException(`Variable '${param}' not found in context`);
          }
        }

        // Handle direct variable references
        if (context[param] !== undefined) {
          return context[param];
        }

        // Handle nested function calls or expressions
        if (param.includes('(') || param.includes('+') || param.includes('-') || param.includes('*') || param.includes('/')) {
          try {
            this.evaluator.setVariables(context);
            return this.evaluator.evaluate(param);
          } catch (error: any) {
            throw new RuleFlowException(`Cannot evaluate parameter '${param}': ${error.message}`);
          }
        }
      }

      // Return literal value
      return param;
    });

    try {
      const result = this.evaluator.getFunctionRegistry().call(functionName, resolvedParams);

      const roundedResult = typeof result === 'number' ? this.evaluator.applyAutoRounding(result) : result;
      this.storeResult(formula, roundedResult, context);

      // Handle variable setting
      if (formula.set_vars) {
        this.setVariables(formula.set_vars, context);
      }
    } catch (error: any) {
      throw new RuleFlowException(`Function call '${functionName}' failed: ${error.message}`);
    }
  }

  private processFormula(formula: Formula, context: Record<string, any>): void {
    // แก้ไขการ validate inputs ให้รองรับ $ notation
    if (formula.inputs) {
      const processedInputs = this.preprocessInputs(formula.inputs, context);
      this.inputValidator.validate(context, processedInputs);
    }

    // แก้ไขการประมวลผล formula ให้รองรับ $ notation
    let processedFormula = formula.formula!;
    
    // Replace $variable with actual variable names for evaluation
    processedFormula = this.preprocessFormulaExpression(processedFormula, context);

    this.evaluator.setVariables(context);
    const result = this.evaluator.evaluate(processedFormula);

    this.storeResult(formula, result, context);

    // Handle variable setting
    if (formula.set_vars) {
      this.setVariables(formula.set_vars, context);
    }
  }

  private processSwitch(formula: Formula, context: Record<string, any>): void {
    
    const switchValue = this.resolveSwitchValue(formula.switch!, context);

    if (switchValue === undefined) {
      throw new RuleFlowException(
        `Switch variable '${formula.switch}' not found. Available variables: ${Object.keys(context).join(', ')}`
      );
    }

    let matched = false;

    for (const when of formula.when || []) {
      if (this.evaluateCondition(when.if, switchValue, context)) {
         if (when.function_call) {
          const functionResult = this.executeFunctionCall(when, context);
          this.storeResult(formula, functionResult, context);
        } else {
          const resolvedResult = this.resolveValue(when.result, context);
          this.storeResult(formula, resolvedResult, context);
        }

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
      // แก้ไขการจัดการ function_call ใน default
      if (typeof formula.default === 'object' && 
            formula.default !== null && 
            'function_call' in formula.default) {
          const functionResult = this.executeFunctionCall(formula.default as DefaultFunctionCall, context);
          this.storeResult(formula, functionResult, context);
        } else {
          const resolvedDefault = this.resolveValue(formula.default, context);
          this.storeResult(formula, resolvedDefault, context);
        }
      }

      // เพิ่มการจัดการ default_vars
      if (formula.default_vars) {
        this.setVariables(formula.default_vars, context);
      }

      // Handle regular set_vars (ถ้ามี)
      if (formula.set_vars) {
        this.setVariables(formula.set_vars, context);
      }
    }
  }

  // Preprocess inputs ให้รองรับ $ notation
  private preprocessInputs(inputs: string[], context: Record<string, any>): string[] {
    return inputs.map(input => {
      if (input.startsWith('$')) {
        // Convert $variable to variable name และตรวจสอบว่ามีใน context หรือไม่
        const varName = input.substring(1);
        if (context[varName] === undefined) {
          throw new RuleFlowException(`Input variable '${input}' not found in context`);
        }
        return varName;
      }
      return input;
    });
  }

  //  NEW: Preprocess formula expression ให้รองรับ $ notation
  private preprocessFormulaExpression(formula: string, context: Record<string, any>): string {
    // Replace $variable with actual variable names for evaluation
    return formula.replace(/\$([a-zA-Z_][a-zA-Z0-9_]*)/g, (match, varName) => {
      if (context[varName] === undefined) {
        throw new RuleFlowException(`Variable '${match}' referenced in formula not found in context`);
      }
      return varName;
    });
  }

  private executeFunctionCall(when: any, context: Record<string, any>): any {
    try {
      const functionName = when.function_call;
      const params = when.params || [];

      // Resolve parameters from context
      const resolvedParams = params.map((param: any) => {
        if (typeof param === 'string' && param.startsWith('$')) {
          const varName = param.substring(1);
          return context[varName] !== undefined ? context[varName] : param;
        }
        return param;
      });

      const result = this.evaluator.getFunctionRegistry().call(functionName, resolvedParams);
      return typeof result === 'number' ? this.evaluator.applyAutoRounding(result) : result;

    } catch (error: any) {
      throw new RuleFlowException(`Function call '${when.function_call}' failed: ${error.message}`);
    }
  }

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
        // แก้ไขให้ preprocess $ notation ก่อน evaluate
        const processedExpression = this.preprocessFormulaExpression(value, context);
        this.evaluator.setVariables(context);
        return this.evaluator.evaluate(processedExpression);
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
      // แก้ไขให้ใช้ $ notation อย่างถูกต้อง
      let variableName = key;
      if (key.startsWith('$')) {
        variableName = key.substring(1);
      }
      
      // Resolve value ถ้าเป็น $ reference หรือ expression
      const resolvedValue = this.resolveValue(value, context);
      context[variableName] = resolvedValue;
    }
  }

  /**
   *  Resolve switch values with $ notation support
   */
  private resolveSwitchValue(switchField: string, context: Record<string, any>): any {
    // Handle $ notation in switch field
    if (switchField.startsWith('$')) {
      const varName = switchField.substring(1);
      return context[varName];
    }
    
    // Handle direct variable reference
    return context[switchField];
  }

  /**
   * Store results with $ notation support in 'as' field
   */
  private storeResult(formula: Formula, result: any, context: Record<string, any>): void {
    const storeAs = formula.as || formula.id;
    
    // Handle $ notation in 'as' field
    let variableName = storeAs;
    if (typeof storeAs === 'string' && storeAs.startsWith('$')) {
      variableName = storeAs.substring(1);
    }
    
    context[variableName] = result;
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

    // Handle function-based conditions
    if (condition.op === 'function' && condition.function) {
      return this.evaluateFunctionCondition(condition, switchValue, context);
    }

    // แก้ไข: Handle condition with var property (รองรับ $ notation)
    if (condition.var) {
      let varName = condition.var;
      if (varName.startsWith('$')) {
        varName = varName.substring(1);
      }
      
      const valueToCompare = context[varName];
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

  private evaluateFunctionCondition(condition: any, switchValue: any, context: Record<string, any>): boolean {
    try {
      const functionName = condition.function;
      const params = condition.params || [switchValue];

      // Resolve parameters from context
      const resolvedParams = params.map((param: any) => {
        if (typeof param === 'string' && param.startsWith('$')) {
          const varName = param.substring(1);
          return context[varName] !== undefined ? context[varName] : param;
        }
        return param;
      });

      // Call function through registry
      const result = this.evaluator.getFunctionRegistry().call(functionName, resolvedParams);
      return Boolean(result);
    } catch (error: any) {
      throw new RuleFlowException(`Function condition '${condition.function}' failed: ${error.message}`);
    }
  }

  private compareValues(leftValue: any, operator: string, rightValue: any): boolean {
    const left = typeof leftValue === 'string' ? parseFloat(leftValue) || leftValue : leftValue;
    const right = typeof rightValue === 'string' ? parseFloat(rightValue) || rightValue : rightValue;

    switch (operator) {
      case '<':
        return left < right;
      case '<=':
        return left <= right;
      case '>':
        return left > right;
      case '>=':
        return left >= right;
      case '==':
      case '=':
        return left == right;
      case '!=':
      case '<>':
        return left != right;
      case 'between':
        return this.evaluateBetween(left, rightValue);
      case 'in':
        return this.evaluateIn(left, rightValue);
      case 'not_in':
        return this.evaluateNotIn(left, rightValue);
      case 'contains':
        return this.evaluateContains(left, rightValue);
      case 'starts_with':
        return this.evaluateStartsWith(left, rightValue);
      case 'ends_with':
        return this.evaluateEndsWith(left, rightValue);
      default:
        throw new RuleFlowException(`Unknown operator: ${operator}`);
    }
  }

  private evaluateBetween(leftValue: any, rightValue: any): boolean {
    if (!Array.isArray(rightValue) || rightValue.length !== 2) {
      throw new RuleFlowException(`Between operator requires array with 2 values, got: ${JSON.stringify(rightValue)}`);
    }

    const numericLeft = Number(leftValue);
    const min = Number(rightValue[0]);
    const max = Number(rightValue[1]);

    if (isNaN(numericLeft) || isNaN(min) || isNaN(max)) {
      throw new RuleFlowException(`Between operator requires numeric values. Got: ${leftValue} between [${rightValue[0]}, ${rightValue[1]}]`);
    }

    return numericLeft >= min && numericLeft <= max;
  }

  private evaluateIn(leftValue: any, rightValue: any): boolean {
    if (!Array.isArray(rightValue)) {
      throw new RuleFlowException(`In operator requires array value, got: ${typeof rightValue}`);
    }
    return rightValue.includes(leftValue);
  }

  private evaluateNotIn(leftValue: any, rightValue: any): boolean {
    if (!Array.isArray(rightValue)) {
      throw new RuleFlowException(`Not_in operator requires array value, got: ${typeof rightValue}`);
    }
    return !rightValue.includes(leftValue);
  }

  private evaluateContains(leftValue: any, rightValue: any): boolean {
    const leftStr = String(leftValue);
    const rightStr = String(rightValue);
    return leftStr.includes(rightStr);
  }

  private evaluateStartsWith(leftValue: any, rightValue: any): boolean {
    const leftStr = String(leftValue);
    const rightStr = String(rightValue);
    return leftStr.startsWith(rightStr);
  }

  private evaluateEndsWith(leftValue: any, rightValue: any): boolean {
    const leftStr = String(leftValue);
    const rightStr = String(rightValue);
    return leftStr.endsWith(rightStr);
  }

  private processAccumulativeScoring(formula: Formula, context: Record<string, any>): void {
    if (!formula.rules) {
      throw new RuleFlowException(`Accumulative scoring formula '${formula.id}' must have rules`);
    }

    const result = this.scoringProcessor.processAccumulativeScore(formula.rules, context);

    // ✅ ใช้ storeResult แทน
    this.storeResult(formula, result.score, context);

    // Store additional metadata
    const storeAs = formula.as || formula.id;
    const variableName = typeof storeAs === 'string' && storeAs.startsWith('$') ? storeAs.substring(1) : storeAs;

    if (result.breakdown) {
      context[`${variableName}_breakdown`] = result.breakdown;
    }

    if (result.decision) {
      context[`${variableName}_decision`] = result.decision;
    }

    if (result.level) {
      context[`${variableName}_level`] = result.level;
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
      const variableName = typeof storeAs === 'string' && storeAs.startsWith('$') ? storeAs.substring(1) : storeAs;
      const value = context[variableName];
      result = this.scoringProcessor.processSimpleScore(formula.scoring, value, context);
    } else {
      throw new RuleFlowException(`Invalid scoring configuration for formula '${formula.id}'`);
    }

    // ใช้ storeResult แทน
    this.storeResult(formula, result.score, context);

    // Store additional metadata
    const storeAs = formula.as || formula.id;
    const variableName = typeof storeAs === 'string' && storeAs.startsWith('$') ? storeAs.substring(1) : storeAs;

    if (result.decision) {
      context[`${variableName}_decision`] = result.decision;
    }

    if (result.level) {
      context[`${variableName}_level`] = result.level;
    }

    if (result.breakdown) {
      context[`${variableName}_breakdown`] = result.breakdown;
    }

    if (result && typeof result === 'object') {
      const excludedKeys = ['score', 'decision', 'level', 'breakdown'];
      for (const [key, value] of Object.entries(result)) {
        if (!excludedKeys.includes(key) && value !== undefined && value !== null) {
          context[`${variableName}_${key}`] = value;
        }
      }
    }
  }
}