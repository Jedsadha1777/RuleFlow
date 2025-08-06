import { RuleFlowException } from '../exceptions/RuleFlowException';
import { FunctionRegistry } from '../functions/FunctionRegistry';

export class ExpressionEvaluator {
  private variables: Record<string, any> = {};
  private functionRegistry: FunctionRegistry;
  
  // Automatic floating point precision handling
  private autoRoundPrecision: number | null = 10; // Default precision 10 decimal places
  private autoRoundThreshold: number = 1e-10; // Threshold for detecting precision issues

  constructor(functionRegistry?: FunctionRegistry) {
    this.functionRegistry = functionRegistry || new FunctionRegistry();
  }

  // ========================================
  // PUBLIC API METHODS
  // ========================================

  setVariables(vars: Record<string, any>): void {
    this.variables = { ...vars };
  }

  getFunctionRegistry(): FunctionRegistry {
    return this.functionRegistry;
  }

  setAutoRounding(precision: number = 10): void {
    this.autoRoundPrecision = precision;
  }

  disableAutoRounding(): void {
    this.autoRoundPrecision = null;
  }

  /**
   * Safe evaluation method with better error handling - matches PHP safeEval()
   */
  safeEval(expression: string, context: Record<string, any>): any {
    try {
      // Replace variables first
      const expr = this.replaceVariablesInExpression(expression, context);
      
      // Process functions
      const processedExpr = this.processFunctions(expr);
      
      // Validate final expression
      this.validateFinalExpression(processedExpr);

      // Tokenize and evaluate
      const tokens = this.tokenize(processedExpr);
      const processedTokens = this.processUnaryOperators(tokens);
      const postfix = this.convertToPostfix(processedTokens);
      
      const result = this.evaluatePostfix(postfix);
      
      // Apply automatic rounding to final result
      return this.applyAutoRounding(result);
    } catch (error: any) {
      throw new RuleFlowException(`Safe evaluation failed for '${expression}': ${error.message}`);
    }
  }

  /**
   * Evaluate $ expression at runtime - matches PHP evaluateDollarExpression()
   */
  evaluateDollarExpression(expression: string, context: Record<string, any>): any {
    // Set variables from context
    this.setVariables(context);

    // Evaluate expression with $ notation
    return this.evaluate(expression);
  }

  /**
   * Main evaluation method
   */
  evaluate(expression: string): any {
    let processedExpression = expression;

    // Add $ notation preprocessing
    processedExpression = this.preprocessDollarNotation(processedExpression);

    // First, replace variables
    processedExpression = this.replaceVariables(processedExpression);

    // Then, process function calls (including nested ones)
    processedExpression = this.processFunctionCalls(processedExpression);

    try {
      const result = this.safeEvaluate(processedExpression);
      // Apply automatic rounding to final result
      return this.applyAutoRounding(result);
    } catch (error) {
      throw new RuleFlowException(`Expression evaluation failed: ${expression} -> ${processedExpression}`);
    }
  }

  /**
   * Check if expression contains $ notation
   */
  hasDollarNotation(expression: string): boolean {
    return /\$[a-zA-Z_][a-zA-Z0-9_]*/.test(expression);
  }

  /**
   * Extract all $ variables from expression
   */
  extractDollarVariables(expression: string): string[] {
    const matches = expression.match(/\$([a-zA-Z_][a-zA-Z0-9_]*)/g);
    return matches ? matches.map(match => match.substring(1)) : [];
  }

  // ========================================
  // CORE ARITHMETIC EVALUATION
  // ========================================

  private safeEvaluate(expression: string): any {
    // Remove whitespace
    expression = expression.trim();

    try {
      // Improved pattern to support $ notation and more characters
      if (!/^[0-9+\-*/.() \w$]+$/.test(expression)) {
        throw new Error(`Invalid characters in expression: ${expression}`);
      }

      // Use more secure evaluation method
      const result = this.evaluateArithmetic(expression);
      return result;
    } catch (error) {
      throw new RuleFlowException(`Cannot evaluate expression: ${expression}`);
    }
  }

  private evaluateArithmetic(expression: string): number {
    // Convert to postfix notation and evaluate
    const tokens = this.tokenize(expression);
    const processedTokens = this.processUnaryOperators(tokens);
    const postfix = this.convertToPostfix(processedTokens);
    return this.evaluatePostfix(postfix);
  }

  // ========================================
  // TOKENIZATION & PARSING
  // ========================================

  private tokenize(expression: string): string[] {
    const tokens: string[] = [];
    let current = '';

    for (let i = 0; i < expression.length; i++) {
      const char = expression[i];

      if (/\d|\./.test(char)) {
        current += char;
      } else if (char === '*' && i + 1 < expression.length && expression[i + 1] === '*') {
        // Handle ** operator
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

  /**
   * Process unary operators (missing from original TypeScript version)
   */
  private processUnaryOperators(tokens: string[]): string[] {
    const processed: string[] = [];
    const length = tokens.length;
    
    for (let i = 0; i < length; i++) {
      const token = tokens[i];
      
      // Check if it's unary minus
      if (token === '-' && this.isUnaryMinus(tokens, i)) {
        // Convert unary minus to 'u-' to distinguish from binary minus
        processed.push('u-');
      } else {
        processed.push(token);
      }
    }
    
    return processed;
  }

  /**
   * Check if minus is unary (missing from original TypeScript version)
   */
  private isUnaryMinus(tokens: string[], index: number): boolean {
    // If it's the first token = unary
    if (index === 0) {
      return true;
    }
    
    const prevToken = tokens[index - 1];
    
    // If previous token is operator or '(' = unary
    return ['+', '-', '*', '/', '**', '('].includes(prevToken);
  }

  private convertToPostfix(tokens: string[]): string[] {
    const precedence: Record<string, number> = {
      '+': 1, '-': 1,
      '*': 2, '/': 2,
      '**': 3,
      'u-': 4  // 🔧 Unary minus has highest precedence
    };

    // 🔧 Right associative operators
    const rightAssociative = new Set(['**', 'u-']);

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
          precedence[operators[operators.length - 1]] &&
          (
            // Handle right associativity correctly
            (precedence[operators[operators.length - 1]] > precedence[token]) ||
            (precedence[operators[operators.length - 1]] === precedence[token] && 
             !rightAssociative.has(token))
          )
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
      } else if (token === 'u-') {
        // 🔧 Handle unary minus
        if (stack.length < 1) {
          throw new Error(`Invalid expression: insufficient operands for unary minus`);
        }
        
        const a = stack.pop()!;
        stack.push(-a);
      } else {
        // Binary operators
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

        // Apply automatic rounding to intermediate results
        stack.push(this.applyAutoRounding(result));
      }
    }

    if (stack.length !== 1) {
      throw new Error('Invalid expression: multiple results');
    }

    return stack[0];
  }

  // ========================================
  // VARIABLE AND FUNCTION PROCESSING
  // ========================================

  private preprocessDollarNotation(expression: string): string {
    return expression.replace(/\$([a-zA-Z_][a-zA-Z0-9_]*)/g, (match, varName) => {
      if (this.variables[varName] !== undefined) {
        const value = this.variables[varName];
        // If it's a number, return as string for expression
        if (typeof value === 'number') {
          return String(value);
        }
        // If it's a string that looks like a number, return as is
        if (typeof value === 'string' && /^-?\d+\.?\d*$/.test(value)) {
          return value;
        }
        // For other types, convert to string
        return String(value);
      } else {
        throw new RuleFlowException(
          `Variable '${match}' not found in context. Available variables: ${Object.keys(this.variables).join(', ')}`
        );
      }
    });
  }

  private replaceVariables(expression: string): string {
    let processedExpression = expression;

    // Improved variable replacement to avoid replacing function names
    for (const [key, value] of Object.entries(this.variables)) {
      // Replace variable (not prefixed with $) - but avoid replacing function names
      // Use word boundary and check it's not a function
      const directRegex = new RegExp(`\\b${key}\\b(?!\\s*\\()`, 'g');
      processedExpression = processedExpression.replace(directRegex, String(value));
    }

    return processedExpression;
  }

  /**
   * Replace variables in expression - matches PHP replaceVariables()
   */
  private replaceVariablesInExpression(expression: string, vars: Record<string, any>): string {
    let expr = expression.trim();
    
    // Replace $variable with actual values
    expr = expr.replace(/\$([a-zA-Z_][a-zA-Z0-9_]*)/g, (match, varName) => {
      if (vars[varName] !== undefined) {
        const value = vars[varName];
        
        if (typeof value === 'number' || (typeof value === 'string' && /^-?\d+\.?\d*$/.test(value))) {
          return String(value);
        } else {
          throw new RuleFlowException(`Variable ${varName} must be numeric, got: ${typeof value}`);
        }
      } else {
        throw new RuleFlowException(`Variable $${varName} not found in context`);
      }
    });
    
    // Handle variables without $ prefix
    for (const [varName, value] of Object.entries(vars)) {
      if (typeof value === 'number' || (typeof value === 'string' && /^-?\d+\.?\d*$/.test(value))) {
        // Replace whole word boundaries only
        const regex = new RegExp(`\\b${varName}\\b`, 'g');
        expr = expr.replace(regex, String(value));
      }
    }
    
    return expr;
  }

  private processFunctionCalls(expression: string): string {
    if (!this.hasFunctionCalls(expression)) {
      return expression;
    }
    
    try {
      return this.processInnerMostFunctionsRecursive(expression);
    } catch (error: any) {
      throw new RuleFlowException(`Function processing failed: ${error.message}`);
    }
  }

  private hasFunctionCalls(expression: string): boolean {
    return /[a-zA-Z_][a-zA-Z0-9_]*\s*\([^)]*\)/.test(expression);
  }

  // Alternative approach - ใช้ recursive parsing
  private processInnerMostFunctionsRecursive(expression: string): string {
    
    // หา function call แรกที่เจอ (จากซ้ายไปขวา)
    const functionMatch = expression.match(/([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/);
    if (!functionMatch) {
      return expression; // ไม่มี function calls
    }
    
    const functionName = functionMatch[1];
    const startIndex = functionMatch.index!;
    const openParenIndex = startIndex + functionName.length;
    
    // หา closing parenthesis ที่ match
    let parenCount = 0;
    let endIndex = -1;
    
    for (let i = openParenIndex; i < expression.length; i++) {
      if (expression[i] === '(') {
        parenCount++;
      } else if (expression[i] === ')') {
        parenCount--;
        if (parenCount === 0) {
          endIndex = i;
          break;
        }
      }
    }
    
    if (endIndex === -1) {
      throw new RuleFlowException(`Unmatched parentheses in: ${expression}`);
    }
    
    const argsString = expression.substring(openParenIndex + 1, endIndex);
    const fullMatch = expression.substring(startIndex, endIndex + 1);

    
    // ถ้า args ยังมี function calls ให้ process ก่อน
    const processedArgs = this.hasFunctionCalls(argsString) 
      ? this.processInnerMostFunctionsRecursive(argsString)
      : argsString;
    
    try {
      const args = this.parseArguments(processedArgs);
      const result = this.functionRegistry.call(functionName, args);
      const resultStr = String(this.applyAutoRounding(result));
      

      
      // แทนที่ function call ด้วยผลลัพธ์
      const newExpression = expression.substring(0, startIndex) + 
                          resultStr + 
                          expression.substring(endIndex + 1);
      
      // ถ้ายังมี function calls อื่น ให้ process ต่อ
      return this.hasFunctionCalls(newExpression) 
        ? this.processInnerMostFunctionsRecursive(newExpression)
        : newExpression;
        
    } catch (error: any) {
      throw new RuleFlowException(`Function call failed: ${fullMatch} - ${error.message}`);
    }
  }

  /**
   * Process functions - matches PHP processFunctions()
   */
  private processFunctions(expression: string): string {
    // Simple function processing - replace function calls with results
    return expression.replace(/([a-zA-Z_][a-zA-Z0-9_]*)\s*\(([^)]*)\)/g, (match, functionName, argsString) => {
      try {
        const args = this.parseArguments(argsString);
        const result = this.functionRegistry.call(functionName, args);
        return String(this.applyAutoRounding(result));
      } catch (error: any) {
        throw new RuleFlowException(`Function processing failed: ${match} - ${error.message}`);
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

  /**
   * Validate final expression - matches PHP validateFinalExpression()
   */
  private validateFinalExpression(expr: string): void {
    const availableFunctions = this.functionRegistry.getAvailableFunctions();
    for (const func of availableFunctions) {
      if (expr.includes(func)) {
        return;
      }
    }

    if (/\$/.test(expr)) {
      throw new RuleFlowException(`Expression contains unresolved variables or invalid characters: '${expr}'`);
    }
    
    if (!/^[0-9+\-*\/\(\)\s\.\*]+$/.test(expr)) {
      throw new RuleFlowException(`Expression contains unresolved variables or invalid characters: '${expr}'`);
    }
  }

  // ========================================
  // UTILITY METHODS
  // ========================================

  /**
   * Core automatic rounding logic - matches PHP applyAutoRounding()
   */
  public applyAutoRounding(value: number): number {
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
}