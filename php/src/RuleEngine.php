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
    }

    /**
     * Process expression-based formula
     */
    private function processExpressionFormula(array $formula, array &$context): void
    {
        try {
            // Prepare variables for expression
            $vars = [];
            foreach ($formula['inputs'] as $key) {
                if (!isset($context[$key])) {
                    throw new Exception("Missing input: {$key}");
                }
                $vars[$key] = $context[$key];
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
                $matched = true;
                break;
            }
        }

        // Handle default value
        if (!$matched) {
            $context[$formula['id']] = $formula['default'] ?? null;
        }
    }

    /**
     * Process weight/score calculation
     */
    private function processWeightScore(array $formula, array &$context): void
    {
        $value = $context[$formula['store_as'] ?? $formula['id']] ?? null;

        if ($value !== null && $this->evaluateCondition($value, $formula['weight_score']['condition'])) {
            $context[$formula['id'] . '_score'] = $formula['weight_score']['score'];
        } else {
            $context[$formula['id'] . '_score'] = 0;
        }
    }

    /**
     * Evaluate condition based on operator and value
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
            default => throw new Exception("Unsupported operator: {$operator}")
        };
    }

    /**
     * Safely evaluate mathematical expressions
     */
    private function safeEval(string $expr, array $vars): float
    {
        // Process functions first
        $expr = $this->processFunctions($expr, $vars);

        // Validate expression
        $this->validateExpression($expr);

        // Replace variables with values
        $expr = $this->replaceVariables($expr, $vars);

        // Validate final expression
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
            $pattern = '/\b' . $func . '\s*\(([^)]+)\)/';
            while (preg_match($pattern, $expr, $matches)) {
                $args = array_map('trim', explode(',', $matches[1]));
                $result = $this->callFunction($func, $args, $vars);
                $expr = str_replace($matches[0], (string)$result, $expr);
            }
        }

        return $expr;
    }

    /**
     * Call allowed function with arguments
     */
    private function callFunction(string $funcName, array $args, array $vars): float
    {
        // Evaluate arguments
        $evaluatedArgs = [];
        foreach ($args as $arg) {
            if (is_numeric($arg)) {
                $evaluatedArgs[] = (float)$arg;
            } elseif (isset($vars[$arg])) {
                $evaluatedArgs[] = $vars[$arg];
            } else {
                throw new Exception("Unknown variable in function: {$arg}");
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
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*|[0-9]+\.?[0-9]*|[\+\-\*\/\(\)\s\*\*]+$/', $expr)) {
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
        $cleanExpr = str_replace('**', '^', $expr);
        if (!preg_match('/^[0-9+\-*\/\(\)\s\.]+$/', $cleanExpr)) {
            throw new Exception("Expression contains unresolved variables or invalid characters");
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

        // Check that formula has at least one action
        if (!isset($formula['expression']) && !isset($formula['switch_on'])) {
            $errors[] = "$prefix: Formula must have either 'expression' or 'switch_on'";
        }

        return $errors;
    }

    /**
     * Validate expression formula structure
     */
    private function validateExpressionFormula(array $formula, string $prefix): array
    {
        $errors = [];

        if (!is_string($formula['expression']) || empty($formula['expression'])) {
            $errors[] = "$prefix: 'expression' must be a non-empty string";
        }

        if (!isset($formula['inputs']) || !is_array($formula['inputs'])) {
            $errors[] = "$prefix: 'inputs' must be an array";
        } elseif (empty($formula['inputs'])) {
            $errors[] = "$prefix: 'inputs' cannot be empty for expression formula";
        }

        // Validate expression syntax
        if (isset($formula['expression']) && is_string($formula['expression'])) {
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
        } elseif (!in_array($condition['operator'], ['<', '<=', '>', '>=', '==', '!=', 'between'], true)) {
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
        } elseif (!is_numeric($condition['value'])) {
            $errors[] = "$prefix: 'value' must be numeric";
        }

        return $errors;
    }

    /**
     * Validate weight_score structure
     */
    private function validateWeightScore(array $weightScore, string $prefix): array
    {
        $errors = [];

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

        return $errors;
    }

    /**
     * Validate expression syntax without executing
     */
    private function validateExpressionSyntax(string $expr): void
    {
        // Check for allowed characters
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*|[0-9]+\.?[0-9]*|[\+\-\*\/\(\)\s\*\*,]+$/', $expr)) {
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
            $id = $formula['id'];

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
