export interface RuleFlowConfig {
  formulas: Formula[];
}

export interface Formula {
  id: string;
  formula?: string;
  inputs?: string[];
  switch?: string;
  when?: WhenCondition[];
  default?: any;
}

export interface WhenCondition {
  if: Condition;
  result: any;
}

export interface Condition {
  op: string;
  value: any;
}

// Exception
export class RuleFlowException extends Error {
  constructor(message: string) {
    super(message);
    this.name = 'RuleFlowException';
  }
}

// Fixed Expression Evaluator
class ExpressionEvaluator {
  private variables: Record<string, any> = {};

  setVariables(vars: Record<string, any>): void {
    this.variables = { ...vars };
  }

  evaluate(expression: string): any {
    console.log('Evaluating expression:', expression);
    console.log('Available variables:', this.variables);
    
    let processedExpression = expression;
    
    // แทนที่ตัวแปรทั้งแบบ $variable และ variable
    for (const [key, value] of Object.entries(this.variables)) {
      // แทนที่ $variable
      const dollarRegex = new RegExp(`\\$${key}\\b`, 'g');
      processedExpression = processedExpression.replace(dollarRegex, String(value));
      
      // แทนที่ variable (ไม่มี $)
      const directRegex = new RegExp(`\\b${key}\\b`, 'g');
      processedExpression = processedExpression.replace(directRegex, String(value));
    }

    console.log('Processed expression:', processedExpression);

    try {
      // ตรวจสอบว่ามีเฉพาะตัวเลขและ operator
      if (!/^[0-9+\-*/().\ ]+$/.test(processedExpression)) {
        throw new Error(`Invalid characters in expression: ${processedExpression}`);
      }
      
      const result = Function(`"use strict"; return (${processedExpression})`)();
      console.log('Result:', result);
      return result;
      
    } catch (error) {
      console.error('Evaluation error:', error);
      throw new RuleFlowException(`Expression evaluation failed: ${expression} -> ${processedExpression}`);
    }
  }
}

// Main RuleFlow Class
export class RuleFlow {
  private evaluator: ExpressionEvaluator;

  constructor() {
    this.evaluator = new ExpressionEvaluator();
  }

  async evaluate(config: RuleFlowConfig, inputs: Record<string, any>): Promise<Record<string, any>> {
    if (!config.formulas || !Array.isArray(config.formulas)) {
      throw new RuleFlowException('Configuration must have formulas array');
    }

    const context = { ...inputs };

    for (const formula of config.formulas) {
      if (!formula.id) {
        throw new RuleFlowException('Formula must have an id');
      }

      if (formula.formula) {
        // Handle math formula
        if (formula.inputs) {
          for (const required of formula.inputs) {
            if (!(required in context)) {
              throw new RuleFlowException(`Required input '${required}' is missing`);
            }
          }
        }

        this.evaluator.setVariables(context);
        const result = this.evaluator.evaluate(formula.formula);
        context[formula.id] = result;

      } else if (formula.switch) {
        // Handle switch logic
        const switchValue = context[formula.switch];
        
        if (switchValue === undefined) {
          throw new RuleFlowException(`Switch variable '${formula.switch}' not found`);
        }

        let matched = false;

        for (const when of formula.when || []) {
          if (this.evaluateCondition(when.if, switchValue)) {
            context[formula.id] = when.result;
            matched = true;
            break;
          }
        }

        if (!matched && formula.default !== undefined) {
          context[formula.id] = formula.default;
        }

      } else {
        throw new RuleFlowException(`Formula '${formula.id}' must have either 'formula' or 'switch'`);
      }
    }

    return context;
  }

  private evaluateCondition(condition: Condition, switchValue: any): boolean {
    switch (condition.op) {
      case '==': return switchValue == condition.value;
      case '!=': return switchValue != condition.value;
      case '>': return switchValue > condition.value;
      case '<': return switchValue < condition.value;
      case '>=': return switchValue >= condition.value;
      case '<=': return switchValue <= condition.value;
      default:
        throw new RuleFlowException(`Unknown operator: ${condition.op}`);
    }
  }

  getSystemInfo(): Record<string, string> {
    return {
      version: '1.0.0-typescript',
      engine: 'TypeScript'
    };
  }
}