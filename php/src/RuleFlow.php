<?php

declare(strict_types=1);

/**
 * Rule Engine with $ notation for variables and references
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
     * Generate PHP function code as string
     */
    public function generateFunctionAsString(array $config): string
    {
        $errors = $this->validateConfig($config);
        if (!empty($errors)) {
            throw new Exception("Invalid configuration: " . implode(', ', $errors));
        }

        return $this->generateFunctionCode($config);
    }

    /**
     * Generate optimized PHP function code
     */
    private function generateFunctionCode(array $config): string
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
            // Handle $ notation for variable reference
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
                    
                    $code .= "            \$score += {$range['score']};\n";
                    
                    // Handle set_vars in ranges
                    if (isset($range['set_vars'])) {
                        $code .= $this->processSetVars($range['set_vars'], "            ");
                    }
                }
                $code .= "        }\n";
            } elseif (isset($rule['if'])) {
                $condition = $this->generateConditionCode('$value', $rule['if']);
                $code .= "        if ($condition) {\n";
                $code .= "            \$score += {$rule['score']};\n";
                
                // Handle set_vars in rules
                if (isset($rule['set_vars'])) {
                    $code .= $this->processSetVars($rule['set_vars'], "            ");
                }
                
                $code .= "        }\n";
            }
            
            $code .= "    }\n";
        }
        
        // Use $ notation for score output (remove _score suffix)
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
     * Normalize variable name (remove $ prefix for context key)
     */
    private function normalizeVariableName(string $varName): string
    {
        return substr($varName, 0, 1) === '$' ? substr($varName, 1) : $varName;
    }

    /**
     * Check if value is a $ reference
     */
    private function isDollarReference(string $value): bool
    {
        return preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', $value) === 1;
    }

    /**
     * Check if value is a $ expression
     */
    private function isDollarExpression(string $value): bool
    {
        return preg_match('/\$[a-zA-Z_][a-zA-Z0-9_]*/', $value) && 
               preg_match('/[\+\-\*\/\(\)]/', $value);
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
            default => throw new Exception("Unsupported operator: $operator")
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
        
        throw new Exception("Between operator requires array with 2 values");
    }

    /**
     * Optimize execution order with $ notation support
     */
    private function optimizeExecutionOrder(array $formulas): array
    {
        $ordered = [];
        $processed = [];
        $dependencies = [];
        $maxIterations = count($formulas) * 2;
        $iteration = 0;
        
        // Build dependency map with $ notation support
        foreach ($formulas as $formula) {
            $id = $formula['id'];
            $deps = [];
            
            if (isset($formula['inputs'])) {
                $deps = array_merge($deps, $formula['inputs']);
            }
            if (isset($formula['switch'])) {
                $switchVar = $this->normalizeVariableName($formula['switch']);
                $deps[] = $switchVar;
            }
            
            // Add dependencies from scoring vars
            if (isset($formula['scoring']['ifs']['vars'])) {
                foreach ($formula['scoring']['ifs']['vars'] as $var) {
                    $varKey = $this->normalizeVariableName($var);
                    $deps[] = $varKey;
                }
            }
            
            // Add dependencies from rules
            if (isset($formula['rules'])) {
                foreach ($formula['rules'] as $rule) {
                    if (isset($rule['var'])) {
                        $varKey = $this->normalizeVariableName($rule['var']);
                        $deps[] = $varKey;
                    }
                }
            }
            
            $dependencies[$id] = array_unique($deps);
        }
        
        // Topological sort
        while (count($ordered) < count($formulas) && $iteration < $maxIterations) {
            $iteration++;
            $progressMade = false;
            
            foreach ($formulas as $formula) {
                $id = $formula['id'];
                
                if (in_array($id, $processed)) {
                    continue;
                }
                
                $canProcess = true;
                foreach ($dependencies[$id] as $dep) {
                    $depSatisfied = false;
                    $isInput = true;
                    
                    foreach ($formulas as $f) {
                        $outputKey = isset($f['as']) ? 
                            $this->normalizeVariableName($f['as']) : $f['id'];
                            
                        if ($outputKey === $dep) {
                            $isInput = false;
                            if (in_array($f['id'], $processed)) {
                                $depSatisfied = true;
                            }
                            break;
                        }
                    }
                    
                    if ($isInput) {
                        $depSatisfied = true;
                    }
                    
                    if (!$depSatisfied) {
                        $canProcess = false;
                        break;
                    }
                }
                
                if ($canProcess) {
                    $ordered[] = $formula;
                    $processed[] = $id;
                    $progressMade = true;
                }
            }
            
            if (!$progressMade) {
                foreach ($formulas as $formula) {
                    if (!in_array($formula['id'], $processed)) {
                        $ordered[] = $formula;
                        $processed[] = $formula['id'];
                    }
                }
                break;
            }
        }
        
        return $ordered;
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
     * Validate JSON configuration format with $ notation
     */
    public function validateConfig(array $config): array
    {
        $errors = [];

        if (!isset($config['formulas'])) {
            $errors[] = "Missing required 'formulas' key";
            return $errors;
        }

        if (!is_array($config['formulas'])) {
            $errors[] = "'formulas' must be an array";
            return $errors;
        }

        foreach ($config['formulas'] as $index => $formula) {
            $formulaErrors = $this->validateFormula($formula, $index);
            $errors = array_merge($errors, $formulaErrors);
        }

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

        $configErrors = $this->validateConfig($config);
        if (!empty($configErrors)) {
            $result['valid'] = false;
            $result['errors'] = $configErrors;
            return $result;
        }

        if (!empty($sampleInputs)) {
            try {
                $testResult = $this->evaluate($config, $sampleInputs);
                $result['test_results'] = $testResult;
            } catch (Exception $e) {
                $result['valid'] = false;
                $result['errors'][] = "Test execution failed: " . $e->getMessage();
            }
        }

        $warnings = $this->checkForWarnings($config);
        $result['warnings'] = $warnings;

        return $result;
    }

    /**
     * Evaluate formulas based on JSON configuration and input values
     */
    public function evaluate(array $config, array $inputs): array
    {
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
        if (isset($formula['formula'])) {
            $this->processExpressionFormula($formula, $context);
        }

        if (isset($formula['switch'])) {
            $this->processSwitchFormula($formula, $context);
        }

        if (isset($formula['scoring'])) {
            $this->processWeightScore($formula, $context);
        }
        
        if (isset($formula['rules'])) {
            $this->processAccumulativeScore($formula, $context);
        }
    }

    /**
     * Process expression-based formula with $ notation support
     */
    private function processExpressionFormula(array $formula, array &$context): void
    {
        try {
            $vars = [];
            
            if (!empty($formula['inputs'])) {
                foreach ($formula['inputs'] as $key) {
                    // Handle both regular inputs and $ notation inputs
                    $contextKey = $this->normalizeVariableName($key);
                    
                    // Try to find the variable in context
                    if (isset($context[$contextKey])) {
                        // Use the normalized key for context lookup, but original key for variable name in expression
                        $vars[$key] = $context[$contextKey];
                    } elseif (isset($context[$key])) {
                        // Fallback: try original key directly
                        $vars[$key] = $context[$key];
                    } else {
                        // Check if it's a $ reference that needs to be resolved
                        if (substr($key, 0, 1) === '$') {
                            $actualKey = substr($key, 1);
                            if (isset($context[$actualKey])) {
                                $vars[$key] = $context[$actualKey];
                            } else {
                                throw new Exception("Missing input: {$key} (normalized: '$contextKey', actual: '$actualKey')");
                            }
                        } else {
                            throw new Exception("Missing input: {$key} (normalized: '$contextKey')");
                        }
                    }
                }
            }

            $result = $this->safeEval($formula['formula'], $vars);
            $storeKey = isset($formula['as']) ? 
                $this->normalizeVariableName($formula['as']) : $formula['id'];
            $context[$storeKey] = $result;
            
            // Debug output (can be removed in production)
            // echo "Formula '{$formula['id']}' result: $result stored as '$storeKey'\n";
            
        } catch (Exception $e) {
            // Add more context to the error
            $availableKeys = array_keys($context);
            throw new Exception("Error evaluating formula '{$formula['id']}': " . $e->getMessage() . 
                            " | Available context keys: " . implode(', ', $availableKeys));
        }
    }

    /**
     * Process switch/case formula
     */
    private function processSwitchFormula(array $formula, array &$context): void
    {
        $switchVar = $this->normalizeVariableName($formula['switch']);
        $switchValue = $context[$switchVar] ?? null;

        if ($switchValue === null) {
            throw new Exception("Switch value '{$formula['switch']}' not found in context");
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
            $storeAs = isset($formula['as']) ? 
                $this->normalizeVariableName($formula['as']) : $formula['id'];
            $value = $context[$storeAs] ?? null;
            if ($value === null) {
                $context[$formula['id']] = 0;
                return;
            }
            
            foreach ($weightScore['ranges'] as $range) {
                if ($this->evaluateCondition($value, $range['if'], $context)) {
                    $context[$formula['id']] = $range['score'];
                    
                    if (isset($range['set_vars'])) {
                        $this->processSetVarsRuntime($range['set_vars'], $context);
                    }
                    return;
                }
            }
            $context[$formula['id']] = $weightScore['default'] ?? 0;
        }
        elseif (isset($weightScore['ifs'])) {
            $result = $this->evaluateMultiConditionScore($weightScore['ifs'], $context);
            $context[$formula['id']] = $result['score'] ?? 0;
            
            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    if ($key !== 'score') {
                        $context[$formula['id'] . '_' . $key] = $value;
                    }
                }
            }
        }
        else {
            $storeAs = isset($formula['as']) ? 
                $this->normalizeVariableName($formula['as']) : $formula['id'];
            $value = $context[$storeAs] ?? null;
            if ($value === null) {
                $context[$formula['id']] = 0;
                return;
            }
            
            if ($this->evaluateCondition($value, $weightScore['if'], $context)) {
                $context[$formula['id']] = $weightScore['score'];
            } else {
                $context[$formula['id']] = 0;
            }
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
        foreach ($setVars as $varName => $varValue) {
            $contextKey = $this->normalizeVariableName($varName);
            
            if (is_string($varValue) && $this->isDollarReference($varValue)) {
                $refKey = $this->normalizeVariableName($varValue);
                $context[$contextKey] = $context[$refKey] ?? null;
            } elseif (is_string($varValue) && $this->isDollarExpression($varValue)) {
                $context[$contextKey] = $this->evaluateDollarExpression($varValue, $context);
            } else {
                $context[$contextKey] = $varValue;
            }
        }
    }

    /**
     * Evaluate $ expression at runtime
     */
    private function evaluateDollarExpression(string $expr, array $context): float
    {
        // Replace $variable with actual values
        $evalExpr = preg_replace_callback('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', function($matches) use ($context) {
            $varName = $matches[1];
            $value = $context[$varName] ?? 0;
            return is_numeric($value) ? (string)$value : '0';
        }, $expr);
        
        // Use safe evaluation
        return $this->safeEval($evalExpr, []);
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
            // Handle $ references in arrays (for between, in operators)
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
            default => throw new Exception("Unsupported operator: {$operator}")
        };
    }

    /**
     * Safely evaluate mathematical expressions
     */
    private function safeEval(string $expr, array $vars): float
    {
        $expr = $this->replaceVariables($expr, $vars);
        $expr = $this->processFunctions($expr, $vars);
        $this->validateFinalExpression($expr);

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

        $argsStr = substr($expr, $startPos, $pos - $startPos - 1);
        $args = $this->parseArguments($argsStr);
        
        $result = $this->callFunction($funcName, $args, $vars);
        
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
        $evaluatedArgs = [];
        foreach ($args as $arg) {
            $arg = trim($arg);
            if (is_numeric($arg)) {
                $evaluatedArgs[] = (float)$arg;
            } else {
                try {
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
        if (preg_match('/[a-zA-Z_]/', $expr)) {
            throw new Exception("Invalid numeric expression: '$expr' contains unresolved variables");
        }

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
        foreach ($this->allowedFunctions as $func) {
            if (strpos($expr, $func) !== false) {
                return;
            }
        }
        
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
                array_pop($stack);
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
     * Validate formula with $ notation support
     */
    private function validateFormula(array $formula, int $index): array
    {
        $errors = [];
        $prefix = "Formula[$index]";

        if (!isset($formula['id'])) {
            $errors[] = "$prefix: Missing required 'id' field";
            return $errors;
        }

        if (!is_string($formula['id']) || empty($formula['id'])) {
            $errors[] = "$prefix: 'id' must be a non-empty string";
        }

        // Validate $ notation in 'as' field
        if (isset($formula['as']) && is_string($formula['as']) && 
            !$this->isValidDollarVariable($formula['as'])) {
            $errors[] = "$prefix: 'as' field must use valid \$ notation (e.g., '\$variable_name')";
        }

        // Validate $ notation in 'switch' field
        if (isset($formula['switch']) && is_string($formula['switch']) && 
            !$this->isValidDollarVariable($formula['switch']) && 
            !$this->isValidInputVariable($formula['switch'])) {
            $errors[] = "$prefix: 'switch' field must reference a valid variable or use \$ notation";
        }

        if (isset($formula['formula'])) {
            $expressionErrors = $this->validateExpressionFormula($formula, $prefix);
            $errors = array_merge($errors, $expressionErrors);
        }

        if (isset($formula['switch'])) {
            $switchErrors = $this->validateSwitchFormula($formula, $prefix);
            $errors = array_merge($errors, $switchErrors);
        }

        if (isset($formula['scoring'])) {
            $scoreErrors = $this->validateWeightScore($formula['scoring'], $prefix);
            $errors = array_merge($errors, $scoreErrors);
        }
        
        if (isset($formula['rules'])) {
            $rulesErrors = $this->validateScoreRules($formula['rules'], $prefix);
            $errors = array_merge($errors, $rulesErrors);
        }

        if (!isset($formula['formula']) && !isset($formula['switch']) && 
            !isset($formula['scoring']) && !isset($formula['rules'])) {
            $errors[] = "$prefix: Formula must have at least one action";
        }

        return $errors;
    }

    /**
     * Check if variable uses valid $ notation
     */
    private function isValidDollarVariable(string $varName): bool
    {
        return preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', $varName) === 1;
    }

    /**
     * Check if variable is a valid input variable (no $ prefix)
     */
    private function isValidInputVariable(string $varName): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $varName) && 
               substr($varName, 0, 1) !== '$';
    }

    /**
     * Validate expression formula
     */
    private function validateExpressionFormula(array $formula, string $prefix): array
    {
        $errors = [];

        if (!isset($formula['formula']) || !is_string($formula['formula']) || trim($formula['formula']) === '') {
            $errors[] = "$prefix: 'formula' must be a non-empty string";
        }

        if (!isset($formula['inputs']) || !is_array($formula['inputs'])) {
            $errors[] = "$prefix: 'inputs' must be an array";
        } elseif (empty($formula['inputs']) && isset($formula['formula']) && $formula['formula'] !== '0') {
            $errors[] = "$prefix: 'inputs' cannot be empty for expression formula";
        }

        return $errors;
    }

    /**
     * Validate switch formula
     */
    private function validateSwitchFormula(array $formula, string $prefix): array
    {
        $errors = [];

        if (!isset($formula['switch']) || !is_string($formula['switch'])) {
            $errors[] = "$prefix: 'switch' must be a string";
        }

        if (!isset($formula['when']) || !is_array($formula['when'])) {
            $errors[] = "$prefix: 'when' must be an array";
        } elseif (empty($formula['when'])) {
            $errors[] = "$prefix: 'when' cannot be empty for switch formula";
        }

        return $errors;
    }

    /**
     * Validate weight score
     */
    private function validateWeightScore(array $weightScore, string $prefix): array
    {
        $errors = [];

        if (isset($weightScore['ifs'])) {
            if (!isset($weightScore['ifs']['vars']) || !is_array($weightScore['ifs']['vars'])) {
                $errors[] = "$prefix.scoring: 'vars' must be an array";
            }
            if (!isset($weightScore['ifs']['tree']) || !is_array($weightScore['ifs']['tree'])) {
                $errors[] = "$prefix.scoring: 'tree' must be an array";
            }
        } elseif (isset($weightScore['ranges'])) {
            if (!is_array($weightScore['ranges']) || empty($weightScore['ranges'])) {
                $errors[] = "$prefix.scoring: 'ranges' must be a non-empty array";
            }
        } else {
            if (!isset($weightScore['if'])) {
                $errors[] = "$prefix.scoring: Missing 'if'";
            }
            if (!isset($weightScore['score'])) {
                $errors[] = "$prefix.scoring: Missing 'score'";
            }
        }

        return $errors;
    }

    /**
     * Validate score rules
     */
    private function validateScoreRules(array $scoreRules, string $prefix): array
    {
        $errors = [];
        
        if (empty($scoreRules)) {
            $errors[] = "$prefix.rules: cannot be empty";
        }
        
        return $errors;
    }

    /**
     * Check for circular dependencies with $ notation support
     */
    private function checkCircularDependencies(array $formulas): array
    {
        $errors = [];
        $dependencies = [];
        $outputs = [];

        foreach ($formulas as $formula) {
            $id = $formula['id'];
            $storeAs = isset($formula['as']) ? 
                $this->normalizeVariableName($formula['as']) : $id;
            $outputs[$id] = $storeAs;

            $deps = [];
            if (isset($formula['inputs'])) {
                $deps = array_merge($deps, $formula['inputs']);
            }
            if (isset($formula['switch'])) {
                $deps[] = $this->normalizeVariableName($formula['switch']);
            }
            if (isset($formula['scoring']['ifs']['vars'])) {
                foreach ($formula['scoring']['ifs']['vars'] as $var) {
                    $deps[] = $this->normalizeVariableName($var);
                }
            }
            if (isset($formula['rules'])) {
                foreach ($formula['rules'] as $rule) {
                    if (isset($rule['var'])) {
                        $deps[] = $this->normalizeVariableName($rule['var']);
                    }
                }
            }
            
            $dependencies[$id] = array_unique($deps);
        }

        foreach ($dependencies as $formulaId => $deps) {
            if ($this->hasCircularDependency($formulaId, $deps, $dependencies, $outputs, [], 0, 100)) {
                $errors[] = "Circular dependency detected involving formula '$formulaId'";
            }
        }

        return $errors;
    }

    /**
     * Helper method to detect circular dependencies with recursion limit
     */
    private function hasCircularDependency(
        string $currentId,
        array $dependencies,
        array $allDependencies,
        array $outputs,
        array $visited,
        int $depth = 0,
        int $maxDepth = 100
    ): bool {
        if ($depth > $maxDepth) {
            return true;
        }
        
        if (in_array($currentId, $visited, true)) {
            return true;
        }

        $visited[] = $currentId;

        foreach ($dependencies as $dep) {
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
                    $visited,
                    $depth + 1,
                    $maxDepth
                )) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check for potential issues and warnings
     */
    private function checkForWarnings(array $config): array
    {
        $warnings = [];

        foreach ($config['formulas'] as $index => $formula) {
            $id = $formula['id'] ?? "Formula[$index]";

            if (isset($formula['as'])) {
                $storeAs = $this->normalizeVariableName($formula['as']);
                $isUsed = false;

                foreach ($config['formulas'] as $otherFormula) {
                    if (isset($otherFormula['inputs']) && in_array($storeAs, $otherFormula['inputs'], true)) {
                        $isUsed = true;
                        break;
                    }
                    if (isset($otherFormula['switch']) && 
                        $this->normalizeVariableName($otherFormula['switch']) === $storeAs) {
                        $isUsed = true;
                        break;
                    }
                }

                if (!$isUsed) {
                    $warnings[] = "Formula '$id' stores value as '{$formula['as']}' but it's never used";
                }
            }

            if (isset($formula['formula']) && strpos($formula['formula'], '/') !== false) {
                $warnings[] = "Formula '$id' contains division - ensure no division by zero";
            }
        }

        return $warnings;
    }
}