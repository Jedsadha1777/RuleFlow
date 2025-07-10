<?php

declare(strict_types=1);

/**
 * Generate optimized PHP code from configurations
 */
class CodeGenerator
{
    /**
     * Generate PHP function code as string
     */
    public function generate(array $config): string
    {
        $formulas = $config['formulas'];
        
        // Analyze dependencies and optimize order
        $optimizedOrder = $this->optimizeExecutionOrder($formulas);
        
        $code = "function(array \$inputs): array {\n";
        $code .= "    \$context = \$inputs;\n\n";
        
        // Generate code for each formula in optimized order
        foreach ($optimizedOrder as $formula) {
            $code .= $this->generateFormulaCode($formula);
        }
        
        $code .= "    return \$context;\n";
        $code .= "}";
        
        return $code;
    }

    /**
     * Generate code for individual formula
     */
    private function generateFormulaCode(array $formula): string
    {
        $code = "    // Formula: {$formula['id']}\n";
        
        if (isset($formula['formula'])) {
            $code .= $this->generateExpressionCode($formula);
        } elseif (isset($formula['switch'])) {
            $code .= $this->generateSwitchCode($formula);
        } elseif (isset($formula['rules'])) {
            $code .= $this->generateScoreRulesCode($formula);
        } elseif (isset($formula['scoring'])) {
            $code .= $this->generateWeightScoreCode($formula);
        }
        
        $code .= "\n";
        return $code;
    }

    /**
     * Generate expression formula code with $ notation
     */
    private function generateExpressionCode(array $formula): string
    {
        $expr = $formula['formula'];
        $inputs = $formula['inputs'] ?? [];
        
        // Handle $ notation for output
        $storeAs = $formula['as'] ?? ('$' . $formula['id']);
        $contextKey = $this->normalizeVariableName($storeAs);
        
        $code = "";
        
        // Input validation
        foreach ($inputs as $input) {
            $code .= "    if (!isset(\$context['$input'])) {\n";
            $code .= "        throw new Exception('Missing input: $input');\n";
            $code .= "    }\n";
        }
        
        // Convert expression to PHP code
        $phpExpr = $this->convertExpressionToPhp($expr, $inputs);
        
        $code .= "    \$context['$contextKey'] = $phpExpr;\n";
        
        return $code;
    }

    /**
     * Generate switch/case code with $ notation support
     */
    private function generateSwitchCode(array $formula): string
    {
        $switchVar = $formula['switch'];
        $id = $formula['id'];
        $cases = $formula['when'];
        $default = $formula['default'] ?? 'null';
        
        // Handle $ notation for switch variable
        $switchContextKey = $this->normalizeVariableName($switchVar);
        
        $code = "    \$switchValue = \$context['$switchContextKey'] ?? null;\n";
        $code .= "    if (\$switchValue === null) {\n";
        $code .= "        throw new Exception('Switch value \\'$switchVar\\' not found');\n";
        $code .= "    }\n";
        
        foreach ($cases as $i => $case) {
            $condition = $this->generateConditionCode('$switchValue', $case['if']);
            $result = $this->escapePhpValue($case['result']);
            
            if ($i === 0) {
                $code .= "    if ($condition) {\n";
            } else {
                $code .= "    } elseif ($condition) {\n";
            }
            
            $code .= "        \$context['$id'] = $result;\n";
            
            // Handle set_vars with $ notation support
            if (isset($case['set_vars'])) {
                $code .= $this->processSetVars($case['set_vars'], "        ");
            }
        }
        
        // Default case
        $code .= "    } else {\n";
        $code .= "        \$context['$id'] = " . $this->escapePhpValue($default) . ";\n";
        
        // Handle default_vars
        if (isset($formula['default_vars'])) {
            $code .= $this->processSetVars($formula['default_vars'], "        ");
        }
        
        $code .= "    }\n";
        
        return $code;
    }

    /**
     * Generate score rules code with $ notation for output
     */
    private function generateScoreRulesCode(array $formula): string
    {
        $id = $formula['id'];
        $rules = $formula['rules'];
        
        $code = "    \$score = 0;\n";
        
        foreach ($rules as $rule) {
            $variable = $rule['var'];
            $varKey = $this->normalizeVariableName($variable);
            
            $code .= "    \$value = \$context['$varKey'] ?? null;\n";
            $code .= "    if (\$value !== null) {\n";
            
            if (isset($rule['ranges'])) {
                foreach ($rule['ranges'] as $i => $range) {
                    $condition = $this->generateConditionCode('$value', $range['if']);
                    
                    if ($i === 0) {
                        $code .= "        if ($condition) {\n";
                    } else {
                        $code .= "        } elseif ($condition) {\n";
                    }
                    
                    // FIX: Use 'result' instead of 'score'
                    $result = $range['result'] ?? 0;
                    $code .= "            \$score += $result;\n";
                    
                    // Handle set_vars in ranges
                    if (isset($range['set_vars'])) {
                        $code .= $this->processSetVars($range['set_vars'], "            ");
                    }
                }
                $code .= "        }\n";
            } elseif (isset($rule['if'])) {
                $condition = $this->generateConditionCode('$value', $rule['if']);
                $code .= "        if ($condition) {\n";
                
                // FIX: Use 'result' instead of 'score'
                $result = $rule['result'] ?? 0;
                $code .= "            \$score += $result;\n";
                
                // Handle set_vars in rules
                if (isset($rule['set_vars'])) {
                    $code .= $this->processSetVars($rule['set_vars'], "            ");
                }
                
                $code .= "        }\n";
            }
            
            $code .= "    }\n";
        }
        
        $code .= "    \$context['$id'] = \$score;\n";
        return $code;
    }

    /**
     * Generate weight score code with $ notation
     */
    private function generateWeightScoreCode(array $formula): string
    {
        $id = $formula['id'];
        $weightScore = $formula['scoring'];
        
        if (isset($weightScore['ranges'])) {
            return $this->generateRangeBasedScoring($formula);
        } elseif (isset($weightScore['if'])) {
            return $this->generateSimpleWeightScore($formula);
        } elseif (isset($weightScore['ifs'])) {
            return $this->generateMultiConditionCode($formula);
        }
        
        $code = "    // Unknown weight score type - using default\n";
        $code .= "    \$context['$id'] = 0;\n";
        
        return $code;
    }

    /**
     * Generate multi-condition code with $ notation
     */
    private function generateMultiConditionCode(array $formula): string
    {
        $id = $formula['id'];
        $multiCondition = $formula['scoring']['ifs'];
        $variables = $multiCondition['vars'];
        $matrix = $multiCondition['tree'];
        
        $code = "    // Multi-condition scoring for {$id}\n";
        
        // Check if all variables exist (handle $ notation)
        foreach ($variables as $var) {
            $varKey = $this->normalizeVariableName($var);
            $code .= "    if (!isset(\$context['$varKey'])) {\n";
            $code .= "        \$context['$id'] = 0;\n";
            $code .= "        return \$context;\n";
            $code .= "    }\n";
        }
        
        // Get variable values
        foreach ($variables as $i => $var) {
            $varKey = $this->normalizeVariableName($var);
            $code .= "    \$var$i = \$context['$varKey'];\n";
        }
        
        $code .= "    \$matched = false;\n";
        $code .= "\n";
        
        // Generate nested conditions
        $code .= $this->generateMatrixCode($matrix, $variables, 0, $id);
        
        // Default case
        $code .= "    if (!\$matched) {\n";
        $code .= "        \$context['$id'] = 0;\n";
        $code .= "    }\n";
        
        return $code;
    }

    /**
     * Generate code for matrix navigation with $ notation
     */
    private function generateMatrixCode(array $matrix, array $variables, int $depth, string $formulaId): string
    {
        $code = "";
        $varName = "\$var$depth";
        
        foreach ($matrix as $i => $item) {
            $condition = $this->generateConditionCode($varName, $item['if']);
            
            if ($i === 0) {
                $code .= "    if ($condition) {\n";
            } else {
                $code .= "    } elseif ($condition) {\n";
            }
            
            // If there are more levels (ranges), recurse
            if (isset($item['ranges']) && ($depth + 1) < count($variables)) {
                $code .= $this->generateMatrixCode($item['ranges'], $variables, $depth + 1, $formulaId);
            } else {
                // Final level - set result
                $score = $item['score'] ?? 0;
                $code .= "        \$context['$formulaId'] = $score;\n";
                $code .= "        \$matched = true;\n";
                
                // Handle additional result data
                foreach ($item as $key => $value) {
                    if (!in_array($key, ['if', 'ranges', 'score'])) {
                        $phpValue = $this->escapePhpValue($value);
                        $code .= "        \$context['{$formulaId}_$key'] = $phpValue;\n";
                    }
                }
                
                // Handle set_vars
                if (isset($item['set_vars'])) {
                    $code .= $this->processSetVars($item['set_vars'], "        ");
                }
            }
        }
        
        $code .= "    }\n";
        
        return $code;
    }

    /**
     * Generate range-based scoring code with $ notation
     */
    private function generateRangeBasedScoring(array $formula): string
    {
        $id = $formula['id'];
        $storeAs = $formula['as'] ?? ('$' . $id);
        $storeAsKey = $this->normalizeVariableName($storeAs);
        $ranges = $formula['scoring']['ranges'];
        $default = $formula['scoring']['default'] ?? 0;
        
        $code = "    \$value = \$context['$storeAsKey'] ?? null;\n";
        $code .= "    if (\$value === null) {\n";
        $code .= "        \$context['$id'] = 0;\n";
        $code .= "    } else {\n";
        
        foreach ($ranges as $i => $range) {
            $condition = $this->generateConditionCode('$value', $range['if']);
            
            if ($i === 0) {
                $code .= "        if ($condition) {\n";
            } else {
                $code .= "        } elseif ($condition) {\n";
            }
            
            $code .= "            \$context['$id'] = {$range['score']};\n";
            
            // Handle set_vars
            if (isset($range['set_vars'])) {
                $code .= $this->processSetVars($range['set_vars'], "            ");
            }
        }
        
        $code .= "        } else {\n";
        $code .= "            \$context['$id'] = $default;\n";
        $code .= "        }\n";
        $code .= "    }\n";
        
        return $code;
    }

    /**
     * Generate simple weight score code with $ notation
     */
    private function generateSimpleWeightScore(array $formula): string
    {
        $id = $formula['id'];
        $storeAs = $formula['as'] ?? ('$' . $id);
        $storeAsKey = $this->normalizeVariableName($storeAs);
        $condition = $formula['scoring']['if'];
        $score = $formula['scoring']['score'];
        
        $code = "    \$value = \$context['$storeAsKey'] ?? null;\n";
        $code .= "    if (\$value !== null && " . 
                 $this->generateConditionCode('$value', $condition) . ") {\n";
        $code .= "        \$context['$id'] = $score;\n";
        $code .= "    } else {\n";
        $code .= "        \$context['$id'] = 0;\n";
        $code .= "    }\n";
        
        return $code;
    }

    /**
     * Process set_vars with $ notation support
     */
    private function processSetVars(array $setVars, string $indent = "        "): string
    {
        $code = "";
        
        foreach ($setVars as $varName => $varValue) {
            // Normalize variable name (remove $ if present for context key)
            $contextKey = $this->normalizeVariableName($varName);
            
            if (is_string($varValue) && $this->isDollarReference($varValue)) {
                // เป็น $ reference
                $refKey = $this->normalizeVariableName($varValue);
                $code .= "{$indent}\$context['$contextKey'] = \$context['$refKey'] ?? null;\n";
            } elseif (is_string($varValue) && $this->isDollarExpression($varValue)) {
                // เป็น expression ที่มี $ variables
                $phpExpr = $this->convertDollarExpressionToPhp($varValue);
                $code .= "{$indent}\$context['$contextKey'] = $phpExpr;\n";
            } else {
                // Literal value
                $phpValue = $this->escapePhpValue($varValue);
                $code .= "{$indent}\$context['$contextKey'] = $phpValue;\n";
            }
        }
        
        return $code;
    }

    /**
     * Generate condition code with $ notation support
     */
    private function generateConditionCode(string $variable, array $condition): string
    {
        $operator = $condition['op'];
        $value = $condition['value'];
        
        return match ($operator) {
            '<' => "$variable < " . $this->formatConditionValue($value),
            '<=' => "$variable <= " . $this->formatConditionValue($value),
            '>' => "$variable > " . $this->formatConditionValue($value),
            '>=' => "$variable >= " . $this->formatConditionValue($value),
            '==' => "$variable == " . $this->escapePhpValue($value),
            '!=' => "$variable != " . $this->escapePhpValue($value),
            'between' => $this->generateBetweenCondition($variable, $value),
            'in' => "in_array($variable, " . var_export($value, true) . ", true)",
            default => throw new RuleFlowException("Unsupported operator: $operator")
        };
    }

    /**
     * Format condition value with $ notation support
     */
    private function formatConditionValue($value): string
    {
        if (is_string($value) && $this->isDollarReference($value)) {
            $valueKey = $this->normalizeVariableName($value);
            return "\$context['$valueKey']";
        }
        
        return $this->formatValue($value);
    }

    /**
     * Generate between condition with $ notation support
     */
    private function generateBetweenCondition(string $variable, $value): string
    {
        if (is_array($value) && count($value) === 2) {
            $min = $this->formatConditionValue($value[0]);
            $max = $this->formatConditionValue($value[1]);
            
            return "$variable >= $min && $variable <= $max";
        }
        
        throw new RuleFlowException("Between operator requires array with 2 values");
    }

    /**
     * Optimize execution order with $ notation support
     */
    private function optimizeExecutionOrder(array $formulas): array
    {
        $ordered = [];
        $processed = [];
        $dependencies = [];
        $dynamicOutputs = []; // Track variables created by set_vars
        $maxIterations = count($formulas) * 3; // เพิ่มจาก *2 เป็น *3
        $iteration = 0;
        
        // Phase 1: Build initial dependency map
        foreach ($formulas as $formula) {
            $id = $formula['id'];
            $deps = [];
            
            // Standard dependencies
            if (isset($formula['inputs'])) {
                foreach ($formula['inputs'] as $input) {
                    $deps[] = $this->normalizeVariableName($input);
                }
            }
            if (isset($formula['switch'])) {
                $switchVar = $this->normalizeVariableName($formula['switch']);
                $deps[] = $switchVar;
            }
            
            // Dependencies from scoring vars
            if (isset($formula['scoring']['ifs']['vars'])) {
                foreach ($formula['scoring']['ifs']['vars'] as $var) {
                    $varKey = $this->normalizeVariableName($var);
                    $deps[] = $varKey;
                }
            }
            
            // Dependencies from rules
            if (isset($formula['rules'])) {
                foreach ($formula['rules'] as $rule) {
                    if (isset($rule['var'])) {
                        $varKey = $this->normalizeVariableName($rule['var']);
                        $deps[] = $varKey;
                    }
                }
            }
            
            // NEW: Extract set_vars dependencies and outputs
            $this->extractSetVarsDependencies($formula, $deps, $dynamicOutputs);
            
            $dependencies[$id] = array_unique($deps);
        }
        
        // Phase 2: Enhanced topological sort with dynamic dependency resolution
        while (count($ordered) < count($formulas) && $iteration < $maxIterations) {
            $iteration++;
            $progressMade = false;
            
            foreach ($formulas as $formula) {
                $id = $formula['id'];
                
                if (in_array($id, $processed)) {
                    continue;
                }
                
                // Check if all dependencies are satisfied
                $canProcess = $this->canProcessFormula($formula, $dependencies[$id], $processed, $dynamicOutputs, $formulas);
                
                if ($canProcess) {
                    $ordered[] = $formula;
                    $processed[] = $id;
                    $progressMade = true;
                    
                    // Update dynamic outputs after processing this formula
                    $this->updateDynamicOutputs($formula, $dynamicOutputs);
                }
            }
            
            // If no progress and still have formulas, there might be circular dependencies
            if (!$progressMade && count($ordered) < count($formulas)) {
                $remaining = [];
                foreach ($formulas as $formula) {
                    if (!in_array($formula['id'], $processed)) {
                        $remaining[] = $formula['id'];
                    }
                }
                
                // Try to break circular dependencies by finding the "safest" formula to process
                $safestFormula = $this->findSafestFormula($formulas, $processed, $dependencies);
                if ($safestFormula) {
                    $ordered[] = $safestFormula;
                    $processed[] = $safestFormula['id'];
                    $this->updateDynamicOutputs($safestFormula, $dynamicOutputs);
                    $progressMade = true;
                } else {
                    // Complete deadlock - use original order for remaining
                    foreach ($formulas as $formula) {
                        if (!in_array($formula['id'], $processed)) {
                            $ordered[] = $formula;
                        }
                    }
                    break;
                }
            }
        }
        
        return $ordered;
    }


    /**
     * Find the safest formula to process when there's a deadlock
     */
    private function findSafestFormula(array $formulas, array $processed, array $dependencies): ?array
    {
        $candidates = [];
        
        foreach ($formulas as $formula) {
            if (in_array($formula['id'], $processed)) {
                continue;
            }
            
            $unmetDeps = 0;
            foreach ($dependencies[$formula['id']] as $dep) {
                if (!$this->isInputVariable($dep)) {
                    $unmetDeps++;
                }
            }
            
            $candidates[] = [
                'formula' => $formula,
                'unmet_deps' => $unmetDeps
            ];
        }
        
        if (empty($candidates)) {
            return null;
        }
        
        // Sort by least unmet dependencies
        usort($candidates, fn($a, $b) => $a['unmet_deps'] <=> $b['unmet_deps']);
        
        return $candidates[0]['formula'];
    }
    
    /**
     * Check if a variable is an input variable (not produced by formulas)
     */
    private function isInputVariable(string $varName): bool
    {
        // Input variables are typically not starting with $ or are basic data types
        $knownInputs = ['base', 'score', 'age', 'income', 'price', 'quantity', 'grade', 'base_score'];
        return in_array($varName, $knownInputs) ||
               (substr($varName, 0, 1) !== '$' && 
                !in_array($varName, ['step1', 'step2', 'final', 'formula_a', 'formula_b', 'intermediate', 'bonus']));
    }
    

     /**
     * Update dynamic outputs after processing a formula
     */
    private function updateDynamicOutputs(array $formula, array &$dynamicOutputs): void
    {
        // Mark formula's main output as available
        $mainOutput = isset($formula['as']) ? 
            $this->normalizeVariableName($formula['as']) : $formula['id'];
        
        if (!isset($dynamicOutputs[$mainOutput])) {
            $dynamicOutputs[$mainOutput] = [];
        }
        $dynamicOutputs[$mainOutput][] = $formula['id'];
    }
    

    /**
     * Extract set_vars dependencies and outputs from formula
     */
    private function extractSetVarsDependencies(array $formula, array &$deps, array &$dynamicOutputs): void
    {
        // Look for set_vars in switch cases
        if (isset($formula['when'])) {
            foreach ($formula['when'] as $case) {
                if (isset($case['set_vars'])) {
                    $this->processSetVarsForDependency($case['set_vars'], $deps, $dynamicOutputs, $formula['id']);
                }
            }
        }
        
        // Look for set_vars in default_vars
        if (isset($formula['default_vars'])) {
            $this->processSetVarsForDependency($formula['default_vars'], $deps, $dynamicOutputs, $formula['id']);
        }
        
        // Look for set_vars in scoring ranges
        if (isset($formula['scoring']['ranges'])) {
            foreach ($formula['scoring']['ranges'] as $range) {
                if (isset($range['set_vars'])) {
                    $this->processSetVarsForDependency($range['set_vars'], $deps, $dynamicOutputs, $formula['id']);
                }
            }
        }
        
        // Look for set_vars in rules
        if (isset($formula['rules'])) {
            foreach ($formula['rules'] as $rule) {
                if (isset($rule['set_vars'])) {
                    $this->processSetVarsForDependency($rule['set_vars'], $deps, $dynamicOutputs, $formula['id']);
                }
            }
        }
    }

    /**
     * Check if formula can be processed given current state
     */
    private function canProcessFormula(array $formula, array $deps, array $processed, array $dynamicOutputs, array $allFormulas): bool
    {
        foreach ($deps as $dep) {
            $depSatisfied = false;
            
            // Check if it's an input variable (always available)
            if ($this->isInputVariable($dep)) {
                $depSatisfied = true;
            }
            // Check if produced by an already processed formula
            elseif (isset($dynamicOutputs[$dep])) {
                foreach ($dynamicOutputs[$dep] as $producingFormulaId) {
                    if (in_array($producingFormulaId, $processed)) {
                        $depSatisfied = true;
                        break;
                    }
                }
            }
            // Check if produced by a formula's main output
            else {
                foreach ($allFormulas as $f) {
                    if (in_array($f['id'], $processed)) {
                        $outputKey = isset($f['as']) ? 
                            $this->normalizeVariableName($f['as']) : $f['id'];
                        if ($outputKey === $dep) {
                            $depSatisfied = true;
                            break;
                        }
                    }
                }
            }
            
            if (!$depSatisfied) {
                return false;
            }
        }
        
        return true;
    }
    


    /**
     * Process set_vars to extract dependencies and track outputs
     */
    private function processSetVarsForDependency(array $setVars, array &$deps, array &$dynamicOutputs, string $formulaId): void
    {
        foreach ($setVars as $varName => $varValue) {
            $outputKey = $this->normalizeVariableName($varName);
            
            // Track this as a dynamic output
            if (!isset($dynamicOutputs[$outputKey])) {
                $dynamicOutputs[$outputKey] = [];
            }
            $dynamicOutputs[$outputKey][] = $formulaId;
            
            // Extract dependencies from the value
            if (is_string($varValue)) {
                if ($this->isDollarReference($varValue)) {
                    // Simple reference: $var1 = $var2
                    $depKey = $this->normalizeVariableName($varValue);
                    $deps[] = $depKey;
                } elseif ($this->isDollarExpression($varValue)) {
                    // Complex expression: extract all $variables
                    if (preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $varValue, $matches)) {
                        foreach ($matches[1] as $depVar) {
                            $deps[] = $depVar;
                        }
                    }
                }
            }
        }
    }
        

    /**
     * Convert mathematical expression to PHP code
     */
    private function convertExpressionToPhp(string $expr, array $inputs): string
    {
        // Replace variables with context access
        foreach ($inputs as $input) {
            $expr = preg_replace('/\b' . preg_quote($input, '/') . '\b/', "\$context['$input']", $expr);
        }
        
        // Replace ** with pow()
        $expr = preg_replace_callback('/(\([^)]+\)|[^\s\*\(\)]+)\s*\*\*\s*(\([^)]+\)|[^\s\*\(\)]+)/', function($matches) {
            $base = trim($matches[1]);
            $exponent = trim($matches[2]);
            
            if (substr($base, 0, 1) === '(' && substr($base, -1) === ')') {
                $base = substr($base, 1, -1);
            }
            if (substr($exponent, 0, 1) === '(' && substr($exponent, -1) === ')') {
                $exponent = substr($exponent, 1, -1);
            }
            
            return "pow({$base}, {$exponent})";
        }, $expr);
        
        return $expr;
    }

    /**
     * Convert $ expression to PHP code
     */
    private function convertDollarExpressionToPhp(string $expr): string
    {
        // Replace $variable_name with $context['variable_name']
        return preg_replace('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', "\$context['$1']", $expr);
    }

    /**
     * Format value for PHP code generation
     */
    private function formatValue($value): string
    {
        if (is_numeric($value)) {
            return (string)$value;
        } elseif (is_string($value)) {
            return "'" . addslashes($value) . "'";
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            return 'null';
        } elseif (is_array($value)) {
            return var_export($value, true);
        } else {
            return var_export($value, true);
        }
    }

    /**
     * Escape PHP value for code generation
     */
    private function escapePhpValue($value): string
    {
        if (is_string($value)) {
            return "'" . addslashes($value) . "'";
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            return 'null';
        } elseif (is_array($value)) {
            return var_export($value, true);
        } elseif (is_numeric($value)) {
            return (string)$value;
        } else {
            return var_export($value, true);
        }
    }

    /**
     * Helper methods
     */
    private function normalizeVariableName(string $varName): string
    {
        return substr($varName, 0, 1) === '$' ? substr($varName, 1) : $varName;
    }

    private function isDollarReference(string $value): bool
    {
        return preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', trim($value)) === 1;
    }

    private function isDollarExpression(string $value): bool
    {
        $trimmed = trim($value);
        return preg_match('/\$[a-zA-Z_][a-zA-Z0-9_]*/', $trimmed) && 
               !$this->isDollarReference($trimmed) &&
               (preg_match('/[\+\-\*\/\(\)\s]/', $trimmed) || 
                preg_match('/\$[a-zA-Z_][a-zA-Z0-9_]*.*\$[a-zA-Z_][a-zA-Z0-9_]*/', $trimmed));
    }
}