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
     * Generate executable function from configuration
     */
    public function generateFunction(array $config): Closure
    {
        // Validate configuration first
        $errors = $this->validateConfig($config);
        if (!empty($errors)) {
            throw new Exception("Invalid configuration: " . implode(', ', $errors));
        }

        // Generate optimized PHP code
        $functionCode = $this->generateFunctionCode($config);
        
        // Create and return executable function
        return eval("return $functionCode;");
    }

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
     * Generate expression formula code
     */
    private function generateExpressionCode(array $formula): string
    {
        $expr = $formula['formula'];
        $inputs = $formula['inputs'] ?? [];
        $storeAs = $formula['as'] ?? $formula['id'];
        
        $code = "";
        
        // Input validation
        foreach ($inputs as $input) {
            $code .= "    if (!isset(\$context['$input'])) {\n";
            $code .= "        throw new Exception('Missing input: $input');\n";
            $code .= "    }\n";
        }
        
        // Convert expression to PHP code
        $phpExpr = $this->convertExpressionToPhp($expr, $inputs);
        
        $code .= "    \$context['$storeAs'] = $phpExpr;\n";
        
        return $code;
    }

    /**
     * Generate switch/case code
     */
    private function generateSwitchCode(array $formula): string
    {
        $switchVar = $formula['switch'];
        $id = $formula['id'];
        $cases = $formula['when'];
        $default = $formula['default'] ?? 'null';
        
        $code = "    \$switchValue = \$context['$switchVar'] ?? null;\n";
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
            
            // Handle set_vars
            if (isset($case['set_vars'])) {
                foreach ($case['set_vars'] as $varName => $varValue) {
                    $phpValue = $this->escapePhpValue($varValue);
                    $code .= "        \$context['$varName'] = $phpValue;\n";
                }
            }
        }
        
        // Default case
        $code .= "    } else {\n";
        $code .= "        \$context['$id'] = " . $this->escapePhpValue($default) . ";\n";
        
        // Handle default_vars
        if (isset($formula['default_vars'])) {
            foreach ($formula['default_vars'] as $varName => $varValue) {
                $phpValue = $this->escapePhpValue($varValue);
                $code .= "        \$context['$varName'] = $phpValue;\n";
            }
        }
        
        $code .= "    }\n";
        
        return $code;
    }

    /**
     * Generate score rules code
     */
    private function generateScoreRulesCode(array $formula): string
    {
        $id = $formula['id'];
        $rules = $formula['rules'];
        
        $code = "    \$score = 0;\n";
        
        foreach ($rules as $rule) {
            $variable = $rule['var'];
            $code .= "    \$value = \$context['$variable'] ?? null;\n";
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
                }
                $code .= "        }\n";
            } elseif (isset($rule['if'])) {
                $condition = $this->generateConditionCode('$value', $rule['if']);
                $code .= "        if ($condition) {\n";
                $code .= "            \$score += {$rule['score']};\n";
                $code .= "        }\n";
            }
            
            $code .= "    }\n";
        }
        
        $code .= "    \$context['{$id}_score'] = \$score;\n";
        
        return $code;
    }

    /**
     * Generate weight score code
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
        
        // Fallback for unknown weight score types
        $code = "    // Unknown weight score type - using default\n";
        $code .= "    \$context['{$id}_score'] = 0;\n";
        
        return $code;
    }

    /**
     * Generate optimized multi-condition code
     */
    private function generateMultiConditionCode(array $formula): string
    {
        $id = $formula['id'];
        $multiCondition = $formula['scoring']['ifs'];
        $variables = $multiCondition['vars'];
        $matrix = $multiCondition['tree'];
        
        $code = "    // Multi-condition scoring for {$id}\n";
        
        // Check if all variables exist
        foreach ($variables as $var) {
            $code .= "    if (!isset(\$context['$var'])) {\n";
            $code .= "        \$context['{$id}_score'] = 0;\n";
            $code .= "        return \$context;\n";
            $code .= "    }\n";
        }
        
        // Get variable values
        foreach ($variables as $i => $var) {
            $code .= "    \$var$i = \$context['$var'];\n";
        }
        
        $code .= "    \$matched = false;\n";
        $code .= "\n";
        
        // Generate nested conditions
        $code .= $this->generateMatrixCode($matrix, $variables, 0, $id);
        
        // Default case
        $code .= "    if (!\$matched) {\n";
        $code .= "        \$context['{$id}_score'] = 0;\n";
        $code .= "    }\n";
        
        return $code;
    }

    /**
     * Generate code for matrix navigation
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
                $code .= "        \$context['{$formulaId}_score'] = $score;\n";
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
                    foreach ($item['set_vars'] as $varName => $varValue) {
                        $phpValue = $this->escapePhpValue($varValue);
                        $code .= "        \$context['$varName'] = $phpValue;\n";
                    }
                }
            }
        }
        
        $code .= "    }\n";
        
        return $code;
    }

    /**
     * Generate range-based scoring code
     */
    private function generateRangeBasedScoring(array $formula): string
    {
        $id = $formula['id'];
        $storeAs = $formula['as'] ?? $id;
        $ranges = $formula['scoring']['ranges'];
        $default = $formula['scoring']['default'] ?? 0;
        
        $code = "    \$value = \$context['$storeAs'] ?? null;\n";
        $code .= "    if (\$value === null) {\n";
        $code .= "        \$context['{$id}_score'] = 0;\n";
        $code .= "    } else {\n";
        
        foreach ($ranges as $i => $range) {
            $condition = $this->generateConditionCode('$value', $range['if']);
            
            if ($i === 0) {
                $code .= "        if ($condition) {\n";
            } else {
                $code .= "        } elseif ($condition) {\n";
            }
            
            $code .= "            \$context['{$id}_score'] = {$range['score']};\n";
            
            // Handle set_vars
            if (isset($range['set_vars'])) {
                foreach ($range['set_vars'] as $varName => $varValue) {
                    $phpValue = $this->escapePhpValue($varValue);
                    $code .= "            \$context['$varName'] = $phpValue;\n";
                }
            }
        }
        
        $code .= "        } else {\n";
        $code .= "            \$context['{$id}_score'] = $default;\n";
        $code .= "        }\n";
        $code .= "    }\n";
        
        return $code;
    }

    /**
     * Generate simple weight score code
     */
    private function generateSimpleWeightScore(array $formula): string
    {
        $id = $formula['id'];
        $storeAs = $formula['as'] ?? $id;
        $condition = $formula['scoring']['if'];
        $score = $formula['scoring']['score'];
        
        $code = "    \$value = \$context['$storeAs'] ?? null;\n";
        $code .= "    if (\$value !== null && " . 
                 $this->generateConditionCode('$value', $condition) . ") {\n";
        $code .= "        \$context['{$id}_score'] = $score;\n";
        $code .= "    } else {\n";
        $code .= "        \$context['{$id}_score'] = 0;\n";
        $code .= "    }\n";
        
        return $code;
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
        
        // Replace ** with pow() - handle parentheses correctly
        $expr = preg_replace_callback('/(\([^)]+\)|[^\s\*\(\)]+)\s*\*\*\s*(\([^)]+\)|[^\s\*\(\)]+)/', function($matches) {
            $base = trim($matches[1]);
            $exponent = trim($matches[2]);
            
            // Remove outer parentheses if they exist
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
     * Generate condition code for PHP
     */
    private function generateConditionCode(string $variable, array $condition): string
    {
        $operator = $condition['op'];
        $value = $condition['value'];
        
        return match ($operator) {
            '<' => "$variable < " . $this->formatValue($value),
            '<=' => "$variable <= " . $this->formatValue($value),
            '>' => "$variable > " . $this->formatValue($value),
            '>=' => "$variable >= " . $this->formatValue($value),
            '==' => "$variable == " . $this->escapePhpValue($value),
            '!=' => "$variable != " . $this->escapePhpValue($value),
            'between' => "$variable >= " . $this->formatValue($value[0]) . " && $variable <= " . $this->formatValue($value[1]),
            'in' => "in_array($variable, " . var_export($value, true) . ", true)",
            default => throw new Exception("Unsupported operator: $operator")
        };
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
     * Optimize execution order based on dependencies
     */
    private function optimizeExecutionOrder(array $formulas): array
    {
        // Simple dependency resolution with infinite loop prevention
        $ordered = [];
        $processed = [];
        $dependencies = [];
        $maxIterations = count($formulas) * 2; // Prevent infinite loops
        $iteration = 0;
        
        // Build dependency map
        foreach ($formulas as $formula) {
            $id = $formula['id'];
            $deps = [];
            
            if (isset($formula['inputs'])) {
                $deps = array_merge($deps, $formula['inputs']);
            }
            if (isset($formula['switch'])) {
                $deps[] = $formula['switch'];
            }
            
            $dependencies[$id] = $deps;
        }
        
        // Topological sort with safety check
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
                    // Check if dependency is satisfied by input or processed formula
                    $depSatisfied = false;
                    
                    // Check if it's an input variable (not produced by another formula)
                    $isInput = true;
                    foreach ($formulas as $f) {
                        if (($f['as'] ?? $f['id']) === $dep) {
                            $isInput = false;
                            if (in_array($f['id'], $processed)) {
                                $depSatisfied = true;
                            }
                            break;
                        }
                    }
                    
                    // If it's an input variable, it's always satisfied
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
            
            // If no progress was made in this iteration, we might have circular dependencies
            if (!$progressMade) {
                // Add remaining formulas in original order to prevent infinite loop
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
        if (isset($formula['formula'])) {
            $this->processExpressionFormula($formula, $context);
        }

        // Process switch/case formulas
        if (isset($formula['switch'])) {
            $this->processSwitchFormula($formula, $context);
        }

        // Process weight/score calculation
        if (isset($formula['scoring'])) {
            $this->processWeightScore($formula, $context);
        }
        
        // Process accumulative scoring
        if (isset($formula['rules'])) {
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
            $result = $this->safeEval($formula['formula'], $vars);
            $storeKey = $formula['as'] ?? $formula['id'];
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
        $switchValue = $context[$formula['switch']] ?? null;

        if ($switchValue === null) {
            throw new Exception("Switch value '{$formula['switch']}' not found in context");
        }

        $matched = false;
        foreach ($formula['when'] as $case) {
            if ($this->evaluateCondition($switchValue, $case['if'])) {
                $context[$formula['id']] = $case['result'];
                
                // Set additional variables if specified
                if (isset($case['set_vars'])) {
                    foreach ($case['set_vars'] as $varName => $varValue) {
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
            if (isset($formula['default_vars'])) {
                foreach ($formula['default_vars'] as $varName => $varValue) {
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
        $weightScore = $formula['scoring'];
        
        // รองรับ multiple scoring ranges
        if (isset($weightScore['ranges'])) {
            $value = $context[$formula['as'] ?? $formula['id']] ?? null;
            if ($value === null) {
                $context[$formula['id'] . '_score'] = 0;
                return;
            }
            
            foreach ($weightScore['ranges'] as $range) {
                if ($this->evaluateCondition($value, $range['if'])) {
                    $context[$formula['id'] . '_score'] = $range['score'];
                    
                    // Set additional variables if specified
                    if (isset($range['set_vars'])) {
                        foreach ($range['set_vars'] as $varName => $varValue) {
                            $context[$varName] = $varValue;
                        }
                    }
                    return;
                }
            }
            $context[$formula['id'] . '_score'] = $weightScore['default'] ?? 0;
        }
        // รองรับ multi-variable scoring
        elseif (isset($weightScore['ifs'])) {
            $result = $this->evaluateMultiConditionScore($weightScore['ifs'], $context);
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
            $value = $context[$formula['as'] ?? $formula['id']] ?? null;
            if ($value === null) {
                $context[$formula['id'] . '_score'] = 0;
                return;
            }
            
            if ($this->evaluateCondition($value, $weightScore['if'])) {
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
        $variables = $multiCondition['vars'];
        $matrix = $multiCondition['tree'];
        
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
            if (isset($item['if']) && $this->evaluateCondition($currentValue, $item['if'])) {
                // Set any variables specified at this level
                if (isset($item['set_vars'])) {
                    foreach ($item['set_vars'] as $varName => $varValue) {
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
        
        foreach ($formula['rules'] as $rule) {
            $ruleScore = $this->evaluateScoreRule($rule, $context);
            $score += $ruleScore;
            
            // Set additional variables if specified
            if (isset($rule['set_vars'])) {
                foreach ($rule['set_vars'] as $varName => $varValue) {
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
        $variable = $rule['var'];
        $value = $context[$variable] ?? null;
        
        if ($value === null) {
            return 0;
        }
        
        if (isset($rule['ranges'])) {
            foreach ($rule['ranges'] as $range) {
                if ($this->evaluateCondition($value, $range['if'])) {
                    return $range['score'];
                }
            }
        } elseif (isset($rule['if'])) {
            if ($this->evaluateCondition($value, $rule['if'])) {
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
        $operator = $condition['op'];
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

    // Validation methods continue...
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

    private function validateScoreRules(array $scoreRules, string $prefix): array
    {
        $errors = [];
        
        if (empty($scoreRules)) {
            $errors[] = "$prefix.rules: cannot be empty";
        }
        
        return $errors;
    }

    private function checkCircularDependencies(array $formulas): array
    {
        $errors = [];
        $dependencies = [];
        $outputs = [];

        // Build dependency graph
        foreach ($formulas as $formula) {
            $id = $formula['id'];
            $storeAs = $formula['as'] ?? $id;
            $outputs[$id] = $storeAs;

            $deps = [];
            if (isset($formula['inputs'])) {
                $deps = array_merge($deps, $formula['inputs']);
            }
            if (isset($formula['switch'])) {
                $deps[] = $formula['switch'];
            }
            $dependencies[$id] = $deps;
        }

        // Check for circular dependencies using DFS with recursion limit
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
        // Prevent infinite recursion
        if ($depth > $maxDepth) {
            return true; // Assume circular dependency if too deep
        }
        
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

    private function checkForWarnings(array $config): array
    {
        $warnings = [];

        foreach ($config['formulas'] as $index => $formula) {
            $id = $formula['id'] ?? "Formula[$index]";

            // Check for unused stored values
            if (isset($formula['as'])) {
                $storeAs = $formula['as'];
                $isUsed = false;

                foreach ($config['formulas'] as $otherFormula) {
                    if (isset($otherFormula['inputs']) && in_array($storeAs, $otherFormula['inputs'], true)) {
                        $isUsed = true;
                        break;
                    }
                    if (isset($otherFormula['switch']) && $otherFormula['switch'] === $storeAs) {
                        $isUsed = true;
                        break;
                    }
                }

                if (!$isUsed) {
                    $warnings[] = "Formula '$id' stores value as '$storeAs' but it's never used";
                }
            }

            // Check for potential division by zero
            if (isset($formula['formula']) && strpos($formula['formula'], '/') !== false) {
                $warnings[] = "Formula '$id' contains division - ensure no division by zero";
            }
        }

        return $warnings;
    }
}