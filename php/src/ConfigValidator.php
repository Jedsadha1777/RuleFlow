<?php

declare(strict_types=1);

/**
 * Configuration validation and input handling
 */
class ConfigValidator
{
    /**
     * Enhanced Two-Pass Processing Validation - STEP 3
     * เพิ่มการตรวจสอบและป้องกันปัญหาก่อนที่จะ process
     */
    private function validateTwoPassConfiguration(array $config): array
    {
        $errors = [];
        $warnings = [];
        $setVarsAnalysis = [];
        
        // Analyze all set_vars usage across formulas
        foreach ($config['formulas'] as $formula) {
            $formulaId = $formula['id'];
            
            // Extract all set_vars from this formula
            $formulaSetVars = $this->extractAllSetVars($formula);
            
            foreach ($formulaSetVars as $varName => $varValue) {
                $normalizedVar = $this->normalizeVariableName($varName);
                
                if (!isset($setVarsAnalysis[$normalizedVar])) {
                    $setVarsAnalysis[$normalizedVar] = [
                        'producers' => [],
                        'consumers' => [],
                        'definitions' => []
                    ];
                }
                
                // Track who produces this variable
                $setVarsAnalysis[$normalizedVar]['producers'][] = $formulaId;
                $setVarsAnalysis[$normalizedVar]['definitions'][] = [
                    'formula' => $formulaId,
                    'value' => $varValue,
                    'type' => $this->categorizeSetVarValue($varValue)
                ];
            }
            
            // Track who consumes variables
            $consumed = $this->extractConsumedVariables($formula);
            foreach ($consumed as $consumedVar) {
                $normalizedVar = $this->normalizeVariableName($consumedVar);
                if (isset($setVarsAnalysis[$normalizedVar])) {
                    $setVarsAnalysis[$normalizedVar]['consumers'][] = $formulaId;
                }
            }
        }
        
        // Validation checks
        $errors = array_merge($errors, $this->validateSetVarsIntegrity($setVarsAnalysis));
        $errors = array_merge($errors, $this->validateDependencyChains($config, $setVarsAnalysis));
        $warnings = array_merge($warnings, $this->detectPotentialIssues($setVarsAnalysis));
        
        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'analysis' => $setVarsAnalysis
        ];
    }
    
    /**
     * Extract all set_vars from a formula
     */
    private function extractAllSetVars(array $formula): array
    {
        $allSetVars = [];
        
        // From switch cases
        if (isset($formula['when'])) {
            foreach ($formula['when'] as $case) {
                if (isset($case['set_vars'])) {
                    $allSetVars = array_merge($allSetVars, $case['set_vars']);
                }
            }
        }
        
        // From default_vars
        if (isset($formula['default_vars'])) {
            $allSetVars = array_merge($allSetVars, $formula['default_vars']);
        }
        
        // From scoring ranges
        if (isset($formula['scoring']['ranges'])) {
            foreach ($formula['scoring']['ranges'] as $range) {
                if (isset($range['set_vars'])) {
                    $allSetVars = array_merge($allSetVars, $range['set_vars']);
                }
            }
        }
        
        // From rules
        if (isset($formula['rules'])) {
            foreach ($formula['rules'] as $rule) {
                if (isset($rule['set_vars'])) {
                    $allSetVars = array_merge($allSetVars, $rule['set_vars']);
                }
            }
        }
        
        return $allSetVars;
    }
    
    /**
     * Extract variables consumed by a formula
     */
    private function extractConsumedVariables(array $formula): array
    {
        $consumed = [];
        
        // From inputs
        if (isset($formula['inputs'])) {
            $consumed = array_merge($consumed, $formula['inputs']);
        }
        
        // From switch variable
        if (isset($formula['switch'])) {
            $consumed[] = $formula['switch'];
        }
        
        // From scoring vars
        if (isset($formula['scoring']['ifs']['vars'])) {
            $consumed = array_merge($consumed, $formula['scoring']['ifs']['vars']);
        }
        
        // From rules vars
        if (isset($formula['rules'])) {
            foreach ($formula['rules'] as $rule) {
                if (isset($rule['var'])) {
                    $consumed[] = $rule['var'];
                }
            }
        }
        
        return array_unique($consumed);
    }

    
    
    /**
     * Categorize set_vars value type
     */
    private function categorizeSetVarValue($value): string
    {
        if (!is_string($value)) {
            return 'literal';
        }
        
        if ($this->isDollarReference($value)) {
            return 'reference';
        }
        
        if ($this->isDollarExpression($value)) {
            return 'expression';
        }
        
        return 'literal';
    }
    
    /**
     * Validate set_vars integrity
     */
    private function validateSetVarsIntegrity(array $setVarsAnalysis): array
    {
        $errors = [];
        
        foreach ($setVarsAnalysis as $varName => $analysis) {
            // Check for multiple producers (potential conflicts)
            if (count($analysis['producers']) > 1) {
                $errors[] = "Variable '$varName' is produced by multiple formulas: " . 
                           implode(', ', $analysis['producers']) . 
                           ". This may cause unpredictable behavior.";
            }
            
            // Check for unused variables
            if (empty($analysis['consumers']) && count($analysis['producers']) > 0) {
                $errors[] = "Variable '$varName' is produced but never consumed. " .
                           "Consider removing or using this variable.";
            }
            
            // Check for circular references in expressions
            foreach ($analysis['definitions'] as $def) {
                if ($def['type'] === 'expression') {
                    if ($this->hasCircularReference($varName, $def['value'], $setVarsAnalysis)) {
                        $errors[] = "Circular reference detected: Variable '$varName' depends on itself " .
                                   "through expression '{$def['value']}' in formula '{$def['formula']}'.";
                    }
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate dependency chains
     */
    private function validateDependencyChains(array $config, array $setVarsAnalysis): array
    {
        $errors = [];
        $dependencyGraph = [];
        
        // Build dependency graph including set_vars
        foreach ($config['formulas'] as $formula) {
            $formulaId = $formula['id'];
            $dependencies = [];
            
            // Standard dependencies
            if (isset($formula['inputs'])) {
                foreach ($formula['inputs'] as $input) {
                    $dependencies[] = $this->normalizeVariableName($input);
                }
            }
            
            // Dependencies from consumed set_vars
            foreach ($setVarsAnalysis as $varName => $analysis) {
                if (in_array($formulaId, $analysis['consumers'])) {
                    // Find which formulas produce this variable
                    foreach ($analysis['producers'] as $producer) {
                        if ($producer !== $formulaId) {
                            $dependencies[] = $producer;
                        }
                    }
                }
            }
            
            $dependencyGraph[$formulaId] = array_unique($dependencies);
        }
        
        // Check for complex circular dependencies
        foreach ($dependencyGraph as $formulaId => $deps) {
            if ($this->hasDeepCircularDependency($formulaId, $deps, $dependencyGraph, [], 0, 50)) {
                $errors[] = "Deep circular dependency detected involving formula '$formulaId'. " .
                           "This may cause infinite loops in two-pass processing.";
            }
        }
        
        return $errors;
    }
    
    /**
     * Detect potential issues
     */
    private function detectPotentialIssues(array $setVarsAnalysis): array
    {
        $warnings = [];
        
        foreach ($setVarsAnalysis as $varName => $analysis) {
            // Warn about complex expression chains
            $expressionDepth = $this->calculateExpressionDepth($varName, $setVarsAnalysis);
            if ($expressionDepth > 3) {
                $warnings[] = "Variable '$varName' has complex dependency chain (depth: $expressionDepth). " .
                             "Consider simplifying to improve performance.";
            }
            
            // Warn about potential naming conflicts
            if ($this->isPotentialNamingConflict($varName)) {
                $warnings[] = "Variable '$varName' may conflict with formula IDs or input variables. " .
                             "Consider using more specific naming.";
            }
            
            // Check for performance concerns
            $totalReferences = count($analysis['consumers']);
            if ($totalReferences > 10) {
                $warnings[] = "Variable '$varName' is referenced by many formulas ($totalReferences). " .
                             "Consider optimization if performance is critical.";
            }
        }
        
        return $warnings;
    }
    
    /**
     * Check for circular reference in expression
     */
    private function hasCircularReference(string $varName, string $expression, array $setVarsAnalysis): bool
    {
        // Extract variables from expression
        if (preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $expression, $matches)) {
            foreach ($matches[1] as $referencedVar) {
                if ($referencedVar === $varName) {
                    return true; // Direct circular reference
                }
                
                // Check indirect circular reference
                if (isset($setVarsAnalysis[$referencedVar])) {
                    foreach ($setVarsAnalysis[$referencedVar]['definitions'] as $def) {
                        if ($def['type'] === 'expression') {
                            if ($this->hasCircularReference($varName, $def['value'], $setVarsAnalysis)) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check for deep circular dependency
     */
    private function hasDeepCircularDependency(
        string $currentId,
        array $dependencies,
        array $graph,
        array $visited,
        int $depth,
        int $maxDepth
    ): bool {
        if ($depth > $maxDepth) {
            return true; // Too deep, assume circular
        }
        
        if (in_array($currentId, $visited)) {
            return true; // Found cycle
        }
        
        $visited[] = $currentId;
        
        foreach ($dependencies as $dep) {
            if (isset($graph[$dep])) {
                if ($this->hasDeepCircularDependency($dep, $graph[$dep], $graph, $visited, $depth + 1, $maxDepth)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Calculate expression dependency depth
     */
    private function calculateExpressionDepth(string $varName, array $setVarsAnalysis, array $visited = []): int
    {
        if (in_array($varName, $visited)) {
            return 0; // Circular reference, stop counting
        }
        
        if (!isset($setVarsAnalysis[$varName])) {
            return 0;
        }
        
        $visited[] = $varName;
        $maxDepth = 0;
        
        foreach ($setVarsAnalysis[$varName]['definitions'] as $def) {
            if ($def['type'] === 'expression') {
                if (preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $def['value'], $matches)) {
                    foreach ($matches[1] as $referencedVar) {
                        $depth = 1 + $this->calculateExpressionDepth($referencedVar, $setVarsAnalysis, $visited);
                        $maxDepth = max($maxDepth, $depth);
                    }
                }
            }
        }
        
        return $maxDepth;
    }
    
    /**
     * Check for potential naming conflicts
     */
    private function isPotentialNamingConflict(string $varName): bool
    {
        // Check against common formula IDs and input names
        $commonNames = [
            'result', 'output', 'score', 'total', 'final', 'value',
            'age', 'income', 'price', 'quantity', 'amount', 'rate'
        ];
        
        return in_array($varName, $commonNames);
    }
    
    /**
     * Generate Two-Pass Processing report
     */
    public function generateTwoPassReport(array $config): array
    {
        $validation = $this->validateTwoPassConfiguration($config);
        
        $report = [
            'summary' => [
                'total_set_vars' => count($validation['analysis']),
                'error_count' => count($validation['errors']),
                'warning_count' => count($validation['warnings']),
                'complexity_score' => $this->calculateComplexityScore($validation['analysis']),
                'safety_level' => $this->determineSafetyLevel($validation)
            ],
            'details' => [
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'],
                'variable_analysis' => $validation['analysis']
            ],
            'recommendations' => $this->generateRecommendations($validation),
            'optimizations' => $this->suggestOptimizations($validation['analysis'])
        ];
        
        return $report;
    }
    
    /**
     * Calculate complexity score for two-pass processing
     */
    private function calculateComplexityScore(array $analysis): int
    {
        $score = 0;
        
        foreach ($analysis as $varName => $data) {
            $score += count($data['producers']) * 2;
            $score += count($data['consumers']) * 1;
            
            foreach ($data['definitions'] as $def) {
                if ($def['type'] === 'expression') {
                    $score += 3; // Expressions add complexity
                }
            }
        }
        
        return min(100, $score); // Cap at 100
    }
    
    /**
     * Determine safety level
     */
    private function determineSafetyLevel(array $validation): string
    {
        $errorCount = count($validation['errors']);
        $warningCount = count($validation['warnings']);
        
        if ($errorCount > 0) {
            return 'UNSAFE';
        } elseif ($warningCount > 5) {
            return 'RISKY';
        } elseif ($warningCount > 0) {
            return 'CAUTION';
        } else {
            return 'SAFE';
        }
    }
    
    /**
     * Generate recommendations
     */
    private function generateRecommendations(array $validation): array
    {
        $recommendations = [];
        
        if (count($validation['errors']) > 0) {
            $recommendations[] = "Fix all errors before deploying to production";
            $recommendations[] = "Review circular dependencies and variable conflicts";
        }
        
        if (count($validation['warnings']) > 3) {
            $recommendations[] = "Consider simplifying variable dependency chains";
            $recommendations[] = "Review variable naming conventions";
        }
        
        $complexVars = array_filter($validation['analysis'], function($data) {
            return count($data['definitions']) > 1 || count($data['consumers']) > 5;
        });
        
        if (!empty($complexVars)) {
            $recommendations[] = "Optimize complex variables: " . implode(', ', array_keys($complexVars));
        }
        
        return $recommendations;
    }
    
    /**
     * Suggest optimizations
     */
    private function suggestOptimizations(array $analysis): array
    {
        $optimizations = [];
        
        foreach ($analysis as $varName => $data) {
            if (count($data['consumers']) > 8) {
                $optimizations[] = "Cache variable '$varName' - heavily used (" . count($data['consumers']) . " references)";
            }
            
            if (count($data['definitions']) > 1) {
                $optimizations[] = "Consolidate definitions for variable '$varName' to avoid conflicts";
            }
        }
        
        return $optimizations;
    }

    /**
     * Validate JSON configuration format with $ notation
     */
    public function validate(array $config): array
    {
        $errors = [];

        if (!isset($config['formulas'])) {
            $errors[] = "Missing required 'formulas' key";
        } elseif (!is_array($config['formulas'])) {
            $errors[] = "'formulas' must be an array";
        } else {
            foreach ($config['formulas'] as $index => $formula) {
                $formulaErrors = $this->validateFormula($formula, $index);
                $errors = array_merge($errors, $formulaErrors);
            }

            $dependencyErrors = $this->checkCircularDependencies($config['formulas']);
            $errors = array_merge($errors, $dependencyErrors);
        }

        if (!empty($errors)) {
            throw new RuleFlowException("Configuration validation failed", ['validation_errors' => $errors]);
        }

        return $config;
    }

    /**
     * Validate individual formula
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

        foreach ($formulas as $index => $formula) { 
            $id = $formula['id'] ?? "Formula[$index]";
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
    public function checkForWarnings(array $config): array
    {
        $warnings = [];

        foreach ($config['formulas'] as $index => $formula) {
            // แก้ไข: เช็ค isset ก่อนใช้ $formula['id']
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

    /**
     * Extract required inputs from configuration
     */
    public function extractRequiredInputs(array $config): array
    {
        $requiredInputs = [];
        
        foreach ($config['formulas'] as $formula) {
            if (isset($formula['inputs'])) {
                foreach ($formula['inputs'] as $input) {
                    $normalizedInput = $this->normalizeVariableName($input);
                    $requiredInputs[] = $normalizedInput;
                }
            }
            
            if (isset($formula['switch'])) {
                $switchVar = $this->normalizeVariableName($formula['switch']);
                $requiredInputs[] = $switchVar;
            }
            
            if (isset($formula['rules'])) {
                foreach ($formula['rules'] as $rule) {
                    if (isset($rule['var'])) {
                        $varKey = $this->normalizeVariableName($rule['var']);
                        $requiredInputs[] = $varKey;
                    }
                }
            }
        }
        
        return array_unique($requiredInputs);
    }

    /**
     * Validate and sanitize user inputs
     */
    public function validateInputs(array $requiredInputs, array $userInputs): array
    {
        $errors = [];
        $warnings = [];
        $sanitized = [];
        
        // Check required inputs
        foreach ($requiredInputs as $input) {
            if (!isset($userInputs[$input])) {
                $errors[] = [
                    'field' => $input,
                    'type' => 'MISSING_REQUIRED',
                    'message' => "Field '$input' is required",
                    'suggestion' => "Add '$input' to your input data"
                ];
            } else {
                $value = $userInputs[$input];
                
                // Type validation and conversion
                if ($value === null || $value === '') {
                    $warnings[] = "Field '$input' is empty or null";
                    $sanitized[$input] = null;
                } elseif (!is_numeric($value)) {
                    // Try to convert string numbers
                    $converted = $this->parseNumber($value);
                    if ($converted !== null) {
                        $sanitized[$input] = $converted;
                        if ((string)$converted !== (string)$value) {
                            $warnings[] = "Field '$input' converted from '$value' to '$converted'";
                        }
                    } else {
                        $errors[] = [
                            'field' => $input,
                            'type' => 'INVALID_TYPE',
                            'message' => "Field '$input' must be a number",
                            'current_value' => $value,
                            'suggestion' => "Convert '$input' to numeric value"
                        ];
                    }
                } else {
                    $sanitized[$input] = (float)$value;
                }
            }
        }
        
        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'sanitized' => $sanitized
        ];
    }

    /**
     * Parse string to number
     */
    private function parseNumber($value): ?float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        
        if (is_string($value)) {
            // Remove common non-numeric characters
            $cleaned = preg_replace('/[^\d.-]/', '', $value);
            if (is_numeric($cleaned)) {
                return (float)$cleaned;
            }
        }
        
        return null;
    }

    /**
     * Helper methods
     */
    private function normalizeVariableName(string $varName): string
    {
        return substr($varName, 0, 1) === '$' ? substr($varName, 1) : $varName;
    }

    private function isValidDollarVariable(string $varName): bool
    {
        return preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', $varName) === 1;
    }

    private function isValidInputVariable(string $varName): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $varName) &&  substr($varName, 0, 1) !== '$';
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