import { RuleFlowException } from '../exceptions/RuleFlowException';
import { FunctionRegistry } from '../functions/FunctionRegistry';

export class ExpressionEvaluator {
  private variables: Record<string, any> = {};
  private functionRegistry: FunctionRegistry;
  
  // 🆕 Automatic floating point precision handling
  private autoRoundPrecision: number | null = 10; // Default precision 10 decimal places
  private autoRoundThreshold: number = 1e-10; // Threshold for detecting precision issues

  constructor(functionRegistry?: FunctionRegistry) {
    this.functionRegistry = functionRegistry || new FunctionRegistry();
  }

  // 🆕 Method สำหรับ control automatic rounding
  setAutoRounding(precision: number = 10): void {
    this.autoRoundPrecision = precision;
  }

  disableAutoRounding(): void {
    this.autoRoundPrecision = null;
  }

  setVariables(vars: Record<string, any>): void {
    this.variables = { ...vars };
  }

  getFunctionRegistry(): FunctionRegistry {
    return this.functionRegistry;
  }

  evaluate(expression: string): any {
    let processedExpression = expression;
    
    // First, replace variables
    processedExpression = this.replaceVariables(processedExpression);
    
    // Then, process function calls (including nested ones)
    processedExpression = this.processFunctionCalls(processedExpression);

    try {
      const result = this.safeEvaluate(processedExpression);
      // 🎯 Apply automatic rounding to final result
      return this.applyAutoRounding(result);
    } catch (error) {
      throw new RuleFlowException(`Expression evaluation failed: ${expression} -> ${processedExpression}`);
    }
  }

  // 🆕 Core automatic rounding logic
  private applyAutoRounding(value: number): number {
    if (this.autoRoundPrecision === null || !Number.isFinite(value)) {
      return value;
    }

    // Calculate rounded value
    const factor = Math.pow(10, this.autoRoundPrecision);
    const rounded = Math.round(value * factor) / factor;
    const difference = Math.abs(value - rounded);
    
    // If difference is very small (floating point precision issue), return rounded value
    if (difference < this.autoRoundThreshold) {
      return rounded;
    }
    
    return value;
  }

  private processFunctionCalls(expression: string): string {
    let processed = expression;
    let maxIterations = 10; // Prevent infinite loops
    let iteration = 0;
    
    // Keep processing until no more function calls found
    while (this.hasFunctionCalls(processed) && iteration < maxIterations) {
      processed = this.processInnerMostFunctions(processed);
      iteration++;
    }
    
    if (iteration >= maxIterations) {
      throw new RuleFlowException(`Too many nested function calls or circular references in: ${expression}`);
    }
    
    return processed;
  }

  private hasFunctionCalls(expression: string): boolean {
    return /[a-zA-Z_][a-zA-Z0-9_]*\s*\([^)]*\)/.test(expression);
  }

  private processInnerMostFunctions(expression: string): string {
    // Find and replace innermost function calls first
    // This handles nested functions like round(sqrt(pow(x, 2)))
    
    const functionPattern = /([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^()]*)\)/g;
    
    return expression.replace(functionPattern, (match, functionName, argsString) => {
      try {
        // Parse and evaluate arguments
        const args = this.parseArguments(argsString);
        
        // Call the function
        const result = this.functionRegistry.call(functionName, args);
        // 🎯 Apply automatic rounding to function results
        return String(this.applyAutoRounding(result));
      } catch (error : any) {
        throw new RuleFlowException(`Function call failed: ${match} - ${error.message}`);
      }
    });
  }

  private parseArguments(argsString: string): any[] {
    if (!argsString.trim()) return [];
    
    const args: any[] = [];
    const argStrings = this.splitArgumentsCorrectly(argsString);
    
    for (const argString of argStrings) {
      const trimmed = argString.trim();
      
      // Try to parse as number
      if (/^-?\d*\.?\d+$/.test(trimmed)) {
        args.push(parseFloat(trimmed));
      }
      // Try to parse as boolean
      else if (trimmed === 'true') {
        args.push(true);
      }
      else if (trimmed === 'false') {
        args.push(false);
      }
      // Try to parse as string literal
      else if (/^["'].*["']$/.test(trimmed)) {
        args.push(trimmed.slice(1, -1)); // Remove quotes
      }
      // If it's still an expression, evaluate it
      else {
        try {
          const evaluated = this.safeEvaluate(trimmed);
          args.push(this.applyAutoRounding(evaluated));
        } catch {
          // If evaluation fails, treat as literal value
          args.push(trimmed);
        }
      }
    }
    
    return args;
  }

  private splitArgumentsCorrectly(argsString: string): string[] {
    // Split arguments while respecting nested parentheses and quotes
    const args: string[] = [];
    let current = '';
    let depth = 0;
    let inQuotes = false;
    let quoteChar = '';
    
    for (let i = 0; i < argsString.length; i++) {
      const char = argsString[i];
      
      if (!inQuotes) {
        if (char === '"' || char === "'") {
          inQuotes = true;
          quoteChar = char;
          current += char;
        } else if (char === '(') {
          depth++;
          current += char;
        } else if (char === ')') {
          depth--;
          current += char;
        } else if (char === ',' && depth === 0) {
          args.push(current.trim());
          current = '';
        } else {
          current += char;
        }
      } else {
        current += char;
        if (char === quoteChar && (i === 0 || argsString[i - 1] !== '\\')) {
          inQuotes = false;
          quoteChar = '';
        }
      }
    }
    
    if (current.trim()) {
      args.push(current.trim());
    }
    
    return args;
  }

  private replaceVariables(expression: string): string {
    let processedExpression = expression;
    
    // Replace variables (both $variable and variable)
    for (const [key, value] of Object.entries(this.variables)) {
      // Replace $variable
      const dollarRegex = new RegExp(`\\$${key}\\b`, 'g');
      processedExpression = processedExpression.replace(dollarRegex, String(value));
      
      // Replace variable (not prefixed with $) - but avoid replacing function names
      const directRegex = new RegExp(`\\b${key}\\b(?!\\s*\\()`, 'g');
      processedExpression = processedExpression.replace(directRegex, String(value));
    }

    return processedExpression;
  }

  private safeEvaluate(expression: string): any {
    // Remove whitespace
    expression = expression.trim();
    
    try {
      // Allow numbers, operators, parentheses, and dots
      if (!/^[0-9+\-*/().\ ]+$/.test(expression)) {
        throw new Error(`Invalid characters in expression: ${expression}`);
      }
      
      // 🎯 Use more secure evaluation method
      const result = this.evaluateArithmetic(expression);
      return result;
    } catch (error) {
      throw new RuleFlowException(`Cannot evaluate expression: ${expression}`);
    }
  }

  // 🆕 Safer arithmetic evaluation without using Function constructor
  private evaluateArithmetic(expression: string): number {
    // Convert to postfix notation and evaluate
    const tokens = this.tokenize(expression);
    const postfix = this.convertToPostfix(tokens);
    return this.evaluatePostfix(postfix);
  }

  private tokenize(expression: string): string[] {
    const tokens: string[] = [];
    let current = '';
    
    for (let i = 0; i < expression.length; i++) {
      const char = expression[i];
      
      if (/\d|\./.test(char)) {
        current += char;
      } else if (char === '*' && i + 1 < expression.length && expression[i + 1] === '*') {
        // 🔧 Handle ** operator
        if (current) {
          tokens.push(current);
          current = '';
        }
        tokens.push('**');
        i++; // Skip next *
      } else if (/[+\-*/()]/.test(char)) {
        if (current) {
          tokens.push(current);
          current = '';
        }
        tokens.push(char);
      } else if (char === ' ') {
        if (current) {
          tokens.push(current);
          current = '';
        }
      }
    }
    
    if (current) {
      tokens.push(current);
    }
    
    return tokens;
  }

  private convertToPostfix(tokens: string[]): string[] {
    const precedence: Record<string, number> = {
      '+': 1, '-': 1,
      '*': 2, '/': 2,
      '**': 3
    };
    
    const output: string[] = [];
    const operators: string[] = [];
    
    for (const token of tokens) {
      if (/^\d+\.?\d*$/.test(token)) {
        output.push(token);
      } else if (token === '(') {
        operators.push(token);
      } else if (token === ')') {
        while (operators.length && operators[operators.length - 1] !== '(') {
          output.push(operators.pop()!);
        }
        operators.pop(); // Remove '('
      } else if (precedence[token]) {
        while (
          operators.length &&
          operators[operators.length - 1] !== '(' &&
          precedence[operators[operators.length - 1]] >= precedence[token]
        ) {
          output.push(operators.pop()!);
        }
        operators.push(token);
      }
    }
    
    while (operators.length) {
      output.push(operators.pop()!);
    }
    
    return output;
  }

  private evaluatePostfix(postfix: string[]): number {
    const stack: number[] = [];
    
    for (const token of postfix) {
      if (/^\d+\.?\d*$/.test(token)) {
        stack.push(parseFloat(token));
      } else {
        if (stack.length < 2) {
          throw new Error(`Invalid expression: insufficient operands for ${token}`);
        }
        
        const b = stack.pop()!;
        const a = stack.pop()!;
        
        let result: number;
        switch (token) {
          case '+':
            result = a + b;
            break;
          case '-':
            result = a - b;
            break;
          case '*':
            result = a * b;
            break;
          case '**':
            result = Math.pow(a, b);
            break;
          case '/':
            if (Math.abs(b) < 1e-10) {
              throw new Error(`Division by zero or very small number: ${b}`);
            }
            result = a / b;
            break;
          default:
            throw new Error(`Unknown operator: ${token}`);
        }
        
        // 🎯 Apply automatic rounding to intermediate results
        stack.push(this.applyAutoRounding(result));
      }
    }
    
    if (stack.length !== 1) {
      throw new Error('Invalid expression: multiple results');
    }
    
    return stack[0];
  }
}