class ExpressionEvaluator {
    constructor(functionRegistry) {
        this.variables = {};
        this.functionRegistry = functionRegistry || new FunctionRegistry();
        this.autoRoundPrecision = 10; // Default precision
        this.autoRoundThreshold = 1e-10;
    }

    setVariables(vars) {
        this.variables = { ...vars };
    }

    getFunctionRegistry() {
        return this.functionRegistry;
    }

    setAutoRounding(precision = 10) {
        this.autoRoundPrecision = precision;
    }

    disableAutoRounding() {
        this.autoRoundPrecision = null;
    }

    /**
     * Safe evaluation method - exact TypeScript match
     */
    safeEval(expression, context) {
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
        } catch (error) {
            throw new RuleFlowException(`Safe evaluation failed for '${expression}': ${error.message}`);
        }
    }

    /**
     * Evaluate $ expression at runtime - exact TypeScript match
     */
    evaluateDollarExpression(expression, context) {
        // Set variables from context
        this.setVariables(context);

        // Evaluate expression with $ notation
        return this.evaluate(expression);
    }

    /**
     * Main evaluation method - exact TypeScript match
     */
    evaluate(expression) {
        let processedExpression = expression;

        // Preprocess $ notation
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
     * Check if expression contains $ notation - TypeScript method
     */
    hasDollarNotation(expression) {
        return /\$[a-zA-Z_][a-zA-Z0-9_]*/.test(expression);
    }

    /**
     * Extract all $ variables from expression - TypeScript method
     */
    extractDollarVariables(expression) {
        const matches = expression.match(/\$([a-zA-Z_][a-zA-Z0-9_]*)/g);
        return matches ? matches.map(match => match.substring(1)) : [];
    }

    // ========================================
    // CORE ARITHMETIC EVALUATION
    // ========================================

    safeEvaluate(expression) {
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

    evaluateArithmetic(expression) {
        // Convert to postfix notation and evaluate
        const tokens = this.tokenize(expression);
        const processedTokens = this.processUnaryOperators(tokens);
        const postfix = this.convertToPostfix(processedTokens);
        return this.evaluatePostfix(postfix);
    }

    // ========================================
    // TOKENIZATION & PARSING (TypeScript Methods)
    // ========================================

    tokenize(expression) {
        const tokens = [];
        let current = '';

        for (let i = 0; i < expression.length; i++) {
            const char = expression[i];

            if (/\d|\./.test(char)) {
                current += char;
            } else if (/\s/.test(char)) {
                if (current) {
                    tokens.push(current);
                    current = '';
                }
            } else if (/[+\-*/()]/.test(char)) {
                if (current) {
                    tokens.push(current);
                    current = '';
                }
                tokens.push(char);
            } else {
                current += char;
            }
        }

        if (current) {
            tokens.push(current);
        }

        return tokens;
    }

    /**
     * Process unary operators - TypeScript method
     */
    processUnaryOperators(tokens) {
        const processed = [];
        
        for (let i = 0; i < tokens.length; i++) {
            const token = tokens[i];
            
            if (token === '-' || token === '+') {
                // Check if this is a unary operator
                const prevToken = i > 0 ? tokens[i - 1] : null;
                const isUnary = !prevToken || 
                               prevToken === '(' || 
                               /[+\-*/]/.test(prevToken);
                
                if (isUnary) {
                    // This is a unary operator
                    const nextToken = tokens[i + 1];
                    if (nextToken && /^\d/.test(nextToken)) {
                        // Apply unary operator to next number
                        const value = parseFloat(nextToken);
                        processed.push(String(token === '-' ? -value : value));
                        i++; // Skip the next token as we've processed it
                    } else {
                        processed.push(token);
                    }
                } else {
                    processed.push(token);
                }
            } else {
                processed.push(token);
            }
        }
        
        return processed;
    }

    /**
     * Convert to postfix notation - TypeScript method
     */
    convertToPostfix(tokens) {
        const output = [];
        const operators = [];
        const precedence = { '+': 1, '-': 1, '*': 2, '/': 2, '**': 3 };
        const rightAssociative = { '**': true };

        for (const token of tokens) {
            if (/^\d+\.?\d*$/.test(token)) {
                output.push(parseFloat(token));
            } else if (token === '(') {
                operators.push(token);
            } else if (token === ')') {
                while (operators.length && operators[operators.length - 1] !== '(') {
                    output.push(operators.pop());
                }
                operators.pop(); // Remove '('
            } else if (precedence[token]) {
                while (
                    operators.length &&
                    operators[operators.length - 1] !== '(' &&
                    (precedence[operators[operators.length - 1]] > precedence[token] ||
                     (precedence[operators[operators.length - 1]] === precedence[token] && !rightAssociative[token]))
                ) {
                    output.push(operators.pop());
                }
                operators.push(token);
            } else {
                // Assume it's a number or variable
                const num = parseFloat(token);
                output.push(isNaN(num) ? token : num);
            }
        }

        while (operators.length) {
            output.push(operators.pop());
        }

        return output;
    }

    /**
     * Evaluate postfix expression - TypeScript method
     */
    evaluatePostfix(postfix) {
        const stack = [];

        for (const token of postfix) {
            if (typeof token === 'number') {
                stack.push(token);
            } else if (typeof token === 'string' && /[+\-*/]/.test(token)) {
                if (stack.length < 2) {
                    throw new Error(`Invalid expression: insufficient operands for ${token}`);
                }
                
                const b = stack.pop();
                const a = stack.pop();
                
                let result;
                switch (token) {
                    case '+': result = a + b; break;
                    case '-': result = a - b; break;
                    case '*': result = a * b; break;
                    case '/': 
                        if (b === 0) throw new Error('Division by zero');
                        result = a / b; 
                        break;
                    case '**': result = Math.pow(a, b); break;
                    default: throw new Error(`Unknown operator: ${token}`);
                }
                
                stack.push(result);
            } else {
                // Variable or function call
                stack.push(token);
            }
        }

        if (stack.length !== 1) {
            throw new Error('Invalid expression: stack should contain exactly one result');
        }

        return stack[0];
    }

    // ========================================
    // HELPER METHODS (TypeScript Methods)
    // ========================================

    /**
     * Apply automatic rounding - TypeScript method
     */
    applyAutoRounding(result) {
        if (this.autoRoundPrecision === null || typeof result !== 'number') {
            return result;
        }

        // Check if the result has precision issues
        const rounded = Math.round(result * Math.pow(10, this.autoRoundPrecision)) / Math.pow(10, this.autoRoundPrecision);
        
        // Only apply rounding if the difference is within threshold
        if (Math.abs(result - rounded) < this.autoRoundThreshold) {
            return rounded;
        }
        
        return result;
    }

    /**
     * Preprocess $ notation - TypeScript method
     */
    preprocessDollarNotation(expression) {
        return expression.replace(/\$([a-zA-Z_][a-zA-Z0-9_]*)/g, (match, varName) => {
            if (this.variables.hasOwnProperty(varName)) {
                return String(this.variables[varName]);
            }
            return match; // Keep as is if variable not found
        });
    }

    /**
     * Replace variables in expression - TypeScript method
     */
    replaceVariables(expression) {
        return expression.replace(/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/g, (match) => {
            if (this.variables.hasOwnProperty(match)) {
                return String(this.variables[match]);
            }
            return match;
        });
    }

    /**
     * Replace variables in expression with context - TypeScript method
     */
    replaceVariablesInExpression(expression, context) {
        return expression.replace(/\b([a-zA-Z_][a-zA-Z0-9_]*)\b/g, (match) => {
            if (context.hasOwnProperty(match)) {
                return String(context[match]);
            }
            return match;
        });
    }

    /**
     * Process function calls - TypeScript method
     */
    processFunctionCalls(expression) {
        const hasFunctions = /[a-zA-Z_][a-zA-Z0-9_]*\s*\(/.test(expression);
        if (!hasFunctions) {
            return expression;
        }
        
        try {
            return this.processFunctions(expression);
        } catch (error) {
            throw new RuleFlowException(`JS Function processing failed: ${error.message}`);
        }
    }

    /**
     * Process functions - TypeScript method
     */
    processFunctions(expression) {
        console.log(`🔍 JS Processing: ${expression}`);
        
        // หา function call แรกที่เจอ (จากซ้ายไปขวา)
        const functionMatch = expression.match(/([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/);
        if (!functionMatch) {
            return expression; // ไม่มี function calls
        }
        
        const functionName = functionMatch[1];
        const startIndex = functionMatch.index;
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
        
        console.log(`📞 JS Found function: ${functionName}(${argsString})`);
        
        // ถ้า args ยังมี function calls ให้ process ก่อน
        const hasNestedFunctions = /[a-zA-Z_][a-zA-Z0-9_]*\s*\(/.test(argsString);
        const processedArgs = hasNestedFunctions 
            ? this.processFunctions(argsString)
            : argsString;
        
        try {
            const args = this.parseArguments(processedArgs);
            const result = this.functionRegistry.call(functionName, args);
            const resultStr = String(result);
            
            console.log(`✅ JS ${functionName}(${processedArgs}) = ${resultStr}`);
            
            // แทนที่ function call ด้วยผลลัพธ์
            const newExpression = expression.substring(0, startIndex) + 
                                resultStr + 
                                expression.substring(endIndex + 1);
            
            // ถ้ายังมี function calls อื่น ให้ process ต่อ
            const hasMoreFunctions = /[a-zA-Z_][a-zA-Z0-9_]*\s*\(/.test(newExpression);
            return hasMoreFunctions 
                ? this.processFunctions(newExpression)
                : newExpression;
                
        } catch (error) {
            throw new RuleFlowException(`JS Function call failed: ${fullMatch} - ${error.message}`);
        }
    }

    /**
     * Parse function arguments - helper method
     */
    parseArguments(argsStr) {
        if (!argsStr.trim()) return [];
        
        const args = [];
        let currentArg = '';
        let parenDepth = 0;
        let inQuotes = false;
        let quoteChar = '';
        
        for (let i = 0; i < argsStr.length; i++) {
            const char = argsStr[i];
            
            if (!inQuotes && (char === '"' || char === "'")) {
                inQuotes = true;
                quoteChar = char;
                currentArg += char;
            } else if (inQuotes && char === quoteChar) {
                inQuotes = false;
                currentArg += char;
            } else if (!inQuotes && char === '(') {
                parenDepth++;
                currentArg += char;
            } else if (!inQuotes && char === ')') {
                parenDepth--;
                currentArg += char;
            } else if (!inQuotes && char === ',' && parenDepth === 0) {
                const trimmed = currentArg.trim();
                if (trimmed) {
                    const num = parseFloat(trimmed);
                    args.push(isNaN(num) ? trimmed : num);
                }
                currentArg = '';
            } else {
                currentArg += char;
            }
        }
        
        if (currentArg.trim()) {
            const trimmed = currentArg.trim();
            const num = parseFloat(trimmed);
            args.push(isNaN(num) ? trimmed : num);
        }
        
        return args;
    }

    /**
     * Validate final expression - helper method
     */
    validateFinalExpression(expression) {
        if (!/^[0-9+\-*/.() \s]+$/.test(expression)) {
            throw new RuleFlowException(`Expression contains invalid characters: '${expression}'`);
        }
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ExpressionEvaluator };
} else {
    window.ExpressionEvaluator = ExpressionEvaluator;
}