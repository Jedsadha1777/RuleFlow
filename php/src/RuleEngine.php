<?php

declare(strict_types=1);

/**
 * Rule Engine for evaluating formulas and conditions from JSON configuration
 */
class RuleFlow
{
    private array $allowedFunctions = [
        'abs', 'min', 'max', 'sqrt', 'round', 'ceil', 'floor'
    ];

    private array $operatorPrecedence = [
        '**' => 4,
        '*' => 3,
        '/' => 3,
        '+' => 2,
        '-' => 2
    ];

    private array $rightAssociative = ['**' => true];

    /**
     * Validate JSON configuration format
     */
    public function validateConfig(array $config): array
    {
        $errors = [];

        // Check required top-level keys
        if (!isset($config['formulas'])) {
            $errors[] = "Missing required 'formulas' key";
            return $errors;
        }

        if (!is_array($config['formulas'])) {
            $errors[] = "'formulas' must be an array";
            return $errors;
        }

        // Validate each formula
        foreach ($config['formulas'] as $index => $formula) {
            $formulaErrors = $this->validateFormula($formula, $index);
            $errors = array_merge($errors, $formulaErrors);
        }

        // Check for circular dependencies
        $dependencyErrors = $this->checkCircularDependencies($config['formulas']);
        $errors = array_merge($errors, $dependencyErrors);

        return $errors;
    }

    /**
     * Test configuration with sample inputs
     */
    public function testConfig(array $config, array $sampleInputs = []): array
    {
        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'test_results' => []
        ];

        // Validate config format
        $configErrors = $this->validateConfig($config);
        if (!empty($configErrors)) {
            $result['valid'] = false;
            $result['errors'] = $configErrors;
            return $result;
        }

        // Test with sample inputs if provided
        if (!empty($sampleInputs)) {
            try {
                $testResult = $this->evaluate($config, $sampleInputs);
                $result['test_results'] = $testResult;
            } catch (Exception $e) {
                $result['valid'] = false;
                $result['errors'][] = "Test execution failed: " . $e->getMessage();
            }
        }

        // Check for potential issues
        $warnings = $this->checkForWarnings($config);
        $result['warnings'] = $warnings;

        return $result;
    }

    /**
     * Evaluate formulas based on JSON configuration and input values
     */
    public function evaluate(array $config, array $inputs): array
    {
        // Validate config before execution
        $errors = $this->validateConfig($config);
        if (!empty($errors)) {
            throw new Exception("Invalid configuration: " . implode(', ', $errors));
        }

        $context = $inputs;

        foreach ($config['formulas'] as $formula) {
            $this->processFormula($formula, $context);
        }

        return $context;
    }

    /**
     * Process a single formula
     */
    private function processFormula(array $formula, array &$context): void
    {
        // Process expression formulas
        if (isset($formula['expression'])) {
            $this->processExpressionFormula($formula, $context);
        }

        // Process switch/case formulas
        if (isset($formula['switch_on'])) {
            $this->processSwitchFormula($formula, $context);
        }

        // Process weight/score calculation
        if (isset($formula['weight_score'])) {
            $this->processWeightScore($formula, $context);
        }
        
        // Process accumulative scoring
        if (isset($formula['score_rules'])) {
            $this->processAccumulativeScore($formula, $context);
        }
    }

    /**
     * Process expression-based formula
     */
    private function processExpressionFormula(array $formula, array &$context): void
    {
        try {
            // Prepare variables for expression
            $vars = [];
            
            // Handle case where inputs might be empty (for constants)
            if (!empty($formula['inputs'])) {
                foreach ($formula['inputs'] as $key) {
                    if (!isset($context[$key])) {
                        throw new Exception("Missing input: {$key}");
                    }
                    $vars[$key] = $context[$key];
                }
            }

            // Calculate and store result
            $result = $this->safeEval($formula['expression'], $vars);
            $storeKey = $formula['store_as'] ?? $formula['id'];
            $context[$storeKey] = $result;
        } catch (Exception $e) {
            throw new Exception("Error evaluating formula '{$formula['id']}': " . $e->getMessage());
        }
    }

    /**
     * Process switch/case formula
     */
    private function processSwitchFormula(array $formula, array &$context): void
    {
        $switchValue = $context[$formula['switch_on']] ?? null;

        if ($switchValue === null) {
            throw new Exception("Switch value '{$formula['switch_on']}' not found in context");
        }

        $matched = false;
        foreach ($formula['cases'] as $case) {
            if ($this->evaluateCondition($switchValue, $case['condition'])) {
                $context[$formula['id']] = $case['result'];
                
                // Set additional variables if specified
                if (isset($case['set_variables'])) {
                    foreach ($case['set_variables'] as $varName => $varValue) {
                        $context[$varName] = $varValue;
                    }
                }
                $matched = true;
                break;
            }
        }

        // Handle default value
        if (!$matched) {
            $context[$formula['id']] = $formula['default'] ?? null;
            
            // Set default variables if specified
            if (isset($formula['default_variables'])) {
                foreach ($formula['default_variables'] as $varName => $varValue) {
                    $context[$varName] = $varValue;
                }
            }
        }
    }

    /**
     * Process weight/score calculation with enhanced features
     */
    private function processWeightScore(array $formula, array &$context): void
    {
        $weightScore = $formula['weight_score'];
        
        // รองรับ multiple scoring ranges
        if (isset($weightScore['ranges'])) {
            $value = $context[$formula['store_as'] ?? $formula['id']] ?? null;
            if ($value === null) {
                $context[$formula['id'] . '_score'] = 0;
                return;
            }
            
            foreach ($weightScore['ranges'] as $range) {
                if ($this->evaluateCondition($value, $range['condition'])) {
                    $context[$formula['id'] . '_score'] = $range['score'];
                    
                    // Set additional variables if specified
                    if (isset($range['set_variables'])) {
                        foreach ($range['set_variables'] as $varName => $varValue) {
                            $context[$varName] = $varValue;
                        }
                    }
                    return;
                }
            }
            $context[$formula['id'] . '_score'] = $weightScore['default'] ?? 0;
        }
        // รองรับ multi-variable scoring
        elseif (isset($weightScore['multi_condition'])) {
            $result = $this->evaluateMultiConditionScore($weightScore['multi_condition'], $context);
            $context[$formula['id'] . '_score'] = $result['score'] ?? 0;
            
            // Store additional result data
            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    if ($key !== 'score') {
                        $context[$formula['id'] . '_' . $key] = $value;
                    }
                }
            }
        }
        // เก็บแบบเดิม
        else {
            $value = $context[$formula['store_as'] ?? $formula['id']] ?? null;
            if ($value === null) {
                $context[$formula['id'] . '_score'] = 0;
                return;
            }
            
            if ($this->evaluateCondition($value, $weightScore['condition'])) {
                $context[$formula['id'] . '_score'] = $weightScore['score'];
            } else {
                $context[$formula['id'] . '_score'] = 0;
            }
        }
    }

    /**
     * Evaluate multi-dimensional condition scoring (unlimited levels)
     */
    private function evaluateMultiConditionScore(array $multiCondition, array &$context): array
    {
        $variables = $multiCondition['variables'];
        $matrix = $multiCondition['score_matrix'];
        
        // Get values for all variables
        $values = [];
        foreach ($variables as $var) {
            $value = $context[$var] ?? null;
            if ($value === null) {
                return ['score' => 0];
            }
            $values[] = $value;
        }
        
        // Navigate through the multi-dimensional matrix
        $result = $this->navigateMatrix($matrix, $values, 0, $context);
        
        return is_array($result) ? $result : ['score' => 0];
    }

    /**
     * Recursively navigate through multi-dimensional scoring matrix
     */
    private function navigateMatrix(array $currentLevel, array $values, int $depth, array &$context): array
    {
        // Base case: if we've processed all variables
        if ($depth >= count($values)) {
            return $currentLevel;
        }
        
        $currentValue = $values[$depth];
        
        // Look for matching condition at current level
        foreach ($currentLevel as $item) {
            if (isset($item['condition']) && $this->evaluateCondition($currentValue, $item['condition'])) {
                // Set any variables specified at this level
                if (isset($item['set_variables'])) {
                    foreach ($item['set_variables'] as $varName => $varValue) {
                        $context[$varName] = $varValue;
                    }
                }
                
                // If there are more levels, continue navigation
                if (isset($item['ranges'])) {
                    return $this->navigateMatrix($item['ranges'], $values, $depth + 1, $context);
                }
                
                // Otherwise, return the result
                return $item;
            }
        }
        
        // No match found
        return ['score' => 0];
    }

    /**
     * Process accumulative scoring formula
     */
    private function processAccumulativeScore(array $formula, array &$context): void
    {
        $score = 0;
        $scoreKey = $formula['id'] . '_score';
        
        // Add existing score if any
        if (isset($context[$scoreKey])) {
            $score = $context[$scoreKey];
        }
        
        foreach ($formula['score_rules'] as $rule) {
            $ruleScore = $this->evaluateScoreRule($rule, $context);
            $score += $ruleScore;
            
            // Set additional variables if specified
            if (isset($rule['set_variables'])) {
                foreach ($rule['set_variables'] as $varName => $varValue) {
                    if ($ruleScore > 0 || !isset($rule['only_if_scored']) || !$rule['only_if_scored']) {
                        $context[$varName] = $varValue;
                    }
                }
            }
        }
        
        $context[$scoreKey] = $score;
    }

    /**
     * Evaluate individual score rule
     */
    private function evaluateScoreRule(array $rule, array $context): int
    {
        $variable = $rule['variable'];
        $value = $context[$variable] ?? null;
        
        if ($value === null) {
            return 0;
        }
        
        if (isset($rule['ranges'])) {
            foreach ($rule['ranges'] as $range) {
                if ($this->evaluateCondition($value, $range['condition'])) {
                    return $range['score'];
                }
            }
        } elseif (isset($rule['condition'])) {
            if ($this->evaluateCondition($value, $rule['condition'])) {
                return $rule['score'];
            }
        }
        
        return 0;
    }

    /**
     * Enhanced condition evaluation for between with multiple operators
     */
    private function evaluateCondition($value, array $condition): bool
    {
        $operator = $condition['operator'];
        $condValue = $condition['value'];

        return match ($operator) {
            '<' => $value < $condValue,
            '<=' => $value <= $condValue,
            '>' => $value > $condValue,
            '>=' => $value >= $condValue,
            '==' => $value == $condValue,
            '!=' => $value != $condValue,
            'between' => $value >= $condValue[0] && $value <= $condValue[1],
            'in' => in_array($value, $condValue, true),
            default => throw new Exception("Unsupported operator: {$operator}")
        };
    }

    /**
     * Safely evaluate mathematical expressions
     */
    private function safeEval(string $expr, array $vars): float
    {
        // Replace variables with values first, before processing functions
        $expr = $this->replaceVariables($expr, $vars);
        
        // Process functions after variable replacement
        $expr = $this->processFunctions($expr, $vars);

        // Validate final expression (no need for initial validation since we control the flow)
        $this->validateFinalExpression($expr);

        // Tokenize and evaluate
        $tokens = $this->tokenize($expr);
        $postfix = $this->convertToPostfix($tokens);
        
        return $this->evaluatePostfix($postfix);
    }

    /**
     * Process function calls in expression
     */
    private function processFunctions(string $expr, array $vars): string
    {
        foreach ($this->allowedFunctions as $func) {
            // Use recursive pattern to handle nested parentheses properly
            while (preg_match('/\b' . $func . '\s*\(/', $expr)) {
                $expr = $this->processFunction($expr, $func, $vars);
            }
        }

        return $expr;
    }

    /**
     * Process a single function call with proper parentheses matching
     */
    private function processFunction(string $expr, string $funcName, array $vars): string
    {
        $pattern = '/\b' . $funcName . '\s*\(/';
        if (!preg_match($pattern, $expr, $matches, PREG_OFFSET_CAPTURE)) {
            return $expr;
        }

        $startPos = $matches[0][1] + strlen($matches[0][0]);
        $openParens = 1;
        $pos = $startPos;
        $length = strlen($expr);

        // Find matching closing parenthesis
        while ($pos < $length && $openParens > 0) {
            if ($expr[$pos] === '(') {
                $openParens++;
            } elseif ($expr[$pos] === ')') {
                $openParens--;
            }
            $pos++;
        }

        if ($openParens > 0) {
            throw new Exception("Unmatched parentheses in function call: $funcName");
        }

        // Extract function arguments
        $argsStr = substr($expr, $startPos, $pos - $startPos - 1);
        $args = $this->parseArguments($argsStr);
        
        // Calculate function result
        $result = $this->callFunction($funcName, $args, $vars);
        
        // Replace function call with result
        $functionCall = substr($expr, $matches[0][1], $pos - $matches[0][1]);
        return str_replace($functionCall, (string)$result, $expr);
    }

    /**
     * Parse function arguments handling nested expressions
     */
    private function parseArguments(string $argsStr): array
    {
        if (trim($argsStr) === '') {
            return [];
        }

        $args = [];
        $current = '';
        $parenLevel = 0;
        $length = strlen($argsStr);

        for ($i = 0; $i < $length; $i++) {
            $char = $argsStr[$i];
            
            if ($char === '(') {
                $parenLevel++;
                $current .= $char;
            } elseif ($char === ')') {
                $parenLevel--;
                $current .= $char;
            } elseif ($char === ',' && $parenLevel === 0) {
                $args[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        if (!empty($current)) {
            $args[] = trim($current);
        }

        return $args;
    }

    /**
     * Call allowed function with arguments
     */
    private function callFunction(string $funcName, array $args, array $vars): float
    {
        // Evaluate arguments - they can be expressions or simple values
        $evaluatedArgs = [];
        foreach ($args as $arg) {
            $arg = trim($arg);
            if (is_numeric($arg)) {
                $evaluatedArgs[] = (float)$arg;
            } else {
                // Argument is an expression, evaluate it with simpler method
                try {
                    // Since variables are already replaced, just evaluate the numeric expression
                    $evaluatedArgs[] = $this->evaluateNumericExpression($arg);
                } catch (Exception $e) {
                    throw new Exception("Error evaluating argument '$arg' in function $funcName: " . $e->getMessage());
                }
            }
        }

        return match ($funcName) {
            'abs' => abs($evaluatedArgs[0]),
            'min' => min($evaluatedArgs),
            'max' => max($evaluatedArgs),
            'sqrt' => $this->safeSqrt($evaluatedArgs[0]),
            'round' => round($evaluatedArgs[0], (int)($evaluatedArgs[1] ?? 0)),
            'ceil' => ceil($evaluatedArgs[0]),
            'floor' => floor($evaluatedArgs[0]),
            default => throw new Exception("Unsupported function: {$funcName}")
        };
    }

    /**
     * Evaluate numeric expression (no variables, no functions)
     */
    private function evaluateNumericExpression(string $expr): float
    {
        // Simple check: ensure no alphabetic characters (which would indicate unresolved variables)
        if (preg_match('/[a-zA-Z_]/', $expr)) {
            throw new Exception("Invalid numeric expression: '$expr' contains unresolved variables");
        }

        // Tokenize and evaluate directly
        $tokens = $this->tokenize($expr);
        $postfix = $this->convertToPostfix($tokens);
        
        return $this->evaluatePostfix($postfix);
    }

    /**
     * Safe square root calculation
     */
    private function safeSqrt(float $value): float
    {
        if ($value < 0) {
            throw new Exception("Cannot calculate square root of negative number");
        }

        return sqrt($value);
    }

    /**
     * Validate expression format
     */
    private function validateExpression(string $expr): void
    {
        // Allow alphanumeric variables, numbers, operators, parentheses, commas, and spaces
        if (!preg_match('/^[a-zA-Z_0-9\+\-\*\/\(\)\s\.\*,]+$/', $expr)) {
            throw new Exception("Invalid expression: contains unsafe characters");
        }
    }

    /**
     * Replace variables with their values
     */
    private function replaceVariables(string $expr, array $vars): string
    {
        foreach ($vars as $key => $value) {
            if (!is_numeric($value)) {
                throw new Exception("Variable {$key} must be numeric, got: " . gettype($value));
            }
            $expr = preg_replace('/\b' . preg_quote($key, '/') . '\b/', (string)$value, $expr);
        }

        return $expr;
    }

    /**
     * Validate final expression after variable replacement
     */
    private function validateFinalExpression(string $expr): void
    {
        // Skip validation if expression still contains function calls
        foreach ($this->allowedFunctions as $func) {
            if (strpos($expr, $func) !== false) {
                return; // Functions will be processed separately
            }
        }
        
        // After functions are processed and variables replaced, should only contain numbers and operators
        if (!preg_match('/^[0-9+\-*\/\(\)\s\.\*]+$/', $expr)) {
            throw new Exception("Expression contains unresolved variables or invalid characters: '$expr'");
        }
    }

    /**
     * Tokenize expression
     */
    private function tokenize(string $expr): array
    {
        $expr = str_replace(['(', ')'], [' ( ', ' ) '], $expr);
        return array_filter(
            preg_split('/\s+/', trim($expr)),
            fn($token) => $token !== ''
        );
    }

    /**
     * Convert infix expression to postfix using Shunting-yard algorithm
     */
    private function convertToPostfix(array $tokens): array
    {
        $stack = [];
        $output = [];

        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $output[] = (float)$token;
            } elseif (array_key_exists($token, $this->operatorPrecedence)) {
                while (
                    !empty($stack) &&
                    end($stack) !== '(' &&
                    array_key_exists(end($stack), $this->operatorPrecedence) &&
                    (
                        ($this->operatorPrecedence[end($stack)] > $this->operatorPrecedence[$token]) ||
                        ($this->operatorPrecedence[end($stack)] == $this->operatorPrecedence[$token] && 
                         !isset($this->rightAssociative[$token]))
                    )
                ) {
                    $output[] = array_pop($stack);
                }
                $stack[] = $token;
            } elseif ($token === '(') {
                $stack[] = $token;
            } elseif ($token === ')') {
                while (!empty($stack) && end($stack) !== '(') {
                    $output[] = array_pop($stack);
                }
                if (empty($stack)) {
                    throw new Exception("Mismatched parentheses");
                }
                array_pop($stack); // Remove '('
            } else {
                throw new Exception("Unknown token: {$token}");
            }
        }

        while (!empty($stack)) {
            if (in_array(end($stack), ['(', ')'], true)) {
                throw new Exception("Mismatched parentheses");
            }
            $output[] = array_pop($stack);
        }

        return $output;
    }

    /**
     * Evaluate postfix expression
     */
    private function evaluatePostfix(array $postfix): float
    {
        $stack = [];

        foreach ($postfix as $token) {
            if (is_numeric($token)) {
                $stack[] = $token;
            } else {
                if (count($stack) < 2) {
                    throw new Exception("Invalid expression: insufficient operands");
                }

                $b = array_pop($stack);
                $a = array_pop($stack);

                $result = match ($token) {
                    '+' => $a + $b,
                    '-' => $a - $b,
                    '*' => $a * $b,
                    '/' => $this->safeDivision($a, $b),
                    '**' => pow($a, $b),
                    default => throw new Exception("Unknown operator: {$token}")
                };

                $stack[] = $result;
            }
        }

        if (count($stack) !== 1) {
            throw new Exception("Invalid expression: multiple results");
        }

        return $stack[0];
    }

    /**
     * Safe division operation
     */
    private function safeDivision(float $a, float $b): float
    {
        if ($b == 0) {
            throw new Exception("Division by zero");
        }

        return $a / $b;
    }

    /**
     * Validate individual formula structure
     */
    private function validateFormula(array $formula, int $index): array
    {
        $errors = [];
        $prefix = "Formula[$index]";

        // Check required 'id' field
        if (!isset($formula['id'])) {
            $errors[] = "$prefix: Missing required 'id' field";
            return $errors;
        }

        if (!is_string($formula['id']) || empty($formula['id'])) {
            $errors[] = "$prefix: 'id' must be a non-empty string";
        }

        // Validate expression formula
        if (isset($formula['expression'])) {
            $expressionErrors = $this->validateExpressionFormula($formula, $prefix);
            $errors = array_merge($errors, $expressionErrors);
        }

        // Validate switch formula
        if (isset($formula['switch_on'])) {
            $switchErrors = $this->validateSwitchFormula($formula, $prefix);
            $errors = array_merge($errors, $switchErrors);
        }

        // Validate weight_score
        if (isset($formula['weight_score'])) {
            $scoreErrors = $this->validateWeightScore($formula['weight_score'], $prefix);
            $errors = array_merge($errors, $scoreErrors);
        }
        
        // Validate score_rules
        if (isset($formula['score_rules'])) {
            $rulesErrors = $this->validateScoreRules($formula['score_rules'], $prefix);
            $errors = array_merge($errors, $rulesErrors);
        }

        // Check that formula has at least one action
        if (!isset($formula['expression']) && !isset($formula['switch_on']) && 
            !isset($formula['weight_score']) && !isset($formula['score_rules'])) {
            $errors[] = "$prefix: Formula must have at least one action (expression, switch_on, weight_score, or score_rules)";
        }

        return $errors;
    }

    /**
     * Validate expression formula structure
     */
    private function validateExpressionFormula(array $formula, string $prefix): array
    {
        $errors = [];

        if (!isset($formula['expression']) || !is_string($formula['expression']) || trim($formula['expression']) === '') {
            $errors[] = "$prefix: 'expression' must be a non-empty string";
        }

        if (!isset($formula['inputs']) || !is_array($formula['inputs'])) {
            $errors[] = "$prefix: 'inputs' must be an array";
        } elseif (empty($formula['inputs']) && isset($formula['expression']) && $formula['expression'] !== '0') {
            // Allow empty inputs only for constant expressions like '0'
            $errors[] = "$prefix: 'inputs' cannot be empty for expression formula";
        }

        // Validate expression syntax
        if (isset($formula['expression']) && is_string($formula['expression']) && trim($formula['expression']) !== '') {
            try {
                $this->validateExpressionSyntax($formula['expression']);
            } catch (Exception $e) {
                $errors[] = "$prefix: Invalid expression syntax - " . $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Validate switch formula structure
     */
    private function validateSwitchFormula(array $formula, string $prefix): array
    {
        $errors = [];

        if (!isset($formula['switch_on']) || !is_string($formula['switch_on'])) {
            $errors[] = "$prefix: 'switch_on' must be a string";
        }

        if (!isset($formula['cases']) || !is_array($formula['cases'])) {
            $errors[] = "$prefix: 'cases' must be an array";
        } elseif (empty($formula['cases'])) {
            $errors[] = "$prefix: 'cases' cannot be empty for switch formula";
        } else {
            foreach ($formula['cases'] as $caseIndex => $case) {
                $caseErrors = $this->validateCase($case, "$prefix.cases[$caseIndex]");
                $errors = array_merge($errors, $caseErrors);
            }
        }

        return $errors;
    }

    /**
     * Validate case structure
     */
    private function validateCase(array $case, string $prefix): array
    {
        $errors = [];

        if (!isset($case['condition'])) {
            $errors[] = "$prefix: Missing 'condition'";
        } else {
            $conditionErrors = $this->validateCondition($case['condition'], "$prefix.condition");
            $errors = array_merge($errors, $conditionErrors);
        }

        if (!isset($case['result'])) {
            $errors[] = "$prefix: Missing 'result'";
        }

        return $errors;
    }

    /**
     * Validate condition structure
     */
    private function validateCondition(array $condition, string $prefix): array
    {
        $errors = [];

        if (!isset($condition['operator'])) {
            $errors[] = "$prefix: Missing 'operator'";
        } elseif (!in_array($condition['operator'], ['<', '<=', '>', '>=', '==', '!=', 'between', 'in'], true)) {
            $errors[] = "$prefix: Invalid operator '{$condition['operator']}'";
        }

        if (!isset($condition['value'])) {
            $errors[] = "$prefix: Missing 'value'";
        } elseif ($condition['operator'] === 'between') {
            if (!is_array($condition['value']) || count($condition['value']) !== 2) {
                $errors[] = "$prefix: 'between' operator requires array with exactly 2 values";
            } elseif (!is_numeric($condition['value'][0]) || !is_numeric($condition['value'][1])) {
                $errors[] = "$prefix: 'between' values must be numeric";
            } elseif ($condition['value'][0] > $condition['value'][1]) {
                $errors[] = "$prefix: 'between' first value must be <= second value";
            }
        } elseif ($condition['operator'] === 'in') {
            if (!is_array($condition['value'])) {
                $errors[] = "$prefix: 'in' operator requires array value";
            }
        } elseif (in_array($condition['operator'], ['==', '!='], true)) {
            // Allow any type for equality operators
        } elseif (!is_numeric($condition['value'])) {
            $errors[] = "$prefix: 'value' must be numeric for operator '{$condition['operator']}'";
        }

        return $errors;
    }

    /**
     * Validate weight_score structure
     */
    private function validateWeightScore(array $weightScore, string $prefix): array
    {
        $errors = [];

        // Check for multi_condition
        if (isset($weightScore['multi_condition'])) {
            $multiErrors = $this->validateMultiCondition($weightScore['multi_condition'], "$prefix.weight_score.multi_condition");
            $errors = array_merge($errors, $multiErrors);
        }
        // Check for ranges
        elseif (isset($weightScore['ranges'])) {
            if (!is_array($weightScore['ranges']) || empty($weightScore['ranges'])) {
                $errors[] = "$prefix.weight_score: 'ranges' must be a non-empty array";
            } else {
                foreach ($weightScore['ranges'] as $index => $range) {
                    $rangeErrors = $this->validateRange($range, "$prefix.weight_score.ranges[$index]");
                    $errors = array_merge($errors, $rangeErrors);
                }
            }
        }
        // Original format
        else {
            if (!isset($weightScore['condition'])) {
                $errors[] = "$prefix.weight_score: Missing 'condition'";
            } else {
                $conditionErrors = $this->validateCondition($weightScore['condition'], "$prefix.weight_score.condition");
                $errors = array_merge($errors, $conditionErrors);
            }

            if (!isset($weightScore['score'])) {
                $errors[] = "$prefix.weight_score: Missing 'score'";
            } elseif (!is_numeric($weightScore['score'])) {
                $errors[] = "$prefix.weight_score: 'score' must be numeric";
            }
        }

        return $errors;
    }
    
    /**
     * Validate multi_condition structure
     */
    private function validateMultiCondition(array $multiCondition, string $prefix): array
    {
        $errors = [];
        
        if (!isset($multiCondition['variables']) || !is_array($multiCondition['variables'])) {
            $errors[] = "$prefix: 'variables' must be an array";
        } elseif (empty($multiCondition['variables'])) {
            $errors[] = "$prefix: 'variables' cannot be empty";
        }
        
        if (!isset($multiCondition['score_matrix']) || !is_array($multiCondition['score_matrix'])) {
            $errors[] = "$prefix: 'score_matrix' must be an array";
        } elseif (empty($multiCondition['score_matrix'])) {
            $errors[] = "$prefix: 'score_matrix' cannot be empty";
        }
        
        return $errors;
    }
    
    /**
     * Validate range structure
     */
    private function validateRange(array $range, string $prefix): array
    {
        $errors = [];
        
        if (!isset($range['condition'])) {
            $errors[] = "$prefix: Missing 'condition'";
        } else {
            $conditionErrors = $this->validateCondition($range['condition'], "$prefix.condition");
            $errors = array_merge($errors, $conditionErrors);
        }
        
        if (!isset($range['score'])) {
            $errors[] = "$prefix: Missing 'score'";
        } elseif (!is_numeric($range['score'])) {
            $errors[] = "$prefix: 'score' must be numeric";
        }
        
        return $errors;
    }
    
    /**
     * Validate score_rules structure
     */
    private function validateScoreRules(array $scoreRules, string $prefix): array
    {
        $errors = [];
        
        if (empty($scoreRules)) {
            $errors[] = "$prefix.score_rules: cannot be empty";
            return $errors;
        }
        
        foreach ($scoreRules as $index => $rule) {
            $ruleErrors = $this->validateScoreRule($rule, "$prefix.score_rules[$index]");
            $errors = array_merge($errors, $ruleErrors);
        }
        
        return $errors;
    }
    
    /**
     * Validate individual score rule structure
     */
    private function validateScoreRule(array $rule, string $prefix): array
    {
        $errors = [];
        
        if (!isset($rule['variable'])) {
            $errors[] = "$prefix: Missing 'variable'";
        } elseif (!is_string($rule['variable'])) {
            $errors[] = "$prefix: 'variable' must be a string";
        }
        
        // Must have either 'ranges' or 'condition'
        if (!isset($rule['ranges']) && !isset($rule['condition'])) {
            $errors[] = "$prefix: Must have either 'ranges' or 'condition'";
        }
        
        if (isset($rule['ranges'])) {
            if (!is_array($rule['ranges']) || empty($rule['ranges'])) {
                $errors[] = "$prefix: 'ranges' must be a non-empty array";
            } else {
                foreach ($rule['ranges'] as $index => $range) {
                    $rangeErrors = $this->validateRange($range, "$prefix.ranges[$index]");
                    $errors = array_merge($errors, $rangeErrors);
                }
            }
        }
        
        if (isset($rule['condition'])) {
            $conditionErrors = $this->validateCondition($rule['condition'], "$prefix.condition");
            $errors = array_merge($errors, $conditionErrors);
            
            if (!isset($rule['score'])) {
                $errors[] = "$prefix: Missing 'score' when using 'condition'";
            } elseif (!is_numeric($rule['score'])) {
                $errors[] = "$prefix: 'score' must be numeric";
            }
        }
        
        return $errors;
    }

    /**
     * Validate expression syntax without executing
     */
    private function validateExpressionSyntax(string $expr): void
    {
        // Check for allowed characters including function names, variables, numbers, operators
        if (!preg_match('/^[a-zA-Z_0-9\+\-\*\/\(\)\s\.\,\*]+$/', $expr)) {
            throw new Exception("Expression contains invalid characters");
        }

        // Check for balanced parentheses
        $openCount = substr_count($expr, '(');
        $closeCount = substr_count($expr, ')');
        if ($openCount !== $closeCount) {
            throw new Exception("Unbalanced parentheses");
        }

        // Check for valid function calls
        foreach ($this->allowedFunctions as $func) {
            if (strpos($expr, $func) !== false) {
                $pattern = '/\b' . $func . '\s*\([^)]*\)/';
                if (!preg_match($pattern, $expr)) {
                    throw new Exception("Invalid function call: $func");
                }
            }
        }
    }

    /**
     * Check for circular dependencies in formulas
     */
    private function checkCircularDependencies(array $formulas): array
    {
        $errors = [];
        $dependencies = [];
        $outputs = [];

        // Build dependency graph
        foreach ($formulas as $formula) {
            $id = $formula['id'];
            $storeAs = $formula['store_as'] ?? $id;
            $outputs[$id] = $storeAs;

            if (isset($formula['inputs'])) {
                $dependencies[$id] = $formula['inputs'];
            } elseif (isset($formula['switch_on'])) {
                $dependencies[$id] = [$formula['switch_on']];
            }
        }

        // Check for circular dependencies using DFS
        foreach ($dependencies as $formulaId => $deps) {
            if ($this->hasCircularDependency($formulaId, $deps, $dependencies, $outputs, [])) {
                $errors[] = "Circular dependency detected involving formula '$formulaId'";
            }
        }

        return $errors;
    }

    /**
     * Helper method to detect circular dependencies
     */
    private function hasCircularDependency(
        string $currentId,
        array $dependencies,
        array $allDependencies,
        array $outputs,
        array $visited
    ): bool {
        if (in_array($currentId, $visited, true)) {
            return true;
        }

        $visited[] = $currentId;

        foreach ($dependencies as $dep) {
            // Find which formula produces this dependency
            $producingFormula = null;
            foreach ($outputs as $formulaId => $output) {
                if ($output === $dep) {
                    $producingFormula = $formulaId;
                    break;
                }
            }

            if ($producingFormula && isset($allDependencies[$producingFormula])) {
                if ($this->hasCircularDependency(
                    $producingFormula,
                    $allDependencies[$producingFormula],
                    $allDependencies,
                    $outputs,
                    $visited
                )) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check for potential warnings in configuration
     */
    private function checkForWarnings(array $config): array
    {
        $warnings = [];

        foreach ($config['formulas'] as $index => $formula) {
            $id = $formula['id'] ?? "Formula[$index]";

            // Check for unused stored values
            if (isset($formula['store_as'])) {
                $storeAs = $formula['store_as'];
                $isUsed = false;

                foreach ($config['formulas'] as $otherFormula) {
                    if (isset($otherFormula['inputs']) && in_array($storeAs, $otherFormula['inputs'], true)) {
                        $isUsed = true;
                        break;
                    }
                    if (isset($otherFormula['switch_on']) && $otherFormula['switch_on'] === $storeAs) {
                        $isUsed = true;
                        break;
                    }
                }

                if (!$isUsed) {
                    $warnings[] = "Formula '$id' stores value as '$storeAs' but it's never used";
                }
            }

            // Check for potential division by zero
            if (isset($formula['expression']) && strpos($formula['expression'], '/') !== false) {
                $warnings[] = "Formula '$id' contains division - ensure no division by zero";
            }

            // Check for overlapping switch cases
            if (isset($formula['cases'])) {
                $warnings = array_merge($warnings, $this->checkOverlappingCases($formula, $id));
            }
        }

        return $warnings;
    }

    /**
     * Check for overlapping switch cases
     */
    private function checkOverlappingCases(array $formula, string $formulaId): array
    {
        $warnings = [];
        $cases = $formula['cases'];

        for ($i = 0; $i < count($cases) - 1; $i++) {
            for ($j = $i + 1; $j < count($cases); $j++) {
                if ($this->casesOverlap($cases[$i]['condition'], $cases[$j]['condition'])) {
                    $warnings[] = "Formula '$formulaId' has overlapping cases at positions $i and $j";
                }
            }
        }

        return $warnings;
    }

    /**
     * Check if two conditions overlap
     */
    private function casesOverlap(array $cond1, array $cond2): bool
    {
        // Simple overlap detection for between operators
        if ($cond1['operator'] === 'between' && $cond2['operator'] === 'between') {
            $range1 = $cond1['value'];
            $range2 = $cond2['value'];
            return !($range1[1] < $range2[0] || $range2[1] < $range1[0]);
        }

        return false; // More complex overlap detection can be added
    }
}