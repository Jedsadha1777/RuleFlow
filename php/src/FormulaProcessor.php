<?php

declare(strict_types=1);
require_once __DIR__ . '/RuleFlowHelper.php';


/**
 * Enhanced FormulaProcessor with function_call support
 * Process all types of formulas including direct function calls
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
     * Enhanced processFormula with function_call support
     */
    public function processFormula(array $formula, array &$context): void
    {
        $formulaId = $formula['id'];

        try {
            $result = null;
            
            if (isset($formula['function_call'])) {
                $result = $this->processFunctionCall($formula, $context);
            }
            elseif (isset($formula['switch'])) {
                $switchResult = $this->processSwitch($formula, $context);
                $result = $switchResult['result'];
                $context = $switchResult['context'];
            }
            elseif (isset($formula['formula'])) {
                $result = $this->processExpressionFormula($formula, $context);
            }
            elseif (isset($formula['rules'])) {
                $result = $this->processAccumulativeScore($formula, $context);
            }
            elseif (isset($formula['scoring'])) {
                $result = $this->processWeightScore($formula, $context);
            }
            else {
                throw new RuleFlowException("Invalid formula structure for '$formulaId'");
            }
            
            // Handle result storage
            $this->storeFormulaResult($formulaId, $formula, $result, $context);
            
        } catch (Exception $e) {
            throw new RuleFlowException(
                "Error processing formula '$formulaId': " . $e->getMessage(),
                [
                    'formula_id' => $formulaId, 
                    'formula' => $formula,
                    'available_context' => array_keys($context)
                ]
            );
        }
    }
    
    /**
     * Process direct function call
     */
    private function processFunctionCall(array $formula, array $context): mixed
    {
        $functionName = $formula['function_call'];
        $params = $formula['params'] ?? [];
        
        // Resolve parameter values from context
        $args = [];
        foreach ($params as $param) {
            $args[] = $this->resolveParameter($param, $context);
        }
        
        // Call function through FunctionRegistry
        try {
            $registry = $this->evaluator->getFunctionRegistry();
            return $registry->call($functionName, $args);
        } catch (Exception $e) {
            throw new RuleFlowException("Function '$functionName' call failed: " . $e->getMessage());
        }
    }

    /**
     * Store formula result with proper handling of complex results
     * CLEAR RESPONSIBILITY: Only result storage logic
     */
    private function storeFormulaResult(string $formulaId, array $formula, $result, array &$context): void
    {
        $variableName = $formula['as'] ?? $formulaId;
        $storeKey = RuleFlowHelper::normalizeVariableName($variableName);
        
        if (is_array($result) && isset($result['score'])) {
            // Multi-dimensional scoring result
            $context[$storeKey] = $result['score'];
            
            // Store additional properties with formula prefix
            foreach ($result as $key => $value) {
                if ($key === 'score') continue;
                
                if ($key === 'set_vars' && is_array($value)) {
                    $this->processSetVars($value, $context);
                } else {
                    
                    $context["{$formulaId}_{$key}"] = $value;
                                    
                    // เพิ่มการสร้างตาม 'as' ด้วย
                    if (isset($formula['as'])) {
                        $asVarName = RuleFlowHelper::normalizeVariableName($formula['as']);
                        $context["{$asVarName}_{$key}"] = $value;
                    }
                }
            }
        } else {
            // Simple result
            $context[$storeKey] = $result;
        }
    }
    
    /**
     *  Resolve parameter value from context or use literal
     */
    private function resolveParameter($param, array $context): mixed
    {
        // If it's a string and exists in context, use context value
        if (is_string($param)) {
            // Remove $ prefix if present
            $paramName = ltrim($param, '$');
            
            // Check context with and without $ prefix
            if (isset($context[$paramName])) {
                return $context[$paramName];
            } elseif (isset($context['$' . $paramName])) {
                return $context['$' . $paramName];
            }
        }
        
        // Use literal value (number, string, boolean)
        return $param;
    }

    /**
     * Process expression-based formula with $ notation support
     */
    private function processExpressionFormula(array $formula, array $context): mixed
    {
        $vars = [];

        if (!empty($formula['inputs'])) {
            foreach ($formula['inputs'] as $key) {
                $contextKey = RuleFlowHelper::normalizeVariableName($key);
                
                // 🔧 FIX: ลองหาใน context หลายแบบ
                if (isset($context[$contextKey])) {
                    $vars[$key] = $context[$contextKey];
                    $vars[$contextKey] = $context[$contextKey];
                } elseif (isset($context[$key])) {
                    $vars[$key] = $context[$key];
                } elseif (substr($key, 0, 1) === '$') {
                    $actualKey = substr($key, 1);
                    if (isset($context[$actualKey])) {
                        $vars[$key] = $context[$actualKey];
                    } else {
                        throw new RuleFlowException("Missing input: {$key} (checked: {$contextKey}, {$key}, {$actualKey})");
                    }
                } else {
                    throw new RuleFlowException("Missing input: {$key} (available: " . implode(', ', array_keys($context)) . ")");
                }
            }
        }

        return $this->evaluator->safeEval($formula['formula'], $vars);
    }

    /**
     * 🔧 Enhanced processSwitch with function_call support
     */
    private function processSwitch(array $formula, array $context): mixed
    {
        $switchVar = RuleFlowHelper::normalizeVariableName($formula['switch']);
        $switchValue = $context[$switchVar] ?? null;

  
        
        if ($switchValue === null) {
            throw new RuleFlowException("Switch variable '{$formula['switch']}' not found in context");
        }
        
        // Check each condition
        foreach ($formula['when'] as $condition) {
            if ($this->evaluateCondition($condition['if'], $switchValue, $context)) {
                $result = null;
                
                //  Check if result is a function call
                if (isset($condition['function_call'])) {
                    $result = $this->processFunctionCall($condition, $context);
                } else {
                    $result = $this->evaluateResult($condition, $context);
                }
                
                // Handle set_vars if present
                if (isset($condition['set_vars'])) {
                    $this->processSetVars($condition['set_vars'], $context);
                }
                
                 return ['result' => $result, 'context' => $context];
            }
        }
        
        // Default case
        if (isset($formula['default'])) {
            $result = null;
            
            // Handle default set_vars
            if (isset($formula['default_vars'])) {
                $this->processSetVars($formula['default_vars'], $context);
            }
            
            // Check if default is a function call
            if (is_array($formula['default']) && isset($formula['default']['function_call'])) {
                $result = $this->processFunctionCall($formula['default'], $context);
            } else {
                $result = $this->evaluateResult(['result' => $formula['default']], $context);
            }
            
             return ['result' => $result, 'context' => $context];
        }
        
        throw new RuleFlowException("No matching condition found and no default specified");
    }

    /**
     * Process accumulative scoring
     */
    private function processAccumulativeScore(array $formula, array $context): float
    {
        $totalScore = 0.0;
    
        foreach ($formula['rules'] as $rule) {
            $varKey = RuleFlowHelper::normalizeVariableName($rule['var']);
            $value = $context[$varKey] ?? null;
            
            if ($value === null) {
                continue;
            }
            
            // Handle ranges
            if (isset($rule['ranges'])) {
                foreach ($rule['ranges'] as $range) {
                    if ($this->evaluateCondition($range['if'], $value, $context)) {
                        // FIX: Use 'result' instead of 'score'
                        $totalScore += $range['result'] ?? 0;
                        break;
                    }
                }
            }
            // Handle single condition
            elseif (isset($rule['if'])) {
                if ($this->evaluateCondition($rule['if'], $value, $context)) {
                    // FIX: Use 'result' instead of 'score'
                    $totalScore += $rule['result'] ?? 0;
                }
            }
        }
        
        return $totalScore;
    }

    /**
     * Process weighted scoring
     */
    private function processWeightScore(array $formula, array $context): mixed
    {
         if (isset($formula['scoring']['ifs'])) {
            // Multi-dimensional scoring
            return $this->processMultiConditionScoring($formula, $context);
        } elseif (isset($formula['scoring']['if'])) {
            // Simple scoring
            return $this->processSimpleWeightScore($formula, $context);
        }
        
        throw new RuleFlowException("Invalid scoring structure");
    }

    /**
     * Process multi-condition scoring
     */
    private function processMultiConditionScoring(array $formula, array $context): array
    {
        $result = $this->evaluateMultiConditionScore($formula['scoring']['ifs'], $context);
        return $result;
    }

    /**
     * Process simple weight score
     */
    private function processSimpleWeightScore(array $formula, array $context): float
    {
        $storeAs = isset($formula['as']) ? 
            RuleFlowHelper::normalizeVariableName($formula['as']) : $formula['id'];
        $value = $context[$storeAs] ?? null;
        
        if ($value === null) {
            return 0.0;
        }
        
        if ($this->evaluateCondition($formula['scoring']['if'], $value, $context)) {
            // FIX: Use 'result' instead of 'score' if available
            return (float)($formula['scoring']['result'] ?? $formula['scoring']['score'] ?? 0);
        }
        
        return 0.0;
    }

    /**
     * Evaluate multi-dimensional condition scoring
     */
    private function evaluateMultiConditionScore(array $multiCondition, array $context): array
    {
         $variables = $multiCondition['vars'];
        $matrix = $multiCondition['tree'];
        
        // Get input values
        $inputValues = $this->extractInputValues($variables, $context);
        if (empty($inputValues)) {
            return $this->createDefaultResult();
        }
        
        // Find matching rule
        $matchedRule = $this->findMatchingRule($matrix, $inputValues, $context);
        
        // Build complete result
        return $this->buildScoringResult($matchedRule);
    }

    /**
     * Extract input values for variables
     * CLEAR RESPONSIBILITY: Only handle input extraction
     */
    private function extractInputValues(array $variables, array $context): array
    {
        $values = [];
        foreach ($variables as $var) {
            $varKey = RuleFlowHelper::normalizeVariableName($var);
            $value = $context[$varKey] ?? null;
            if ($value === null) {
                return []; // Return empty if any variable is missing
            }
            $values[] = $value;
        }
        return $values;
    }

    /**
     * Find the matching rule in the multi-dimensional matrix
     * CLEAR RESPONSIBILITY: Only navigate and find the match
     */
    private function findMatchingRule(array $matrix, array $values, array $context): ?array
    {
        return $this->navigateMatrix($matrix, $values, 0, $context);
    }

    /**
     * Navigate scoring matrix - SIMPLIFIED AND FOCUSED
     * CLEAR RESPONSIBILITY: Only navigation logic
     */
    private function navigateMatrix(array $matrix, array $values, int $depth, array $context): ?array
    {
        // Base case: if we've processed all dimensions, return the matrix itself
        if ($depth >= count($values)) {
            return is_array($matrix) ? $matrix : null;
        }
        
        $currentValue = $values[$depth];
        
        // Find matching condition at current depth
        foreach ($matrix as $node) {
            if ($this->evaluateCondition($node['if'], $currentValue, $context)) {
                // If this is the last dimension, find the final match in ranges
                if ($depth === count($values) - 1) {
                    if (isset($node['ranges'])) {
                        // This is the final dimension, evaluate ranges
                        foreach ($node['ranges'] as $range) {
                            if ($this->evaluateCondition($range['if'], $values[$depth], $context)) {
                                return $range;
                            }
                        }
                    }
                    // No ranges or no match in ranges
                    return $node;
                } else {
                    // Continue to next dimension
                    if (isset($node['ranges'])) {
                        return $this->navigateMatrix($node['ranges'], $values, $depth + 1, $context);
                    }
                }
            }
        }
        
        return null; // No match found
    }

    /**
     * Build complete scoring result
     * CLEAR RESPONSIBILITY: Only result construction
     */
    private function buildScoringResult(?array $matchedRule): array
    {
        if ($matchedRule === null) {
            return $this->createDefaultResult();
        }
        
        $result = [
            'score' => $matchedRule['score'] ?? 0
        ];
        
        // Add all additional properties except structural ones
        $excludedKeys = ['if', 'score', 'ranges', 'children'];
        foreach ($matchedRule as $key => $value) {
            if (!in_array($key, $excludedKeys)) {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }

    /**
     * Create default result when no match is found
     */
    private function createDefaultResult(): array
    {
        return ['score' => 0];
    }


    

    /**
     * 🔧 Fixed evaluateCondition method with correct parameter order
     */
    private function evaluateCondition(array $condition, $switchValue, array $context): bool
    {
        // 🆕 Handle nested logical groups - AND operation
        if (isset($condition['and'])) {
            foreach ($condition['and'] as $subCondition) {
                if (!$this->evaluateCondition($subCondition, $switchValue, $context)) {
                    return false; // All conditions must be true for AND
                }
            }
            return true;
        }
        
        // 🆕 Handle nested logical groups - OR operation  
        if (isset($condition['or'])) {
            foreach ($condition['or'] as $subCondition) {
                if ($this->evaluateCondition($subCondition, $switchValue, $context)) {
                    return true; // Any condition can be true for OR
                }
            }
            return false;
        }
        
        // 🆕 Handle variable reference in condition (for complex conditions)
        if (isset($condition['var'])) {
            $varKey = RuleFlowHelper::normalizeVariableName($condition['var']);
            $switchValue = $context[$varKey] ?? null;
            
            if ($switchValue === null) {
                return false; // Missing variable means condition fails
            }
        }

        $operator = $condition['op'] ?? '==';
        $value = $condition['value'] ?? null;
        
        // Handle $ references in condition values
        if (is_string($value) && substr($value, 0, 1) === '$') {
            $varKey = RuleFlowHelper::normalizeVariableName($value);
            $value = $context[$varKey] ?? null;
        }
        
        switch ($operator) {
            case '==':
                return $switchValue == $value;
            case '!=':
                return $switchValue != $value;
            case '>':
                return is_numeric($switchValue) && is_numeric($value) && $switchValue > $value;
            case '>=':
                return is_numeric($switchValue) && is_numeric($value) && $switchValue >= $value;
            case '<':
                return is_numeric($switchValue) && is_numeric($value) && $switchValue < $value;
            case '<=':
                return is_numeric($switchValue) && is_numeric($value) && $switchValue <= $value;
            case 'between':
                return is_array($value) && count($value) === 2 && 
                       is_numeric($switchValue) && 
                       $switchValue >= $value[0] && $switchValue <= $value[1];
            case 'in':
                return is_array($value) && in_array($switchValue, $value);
            case 'not_in':
                return is_array($value) && !in_array($switchValue, $value);
            case 'contains':
                return is_string($switchValue) && is_string($value) && strpos($switchValue, $value) !== false;
            case 'starts_with':
                return is_string($switchValue) && is_string($value) && str_starts_with($switchValue, $value);
            case 'ends_with':
                return is_string($switchValue) && is_string($value) && str_ends_with($switchValue, $value);
            case 'function':
                if (isset($condition['function'])) {
                    $registry = $this->evaluator->getFunctionRegistry();
                    $result = $registry->call($condition['function'], [$switchValue]);
                    return (bool)$result;
                }
                return false;
            default:
                throw new RuleFlowException("Unsupported condition operator: $operator");
        }
    }

    /**
     * 🔧 Enhanced evaluateResult method
     */
    private function evaluateResult(array $condition, array $context): mixed
    {
        if (isset($condition['result'])) {
            $result = $condition['result'];
            
            // If result is a string that looks like a variable, resolve it
            if (is_string($result) && isset($context[$result])) {
                return $context[$result];
            }
            
            // If result is a formula expression, evaluate it
            if (is_string($result) && $this->looksLikeFormula($result)) {
                return $this->evaluator->safeEval($result, $context);
            }
            
            return $result;
        }
        
        throw new RuleFlowException("No result specified in condition");
    }

    /**
     * Process set_vars
     */
    private function processSetVars(array $setVars, array &$context): void
    {
        foreach ($setVars as $varName => $value) {
            $storeKey = RuleFlowHelper::normalizeVariableName($varName);
            
            if (is_string($value)) {
                // Check if it's a simple reference (e.g., '$base_points')
                if ($this->isSimpleReference($value)) {
                    $referenceKey = RuleFlowHelper::normalizeVariableName($value);
                    if (isset($context[$referenceKey])) {
                        // Direct assignment to preserve type
                        $context[$storeKey] = $context[$referenceKey];
                    } else {
                        throw new RuleFlowException("Reference variable '$value' not found in context");
                    }
                }
                // Check if it contains variables or operators (expression)
                elseif ($this->hasVariablesOrOperators($value)) {
                    // CREATE FILTERED CONTEXT
                    $filteredContext = [];
                    
                    // Extract variable names from expression
                    if (preg_match_all('/\$?[a-zA-Z_][a-zA-Z0-9_]*/', $value, $matches)) {
                        foreach ($matches[0] as $varName) {
                            $normalizedVar = RuleFlowHelper::normalizeVariableName($varName);
                            if (isset($context[$normalizedVar])) {
                                $filteredContext[$normalizedVar] = $context[$normalizedVar];
                            }
                        }
                    }
                    
                    try {
                        $evaluatedValue = $this->evaluator->safeEval($value, $filteredContext);
                        $context[$storeKey] = $evaluatedValue;
                    } catch (Exception $e) {
                        throw new RuleFlowException("Error evaluating set_vars expression '$value': " . $e->getMessage());
                    }
                }
                else {
                    // Simple literal string - apply type conversion
                    $context[$storeKey] = $this->convertValueType($value);
                }
            } else {
                // Direct assignment for non-string values
                $context[$storeKey] = $value;
            }
        }
    }

    private function hasVariablesOrOperators($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        $hasVar = strpos($value, '$') !== false;
        $hasOp = preg_match('/[+\-*\/()]/', $value);
        
        return $hasVar || $hasOp;
    }


    /**
     * Check if value is a simple reference (e.g., '$variable_name')
     */
    private function isSimpleReference($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        $trimmed = trim($value);
        // Match exactly: $variable_name (no operators, no extra text)
        return preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', $trimmed) === 1;
    }

    
    /**
     * Convert string value to appropriate type - NEW METHOD
     */
    private function convertValueType($value)
    {
        // If not string, return as-is
        if (!is_string($value)) {
            return $value;
        }
        
        $trimmed = trim($value);
        
        // Handle empty string
        if ($trimmed === '') {
            return '';
        }

        // Handle numeric values
        if (is_numeric($trimmed)) {
                // Check if it's an integer
                if (ctype_digit($trimmed) || (substr($trimmed, 0, 1) === '-' && ctype_digit(substr($trimmed, 1)))) {
                    $intValue = (int)$trimmed;
                    // Ensure no precision loss
                    if ((string)$intValue === $trimmed) {
                        return $intValue;
                    }
                }
                
                // It's a float
                return (float)$trimmed;
            }
        
        // Handle boolean values
         if (in_array(strtolower($trimmed), ['true', 'yes', 'on'])) {
            return true;
        }
        if (in_array(strtolower($trimmed), ['false', 'no', 'off'])) {
            return false;
        }
        
        // Handle null
        if (strtolower($trimmed) === 'null') {
            return null;
        }
        
        
        
        // Return as string if no conversion applies
        return $value;
    }

    /**
     * Check if string looks like a formula
     */
    private function looksLikeFormula($value): bool
    {
        // FIX: Handle null and non-string values
        if (!is_string($value) || $value === null) {
            return false;
        }
        
        // Simple heuristic: contains operators or function calls
        return preg_match('/[+\-*\/()]/', $value) || 
            preg_match('/[a-zA-Z_][a-zA-Z0-9_]*\s*\(/', $value);
    }


    

    /**
     * Get formula type for debugging
     */
    private function getFormulaType(array $formula): string
    {
        if (isset($formula['function_call'])) return 'function_call';
        if (isset($formula['formula'])) return 'expression';
        if (isset($formula['switch'])) return 'switch';
        if (isset($formula['rules'])) return 'rules';
        if (isset($formula['scoring'])) return 'scoring';
        return 'unknown';
    }
}