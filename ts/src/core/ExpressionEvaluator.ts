import { RuleFlowException } from '../exceptions/RuleFlowException.js';
import { FunctionRegistry } from '../functions/FunctionRegistry.js';

export class ExpressionEvaluator {
  private variables: Record<string, any> = {};
  private functionRegistry: FunctionRegistry;

  constructor(functionRegistry?: FunctionRegistry) {
    this.functionRegistry = functionRegistry || new FunctionRegistry();
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
      return this.safeEvaluate(processedExpression);
    } catch (error) {
      throw new RuleFlowException(`Expression evaluation failed: ${expression} -> ${processedExpression}`);
    }
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
        return String(result);
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
          args.push(evaluated);
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
      
      return Function(`"use strict"; return (${expression})`)();
    } catch (error) {
      throw new RuleFlowException(`Cannot evaluate expression: ${expression}`);
    }
  }
}