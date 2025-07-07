<?php

declare(strict_types=1);

/**
 * Process all types of formulas
 */
class FormulaProcessor
{
    private ExpressionEvaluator $evaluator;

    public function __construct(ExpressionEvaluator $evaluator)
    {
        $this->evaluator = $evaluator;
    }

    /**
     * Process all formulas in configuration
     */
    public function processAll(array $config, array $inputs): array
    {
        $context = $inputs;

        foreach ($config['formulas'] as $formula) {
            $this->processFormula($formula, $context);
        }

        return $context;
    }

    /**
     * Process a single formula
     */
    public function processFormula(array $formula, array &$context): void
    {
        try {
            if (isset($formula['formula'])) {
                $this->processExpressionFormula($formula, $context);
            } elseif (isset($formula['switch'])) {
                $this->processSwitchFormula($formula, $context);
            } elseif (isset($formula['scoring'])) {
                $this->processWeightScore($formula, $context);
            } elseif (isset($formula['rules'])) {
                $this->processAccumulativeScore($formula, $context);
            }
        } catch (Exception $e) {
            throw new RuleFlowException(
                "Error processing formula '{$formula['id']}': " . $e->getMessage(),
                [
                    'formula_id' => $formula['id'],
                    'available_context' => array_keys($context),
                    'formula_type' => $this->getFormulaType($formula)
                ]
            );
        }
    }

    /**
     * Process expression-based formula with $ notation support
     */
    private function processExpressionFormula(array $formula, array &$context): void
    {
        $vars = [];
        
        if (!empty($formula['inputs'])) {
            foreach ($formula['inputs'] as $key) {
                $contextKey = $this->normalizeVariableName($key);
                
                if (isset($context[$contextKey])) {
                    $vars[$key] = $context[$contextKey];
                } elseif (isset($context[$key])) {
                    $vars[$key] = $context[$key];
                } else {
                    if (substr($key, 0, 1) === '$') {
                        $actualKey = substr($key, 1);
                        if (isset($context[$actualKey])) {
                            $vars[$key] = $context[$actualKey];
                        } else {
                            throw new RuleFlowException("Missing input: {$key}", [
                                'missing_input' => $key,
                                'normalized_key' => $contextKey,
                                'actual_key' => $actualKey
                            ]);
                        }
                    } else {
                        throw new RuleFlowException("Missing input: {$key}", [
                            'missing_input' => $key,
                            'normalized_key' => $contextKey
                        ]);
                    }
                }
            }
        }

        $result = $this->evaluator->safeEval($formula['formula'], $vars);
        $storeKey = isset($formula['as']) ? 
            $this->normalizeVariableName($formula['as']) : $formula['id'];
        $context[$storeKey] = $result;
    }

    /**
     * Process switch/case formula
     */
    private function processSwitchFormula(array $formula, array &$context): void
    {
        $switchVar = $this->normalizeVariableName($formula['switch']);
        $switchValue = $context[$switchVar] ?? null;

        if ($switchValue === null) {
            throw new RuleFlowException("Switch value '{$formula['switch']}' not found", [
                'switch_variable' => $formula['switch'],
                'normalized_key' => $switchVar
            ]);
        }

        $matched = false;
        foreach ($formula['when'] as $case) {
            if ($this->evaluateCondition($switchValue, $case['if'], $context)) {
                $context[$formula['id']] = $case['result'];
                
                if (isset($case['set_vars'])) {
                    $this->processSetVarsRuntime($case['set_vars'], $context);
                }
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            $context[$formula['id']] = $formula['default'] ?? null;
            
            if (isset($formula['default_vars'])) {
                $this->processSetVarsRuntime($formula['default_vars'], $context);
            }
        }
    }

    /**
     * Process weight/score calculation with enhanced features
     */
    private function processWeightScore(array $formula, array &$context): void
    {
        $weightScore = $formula['scoring'];
        
        if (isset($weightScore['ranges'])) {
            $this->processRangeBasedScoring($formula, $context);
        } elseif (isset($weightScore['ifs'])) {
            $this->processMultiConditionScoring($formula, $context);
        } else {
            $this->processSimpleWeightScore($formula, $context);
        }
    }

    /**
     * Process range-based scoring
     */
    private function processRangeBasedScoring(array $formula, array &$context): void
    {
        $storeAs = isset($formula['as']) ? 
            $this->normalizeVariableName($formula['as']) : $formula['id'];
        $value = $context[$storeAs] ?? null;
        
        if ($value === null) {
            $context[$formula['id']] = 0;
            return;
        }
        
        foreach ($formula['scoring']['ranges'] as $range) {
            if ($this->evaluateCondition($value, $range['if'], $context)) {
                $context[$formula['id']] = $range['score'];
                
                if (isset($range['set_vars'])) {
                    $this->processSetVarsRuntime($range['set_vars'], $context);
                }
                return;
            }
        }
        
        $context[$formula['id']] = $formula['scoring']['default'] ?? 0;
    }

    /**
     * Process multi-condition scoring
     */
    private function processMultiConditionScoring(array $formula, array &$context): void
    {
        $result = $this->evaluateMultiConditionScore($formula['scoring']['ifs'], $context);
        $context[$formula['id']] = $result['score'] ?? 0;
        
        if (is_array($result)) {
            foreach ($result as $key => $value) {
                if ($key !== 'score') {
                    $context[$formula['id'] . '_' . $key] = $value;
                }
            }
        }
    }

    /**
     * Process simple weight score
     */
    private function processSimpleWeightScore(array $formula, array &$context): void
    {
        $storeAs = isset($formula['as']) ? 
            $this->normalizeVariableName($formula['as']) : $formula['id'];
        $value = $context[$storeAs] ?? null;
        
        if ($value === null) {
            $context[$formula['id']] = 0;
            return;
        }
        
        if ($this->evaluateCondition($value, $formula['scoring']['if'], $context)) {
            $context[$formula['id']] = $formula['scoring']['score'];
        } else {
            $context[$formula['id']] = 0;
        }
    }

    /**
     * Evaluate multi-dimensional condition scoring
     */
    private function evaluateMultiConditionScore(array $multiCondition, array &$context): array
    {
        $variables = $multiCondition['vars'];
        $matrix = $multiCondition['tree'];
        
        $values = [];
        foreach ($variables as $var) {
            $varKey = $this->normalizeVariableName($var);
            $value = $context[$varKey] ?? null;
            if ($value === null) {
                return ['score' => 0];
            }
            $values[] = $value;
        }
        
        $result = $this->navigateMatrix($matrix, $values, 0, $context);
        
        return is_array($result) ? $result : ['score' => 0];
    }

    /**
     * Recursively navigate through multi-dimensional scoring matrix
     */
    private function navigateMatrix(array $currentLevel, array $values, int $depth, array &$context): array
    {
        if ($depth >= count($values)) {
            return $currentLevel;
        }
        
        $currentValue = $values[$depth];
        
        foreach ($currentLevel as $item) {
            if (isset($item['if']) && $this->evaluateCondition($currentValue, $item['if'], $context)) {
                if (isset($item['set_vars'])) {
                    $this->processSetVarsRuntime($item['set_vars'], $context);
                }
                
                if (isset($item['ranges'])) {
                    return $this->navigateMatrix($item['ranges'], $values, $depth + 1, $context);
                }
                
                return $item;
            }
        }
        
        return ['score' => 0];
    }

    /**
     * Process accumulative scoring formula
     */
    private function processAccumulativeScore(array $formula, array &$context): void
    {
        $score = 0;
        $scoreKey = $formula['id'];
        
        if (isset($context[$scoreKey])) {
            $score = $context[$scoreKey];
        }
        
        foreach ($formula['rules'] as $rule) {
            $ruleScore = $this->evaluateScoreRule($rule, $context);
            $score += $ruleScore;
            
            if (isset($rule['set_vars'])) {
                if ($ruleScore > 0 || !isset($rule['only_if_scored']) || !$rule['only_if_scored']) {
                    $this->processSetVarsRuntime($rule['set_vars'], $context);
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
        $variable = $this->normalizeVariableName($rule['var']);
        $value = $context[$variable] ?? null;
        
        if ($value === null) {
            return 0;
        }
        
        if (isset($rule['ranges'])) {
            foreach ($rule['ranges'] as $range) {
                if ($this->evaluateCondition($value, $range['if'], $context)) {
                    return $range['score'];
                }
            }
        } elseif (isset($rule['if'])) {
            if ($this->evaluateCondition($value, $rule['if'], $context)) {
                return $rule['score'];
            }
        }
        
        return 0;
    }

    /**
     * Process set_vars at runtime
     */
    private function processSetVarsRuntime(array $setVars, array &$context): void
    {
        // Step 1: แยก simple values กับ complex dependencies
        $simpleVars = [];
        $complexVars = [];
        $pending = [];
        
        foreach ($setVars as $varName => $varValue) {
            $contextKey = $this->normalizeVariableName($varName);
            
            if (is_string($varValue) && ($this->isDollarReference($varValue) || $this->isDollarExpression($varValue))) {
                $complexVars[$contextKey] = $varValue;
                $pending[] = $contextKey;
            } else {
                // Simple values - ประมวลผลทันทีพร้อม type conversion
                $convertedValue = $this->convertValueType($varValue);
                $simpleVars[$contextKey] = $convertedValue;
                $context[$contextKey] = $convertedValue;
            }
        }
        
        // Step 2: Resolve complex dependencies iteratively
        $maxIterations = count($complexVars) * 2; // ป้องกัน infinite loop
        $iteration = 0;
        
        while (!empty($pending) && $iteration < $maxIterations) {
            $iteration++;
            $progressMade = false;
            
            foreach ($pending as $index => $contextKey) {
                $varValue = $complexVars[$contextKey];
                
                try {
                    if ($this->isDollarReference($varValue)) {
                        // Simple reference: $var1 = $var2
                        $refKey = $this->normalizeVariableName($varValue);
                        if (isset($context[$refKey])) {
                            $context[$contextKey] = $context[$refKey];
                            unset($pending[$index]);
                            $progressMade = true;
                        }
                    } elseif ($this->isDollarExpression($varValue)) {
                        // Complex expression: $var1 = $var2 + $var3
                        if ($this->canEvaluateExpression($varValue, $context)) {
                            $result = $this->evaluateExpression($varValue, $context);
                            $context[$contextKey] = $result;
                            unset($pending[$index]);
                            $progressMade = true;
                        }
                    }
                } catch (Exception $e) {
                    // Skip this iteration, may resolve later
                    continue;
                }
            }
            
            // Reset array keys after unset
            $pending = array_values($pending);
            
            if (!$progressMade) {
                // No progress made, check for circular dependencies
                if (!empty($pending)) {
                    $pendingVars = [];
                    foreach ($pending as $index) {
                        $pendingVars[] = array_search($index, array_keys($complexVars));
                    }
                    throw new RuleFlowException(
                        "Cannot resolve set_vars dependencies. Possible circular reference or missing variables: " . 
                        implode(', ', $pendingVars),
                        ['pending_variables' => $pendingVars]
                    );
                }
                break;
            }
        }
        
        if (!empty($pending)) {
            throw new RuleFlowException("Maximum iterations exceeded while resolving set_vars dependencies");
        }
    }


    /**
     * Convert value to proper type
     */
    private function convertValueType($value)
{
    // Keep non-string values as-is, but ensure numbers are proper type
    if (!is_string($value)) {
        if (is_numeric($value)) {
            // Ensure numeric values are proper type
            if (is_float($value) || strpos((string)$value, '.') !== false) {
                return (float)$value;
            } else {
                return (int)$value;
            }
        }
        return $value;
    }
    
    // ❌ OLD BUGGY CODE:
    // if (empty($value)) {
    //     return $value; // BUG: empty('0') = true!
    // }
    
    // ✅ NEW FIXED CODE: Check for truly empty strings only
    if ($value === '' || $value === null) {
        return $value;
    }
    
    // Try to convert numeric strings to numbers
    if (is_numeric($value)) {
        // Check if it's an integer
        if (ctype_digit($value) || (substr($value, 0, 1) === '-' && ctype_digit(substr($value, 1)))) {
            $intValue = (int)$value;
            // Return int if it fits exactly, otherwise return float
            if ((string)$intValue === $value) {
                return $intValue;
            }
        }
        
        // Convert to float for decimal numbers
        return (float)$value;
    }
    
    // Handle boolean-like strings
    $lowerValue = strtolower(trim($value));
    if (in_array($lowerValue, ['true', '1', 'yes', 'on'])) {
        return true;
    }
    if (in_array($lowerValue, ['false', '0', 'no', 'off'])) {
        return false;
    }
    
    // Return string as-is for non-convertible values
    return $value;
}

    /**
     * Enhanced condition evaluation with $ notation support
     */
    private function evaluateCondition($value, array $condition, array $context): bool
    {
        $operator = $condition['op'];
        $condValue = $condition['value'];
        
        // Handle $ references in condition values
        if (is_string($condValue) && $this->isDollarReference($condValue)) {
            $varKey = $this->normalizeVariableName($condValue);
            $condValue = $context[$varKey] ?? null;
        } elseif (is_array($condValue)) {
            $condValue = array_map(function($item) use ($context) {
                if (is_string($item) && $this->isDollarReference($item)) {
                    $varKey = $this->normalizeVariableName($item);
                    return $context[$varKey] ?? null;
                }
                return $item;
            }, $condValue);
        }

        return match ($operator) {
            '<' => $value < $condValue,
            '<=' => $value <= $condValue,
            '>' => $value > $condValue,
            '>=' => $value >= $condValue,
            '==' => $value == $condValue,
            '!=' => $value != $condValue,
            'between' => is_array($condValue) && count($condValue) === 2 && 
                        $value >= $condValue[0] && $value <= $condValue[1],
            'in' => in_array($value, (array)$condValue, true),
            default => throw new RuleFlowException("Unsupported operator: {$operator}")
        };
    }

    /**
     * Helper methods
     */
    private function normalizeVariableName(string $varName): string
    {
        return substr($varName, 0, 1) === '$' ? substr($varName, 1) : $varName;
    }

    private function canEvaluateExpression(string $expression, array $context): bool
    {
        // Extract all $variables from expression
        preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $expression, $matches);
        
        if (empty($matches[1])) {
            return true; // No variables needed
        }
        
        // Check if all required variables are available
        foreach ($matches[1] as $varName) {
            if (!isset($context[$varName])) {
                return false;
            }
        }
        
        return true;
    }

    private function evaluateExpression(string $expression, array $context): float
    {
        return $this->evaluator->evaluateDollarExpression($expression, $context);
    }


    private function isDollarReference(string $value): bool
    {
        return preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', trim($value)) === 1;
    }

    private function isDollarExpression(string $value): bool
    {
        $trimmed = trim($value);
        // ต้องมี $ variable และมี operators หรือไม่ใช่ simple reference
        return preg_match('/\$[a-zA-Z_][a-zA-Z0-9_]*/', $trimmed) && 
               !$this->isDollarReference($trimmed) &&
               (preg_match('/[\+\-\*\/\(\)\s]/', $trimmed) || 
                preg_match('/\$[a-zA-Z_][a-zA-Z0-9_]*.*\$[a-zA-Z_][a-zA-Z0-9_]*/', $trimmed));
    }
    

    private function getFormulaType(array $formula): string
    {
        if (isset($formula['formula'])) return 'expression';
        if (isset($formula['switch'])) return 'switch';
        if (isset($formula['scoring'])) return 'scoring';
        if (isset($formula['rules'])) return 'rules';
        return 'unknown';
    }
}