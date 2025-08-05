/**
 * RuleFlow Core - FormulaProcessor
 * Complete 1:1 match with TypeScript version
 */

class FormulaProcessor {
    constructor(functionRegistry) {
        this.functionRegistry = functionRegistry || new FunctionRegistry();
        this.evaluator = new ExpressionEvaluator(this.functionRegistry);
    }

    // ====================================
    // MAIN PROCESSING METHOD
    // ====================================

    /**
     * Process array of formulas - matches TypeScript exactly
     */
    process(formulas, inputs) {
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
            } catch (error) {
                throw new RuleFlowException(`Error processing formula '${formula.id}': ${error.message}`);
            }
        }

        return context;
    }

    // ====================================
    // FORMULA PROCESSING METHODS
    // ====================================

    /**
     * Process mathematical formula
     */
    processFormula(formula, context) {
        // Validate inputs
        if (formula.inputs) {
            for (const input of formula.inputs) {
                const inputKey = input.startsWith('$') ? input.substring(1) : input;
                if (context[inputKey] === undefined) {
                    throw new RuleFlowException(`Input '${input}' not found in context`);
                }
            }
        }

        // Evaluate formula using safeEval with context
        const result = this.evaluator.safeEval(formula.formula, context);

        // Store result
        context[formula.id] = result;

        // Store as variable if specified
        if (formula.as) {
            const varName = formula.as.startsWith('$') ? formula.as.substring(1) : formula.as;
            context[varName] = result;
        }
    }

    /**
     * Process switch logic - matches TypeScript exactly
     */
    processSwitch(formula, context) {
        const switchVariable = formula.switch;
        let switchValue;

        // Get switch value
        if (switchVariable.startsWith('$')) {
            const varName = switchVariable.substring(1);
            switchValue = context[varName];
        } else {
            switchValue = context[switchVariable];
        }

        if (switchValue === undefined) {
            throw new RuleFlowException(`Switch variable '${switchVariable}' not found in context`);
        }

        // Process when conditions
        if (formula.when && Array.isArray(formula.when)) {
            for (const whenClause of formula.when) {
                if (this.evaluateCondition(whenClause.if, switchValue, context)) {
                    const result = this.resolveValue(whenClause.result, context);
                    context[formula.id] = result;
                    
                    // Store as variable if specified
                    if (formula.as) {
                        const varName = formula.as.startsWith('$') ? formula.as.substring(1) : formula.as;
                        context[varName] = result;
                    }

                    // Set additional variables if specified
                    if (whenClause.set_vars) {
                        for (const [varKey, varValue] of Object.entries(whenClause.set_vars)) {
                            const finalVarName = varKey.startsWith('$') ? varKey.substring(1) : varKey;
                            context[finalVarName] = this.resolveValue(varValue, context);
                        }
                    }
                    return;
                }
            }
        }

        // Use default value if no conditions matched
        if (formula.default !== undefined) {
            const result = this.resolveValue(formula.default, context);
            context[formula.id] = result;
            
            if (formula.as) {
                const varName = formula.as.startsWith('$') ? formula.as.substring(1) : formula.as;
                context[varName] = result;
            }

            // Set additional variables for default case
            if (formula.set_vars) {
                for (const [varKey, varValue] of Object.entries(formula.set_vars)) {
                    const finalVarName = varKey.startsWith('$') ? varKey.substring(1) : varKey;
                    context[finalVarName] = this.resolveValue(varValue, context);
                }
            }
        }
    }

    /**
     * Process function call - NEW method to match TypeScript
     */
    processFunctionCall(formula, context) {
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
                    } catch (error) {
                        throw new RuleFlowException(`Cannot evaluate parameter '${param}': ${error.message}`);
                    }
                }
            }

            // Return literal value
            return param;
        });

        try {
            const result = this.functionRegistry.call(functionName, resolvedParams);
            
            // Round numeric results
            const roundedResult = typeof result === 'number' ? 
                Math.round(result * 1000000) / 1000000 : result;

            context[formula.id] = roundedResult;

            // Store as variable if specified
            if (formula.as) {
                const varName = formula.as.startsWith('$') ? formula.as.substring(1) : formula.as;
                context[varName] = roundedResult;
            }

        } catch (error) {
            throw new RuleFlowException(`Function call '${functionName}' failed: ${error.message}`);
        }
    }

    /**
     * Process accumulative scoring - NEW method to match TypeScript
     */
    processAccumulativeScoring(formula, context) {
        let totalScore = 0;
        const details = [];

        if (!formula.rules || !Array.isArray(formula.rules)) {
            throw new RuleFlowException(`Formula '${formula.id}' rules must be an array`);
        }

        for (const rule of formula.rules) {
            if (rule.var && rule.ranges) {
                const varValue = context[rule.var];
                if (varValue === undefined) {
                    continue; // Skip if variable not found
                }

                // Process ranges for this variable
                for (const range of rule.ranges) {
                    if (this.evaluateCondition(range.if, varValue, context)) {
                        const score = range.score || 0;
                        totalScore += score;
                        
                        details.push({
                            variable: rule.var,
                            value: varValue,
                            condition: range.if,
                            score: score,
                            tier: range.tier || null
                        });
                        break; // Only match first applicable range
                    }
                }
            }
        }

        const result = {
            total_score: totalScore,
            details: details
        };

        context[formula.id] = result;

        // Store as variable if specified
        if (formula.as) {
            const varName = formula.as.startsWith('$') ? formula.as.substring(1) : formula.as;
            context[varName] = result;
        }
    }

    /**
     * Process advanced scoring - NEW method to match TypeScript
     */
    processAdvancedScoring(formula, context) {
        if (!formula.scoring || !formula.scoring.ifs) {
            throw new RuleFlowException(`Formula '${formula.id}' must have scoring.ifs configuration`);
        }

        const scoringConfig = formula.scoring.ifs;
        const vars = scoringConfig.vars;
        const tree = scoringConfig.tree;

        if (!vars || !Array.isArray(vars) || vars.length === 0) {
            throw new RuleFlowException(`Scoring configuration must specify variables array`);
        }

        if (!tree || !Array.isArray(tree)) {
            throw new RuleFlowException(`Scoring configuration must have tree array`);
        }

        // Get primary variable value (first variable)
        const primaryVar = vars[0];
        const primaryValue = context[primaryVar];

        if (primaryValue === undefined) {
            throw new RuleFlowException(`Primary scoring variable '${primaryVar}' not found in context`);
        }

        // Find matching tree node
        for (const treeNode of tree) {
            if (this.evaluateCondition(treeNode.if, primaryValue, context)) {
                // Process ranges within this tree node
                if (treeNode.ranges && Array.isArray(treeNode.ranges)) {
                    // Get secondary variable if exists
                    const secondaryVar = vars[1];
                    const secondaryValue = secondaryVar ? context[secondaryVar] : null;

                    for (const range of treeNode.ranges) {
                        const targetValue = secondaryVar ? secondaryValue : primaryValue;
                        
                        if (this.evaluateCondition(range.if, targetValue, context)) {
                            const result = {
                                score: range.score || 0,
                                tier: range.tier || null,
                                level: range.level || null,
                                primary_var: primaryVar,
                                primary_value: primaryValue,
                                secondary_var: secondaryVar,
                                secondary_value: secondaryValue
                            };

                            context[formula.id] = result;

                            if (formula.as) {
                                const varName = formula.as.startsWith('$') ? formula.as.substring(1) : formula.as;
                                context[varName] = result;
                            }
                            return;
                        }
                    }
                }
                break;
            }
        }

        // No matching condition found
        const result = {
            score: 0,
            tier: null,
            level: null,
            primary_var: primaryVar,
            primary_value: primaryValue
        };

        context[formula.id] = result;

        if (formula.as) {
            const varName = formula.as.startsWith('$') ? formula.as.substring(1) : formula.as;
            context[varName] = result;
        }
    }

    // ====================================
    // HELPER METHODS
    // ====================================

    /**
     * Evaluate condition - NEW method to match TypeScript
     */
    evaluateCondition(condition, targetValue, context) {
        if (!condition) return false;

        // Handle AND conditions
        if (condition.and && Array.isArray(condition.and)) {
            return condition.and.every(subCondition => 
                this.evaluateCondition(subCondition, targetValue, context)
            );
        }

        // Handle OR conditions
        if (condition.or && Array.isArray(condition.or)) {
            return condition.or.some(subCondition => 
                this.evaluateCondition(subCondition, targetValue, context)
            );
        }

        // Handle simple operation
        if (condition.op !== undefined) {
            const leftValue = condition.var ? 
                (context[condition.var] !== undefined ? context[condition.var] : targetValue) : 
                targetValue;
            const rightValue = condition.value;

            return this.compareValues(leftValue, rightValue, condition.op);
        }

        return false;
    }

    /**
     * Compare values with operator - NEW method to match TypeScript
     */
    compareValues(left, right, operator) {
        switch (operator) {
            case '==':
            case '===':
                return left == right;
            case '!=':
            case '!==':
                return left != right;
            case '>':
                return Number(left) > Number(right);
            case '>=':
                return Number(left) >= Number(right);
            case '<':
                return Number(left) < Number(right);
            case '<=':
                return Number(left) <= Number(right);
            case 'between':
                if (Array.isArray(right) && right.length === 2) {
                    const numLeft = Number(left);
                    return numLeft >= Number(right[0]) && numLeft <= Number(right[1]);
                }
                return false;
            case 'in':
                return Array.isArray(right) && right.includes(left);
            case 'contains':
                return String(left).includes(String(right));
            case 'starts_with':
                return String(left).startsWith(String(right));
            case 'ends_with':
                return String(left).endsWith(String(right));
            default:
                throw new RuleFlowException(`Unknown operator: ${operator}`);
        }
    }

    /**
     * Resolve value from context or return literal - NEW method to match TypeScript
     */
    resolveValue(value, context) {
        if (typeof value === 'string') {
            // Handle $ variable references
            if (value.startsWith('$')) {
                const varName = value.substring(1);
                return context[varName] !== undefined ? context[varName] : value;
            }

            // Handle direct variable references
            if (context[value] !== undefined) {
                return context[value];
            }

            // Handle expressions
            if (value.includes('+') || value.includes('-') || value.includes('*') || value.includes('/') || value.includes('(')) {
                try {
                    this.evaluator.setVariables(context);
                    return this.evaluator.evaluate(value);
                } catch (error) {
                    // Return literal value if evaluation fails
                    return value;
                }
            }
        }

        // Return literal value
        return value;
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { FormulaProcessor };
} else {
    window.FormulaProcessor = FormulaProcessor;
}