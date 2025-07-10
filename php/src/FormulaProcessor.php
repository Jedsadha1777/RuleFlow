<?php

declare(strict_types=1);

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
            $result = null; // เพิ่มตัวแปร result
            
            if (isset($formula['function_call'])) {
                $result = $this->processFunctionCall($formula, $context);
            }
            // Existing: Switch logic
            elseif (isset($formula['switch'])) {
              $switchResult = $this->processSwitch($formula, $context);

                 $result = $switchResult['result'];
                $context = $switchResult['context'];
            }
            // Existing: Regular formula evaluation
            elseif (isset($formula['formula'])) {
                $result = $this->processExpressionFormula($formula, $context);
            }
            // Existing: Rules-based scoring
            elseif (isset($formula['rules'])) {
                $result = $this->processAccumulativeScore($formula, $context);
            }
            // Existing: Weighted scoring
            elseif (isset($formula['scoring'])) {
                $result = $this->processWeightScore($formula, $context);
            }
            else {
                throw new RuleFlowException("Invalid formula structure for '$formulaId'");
            }
            

            $variableName = $formula['as'] ?? $formulaId;
            $storeKey = $this->normalizeVariableName($variableName);
        
            $context[$storeKey] = $result;
    
            
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
     * 🆕 Process direct function call
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
     * 🆕 Resolve parameter value from context or use literal
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
                $contextKey = $this->normalizeVariableName($key);
                
                // 🔧 FIX: ลองหาใน context หลายแบบ
                if (isset($context[$contextKey])) {
                    $vars[$key] = $context[$contextKey];
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
        $switchVar = $this->normalizeVariableName($formula['switch']);
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
            $varKey = $this->normalizeVariableName($rule['var']);
            $value = $context[$varKey] ?? null;
            
            if ($value === null) {
                continue;
            }
            
            // Handle ranges
            if (isset($rule['ranges'])) {
                foreach ($rule['ranges'] as $range) {
                    if ($this->evaluateCondition($range['if'], $value, $context)) {
                        $totalScore += $range['score'];
                        break;
                    }
                }
            }
            // Handle single condition
            elseif (isset($rule['if'])) {
                if ($this->evaluateCondition($rule['if'], $value, $context)) {
                    $totalScore += $rule['score'];
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
             $result = $this->processMultiConditionScoring($formula, $context);

             return is_array($result) ? ($result['score'] ?? 0) : $result;

        } elseif (isset($formula['scoring']['if'])) {
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
            $this->normalizeVariableName($formula['as']) : $formula['id'];
        $value = $context[$storeAs] ?? null;
        
        if ($value === null) {
            return 0.0;
        }
        
        if ($this->evaluateCondition($formula['scoring']['if'], $value, $context)) {
            return (float)$formula['scoring']['score'];
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
        
        return is_array($result) ? $result : ['score' => $result];
    }

    /**
     * Navigate scoring matrix
     */
    private function navigateMatrix(array $matrix, array $values, int $depth, array $context): mixed
    {
        if ($depth >= count($values)) {
            return $matrix;
        }
        
        $value = $values[$depth];
        
        foreach ($matrix as $node) {
            if ($this->evaluateCondition($node['if'], $value, $context)) {
                if (isset($node['children'])) {
                    return $this->navigateMatrix($node['children'], $values, $depth + 1, $context);
                } else {
                    return $node['score'] ?? 0;
                }
            }
        }
        
        return 0;
    }

    /**
     * 🔧 Fixed evaluateCondition method with correct parameter order
     */
    private function evaluateCondition(array $condition, $switchValue, array $context): bool
    {
        $operator = $condition['op'] ?? '==';
        $value = $condition['value'] ?? null;
        
        // Handle $ references in condition values
        if (is_string($value) && substr($value, 0, 1) === '$') {
            $varKey = $this->normalizeVariableName($value);
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
        $storeKey = $this->normalizeVariableName($varName);
        
   
        if (is_string($value)) {
            $hasVar = strpos($value, '$') !== false;
            $hasOp = preg_match('/[+\-*\/()]/', $value);
            
       
            
            if ($hasVar || $hasOp) {
    
                
                // 🔧 CREATE FILTERED CONTEXT
                $filteredContext = [];
                
                // Extract variable names from expression
                if (preg_match_all('/\$?(\w+)/', $value, $matches)) {
                  
                    
                    foreach ($matches[1] as $foundVar) {
                        if (isset($context[$foundVar])) {
                            $filteredContext[$foundVar] = $context[$foundVar];
                         
                        }
                    }
                }
                

                
                try {
                    $evaluatedValue = $this->evaluator->safeEval($value, $filteredContext);
                    $context[$storeKey] = $evaluatedValue;
           
                } catch (Exception $e) {
                  
                    $context[$storeKey] = $value;
                }
            } else {
                $context[$storeKey] = $value;

            }
        } else {
            $context[$storeKey] = $value;

        }
    }

}

    /**
     * Check if string looks like a formula
     */
    private function looksLikeFormula(string $str): bool
    {
        // Simple check for mathematical operators or function calls
        return preg_match('/[+\-*\/()]|\w+\s*\(/', $str) === 1;
    }

    /**
     * Normalize variable name (handle $ prefix)
     */
    private function normalizeVariableName(string $varName): string
    {
         return substr($varName, 0, 1) === '$' ? substr($varName, 1) : $varName;
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