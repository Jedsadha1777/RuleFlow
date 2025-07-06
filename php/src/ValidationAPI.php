<?php

declare(strict_types=1);

/**
 * Real-time validation API for form fields and partial data
 */
class ValidationAPI
{
    private ConfigValidator $configValidator;
    private InputValidator $inputValidator;
    private SchemaGenerator $schemaGenerator;
    private array $fieldSchemas = [];
    
    public function __construct()
    {
        $this->configValidator = new ConfigValidator();
        $this->inputValidator = new InputValidator();
        $this->schemaGenerator = new SchemaGenerator();
    }
    
    /**
     * Initialize validation for a configuration
     */
    public function initializeValidation(array $config): array
    {
        try {
            $this->configValidator->validate($config);
            $this->fieldSchemas = $this->generateFieldSchemas($config);
            
            return [
                'success' => true,
                'schemas' => $this->fieldSchemas,
                'required_fields' => $this->configValidator->extractRequiredInputs($config)
            ];
        } catch (RuleFlowException $e) {
            return [
                'success' => false,
                'errors' => $e->getValidationErrors(),
                'message' => 'Configuration validation failed'
            ];
        }
    }
    
    /**
     * Validate single field in real-time
     */
    public function validateField(string $fieldName, $value, array $config = []): array
    {
        $result = [
            'field' => $fieldName,
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'suggestions' => [],
            'converted_value' => $value
        ];
        
        try {
            // Get field schema
            $schema = $this->getFieldSchema($fieldName, $config);
            
            // Type conversion
            if (isset($schema['type'])) {
                $result['converted_value'] = $this->convertFieldValue($value, $schema);
            }
            
            // Validation checks
            $errors = $this->performFieldValidation($fieldName, $result['converted_value'], $schema);
            
            if (!empty($errors)) {
                $result['valid'] = false;
                $result['errors'] = $errors;
            }
            
            // Generate suggestions
            $result['suggestions'] = $this->generateFieldSuggestions($fieldName, $value, $schema);
            
            // Generate warnings
            $result['warnings'] = $this->generateFieldWarnings($fieldName, $result['converted_value'], $schema);
            
        } catch (Exception $e) {
            $result['valid'] = false;
            $result['errors'][] = [
                'type' => 'VALIDATION_ERROR',
                'message' => $e->getMessage()
            ];
        }
        
        return $result;
    }
    
    /**
     * Validate partial form data
     */
    public function validatePartial(array $inputs, array $config): array
    {
        $result = [
            'valid' => true,
            'fields' => [],
            'overall_progress' => 0,
            'missing_required' => [],
            'suggestions' => []
        ];
        
        $requiredFields = $this->configValidator->extractRequiredInputs($config);
        $fieldSchemas = $this->generateFieldSchemas($config);
        
        // Validate each provided field
        foreach ($inputs as $field => $value) {
            $fieldResult = $this->validateField($field, $value, $config);
            $result['fields'][$field] = $fieldResult;
            
            if (!$fieldResult['valid']) {
                $result['valid'] = false;
            }
        }
        
        // Check for missing required fields
        foreach ($requiredFields as $field) {
            if (!isset($inputs[$field]) || $inputs[$field] === null || $inputs[$field] === '') {
                $result['missing_required'][] = [
                    'field' => $field,
                    'message' => "Field '$field' is required",
                    'schema' => $fieldSchemas[$field] ?? null
                ];
            }
        }
        
        // Calculate progress
        $providedFields = count($inputs);
        $totalFields = count($requiredFields);
        $result['overall_progress'] = $totalFields > 0 ? ($providedFields / $totalFields) * 100 : 0;
        
        // Generate overall suggestions
        $result['suggestions'] = $this->generateOverallSuggestions($inputs, $config);
        
        return $result;
    }
    
    /**
     * Get field suggestions for autocomplete/dropdown
     */
    public function getFieldSuggestions(string $fieldName, array $config, string $query = ''): array
    {
        $schema = $this->getFieldSchema($fieldName, $config);
        $suggestions = [];
        
        // Enum values
        if (isset($schema['enum'])) {
            $suggestions = array_filter($schema['enum'], function($value) use ($query) {
                return $query === '' || stripos((string)$value, $query) !== false;
            });
        }
        
        // Common patterns based on field name
        $suggestions = array_merge($suggestions, $this->getPatternSuggestions($fieldName, $query));
        
        // Example values
        if (isset($schema['examples'])) {
            $suggestions = array_merge($suggestions, $schema['examples']);
        }
        
        return array_values(array_unique($suggestions));
    }
    
    /**
     * Validate dependencies between fields
     */
    public function validateDependencies(array $inputs, array $config): array
    {
        $result = [
            'valid' => true,
            'dependency_errors' => [],
            'conditional_requirements' => []
        ];
        
        // Analyze field dependencies from configuration
        $dependencies = $this->analyzeDependencies($config);
        
        foreach ($dependencies as $dependent => $requirements) {
            if (isset($inputs[$dependent])) {
                foreach ($requirements as $required) {
                    if (!isset($inputs[$required]) || $inputs[$required] === null) {
                        $result['valid'] = false;
                        $result['dependency_errors'][] = [
                            'dependent_field' => $dependent,
                            'required_field' => $required,
                            'message' => "Field '$required' is required when '$dependent' is provided"
                        ];
                    }
                }
            }
        }
        
        // Check conditional requirements
        $conditionalReqs = $this->getConditionalRequirements($inputs, $config);
        $result['conditional_requirements'] = $conditionalReqs;
        
        return $result;
    }
    
    /**
     * Get real-time validation status
     */
    public function getValidationStatus(array $inputs, array $config): array
    {
        $partialResult = $this->validatePartial($inputs, $config);
        $dependencyResult = $this->validateDependencies($inputs, $config);
        
        $overallValid = $partialResult['valid'] && 
                       $dependencyResult['valid'] && 
                       empty($partialResult['missing_required']);
        
        return [
            'ready_to_submit' => $overallValid,
            'validation_score' => $this->calculateValidationScore($partialResult, $dependencyResult),
            'next_recommended_field' => $this->getNextRecommendedField($inputs, $config),
            'completion_estimate' => $this->estimateCompletionTime($inputs, $config),
            'field_validation' => $partialResult,
            'dependency_validation' => $dependencyResult
        ];
    }
    
    /**
     * Generate live preview of results
     */
    public function generateLivePreview(array $inputs, array $config): array
    {
        try {
            // Fill missing values with defaults or estimates
            $filledInputs = $this->fillMissingValues($inputs, $config);
            
            // Create a temporary RuleFlow instance
            $ruleFlow = new RuleFlow();
            $results = $ruleFlow->evaluate($config, $filledInputs);
            
            return [
                'success' => true,
                'preview_results' => $results,
                'estimated_fields' => array_diff_key($filledInputs, $inputs),
                'confidence' => $this->calculatePreviewConfidence($inputs, $filledInputs)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'preview_results' => null
            ];
        }
    }
    
    /**
     * Get smart validation hints
     */
    public function getValidationHints(string $fieldName, $currentValue, array $config): array
    {
        $hints = [];
        $schema = $this->getFieldSchema($fieldName, $config);
        
        // Range hints
        if (isset($schema['minimum']) || isset($schema['maximum'])) {
            $min = $schema['minimum'] ?? 'no limit';
            $max = $schema['maximum'] ?? 'no limit';
            $hints[] = [
                'type' => 'range',
                'message' => "Valid range: $min to $max",
                'severity' => 'info'
            ];
        }
        
        // Format hints
        if (isset($schema['format'])) {
            $hints[] = [
                'type' => 'format',
                'message' => $this->getFormatHint($schema['format']),
                'severity' => 'info'
            ];
        }
        
        // Value-specific hints
        if ($currentValue !== null && $currentValue !== '') {
            $hints = array_merge($hints, $this->getValueSpecificHints($fieldName, $currentValue, $schema));
        }
        
        // Context hints based on other fields
        $hints = array_merge($hints, $this->getContextualHints($fieldName, $config));
        
        return $hints;
    }
    
    /**
     * Batch validate multiple field updates
     */
    public function batchValidateFields(array $fieldUpdates, array $currentInputs, array $config): array
    {
        $results = [];
        $updatedInputs = array_merge($currentInputs, $fieldUpdates);
        
        foreach ($fieldUpdates as $field => $value) {
            $results[$field] = $this->validateField($field, $value, $config);
        }
        
        // Cross-field validation
        $crossValidation = $this->validateDependencies($updatedInputs, $config);
        
        return [
            'field_results' => $results,
            'cross_validation' => $crossValidation,
            'overall_status' => $this->getValidationStatus($updatedInputs, $config)
        ];
    }
    
    /**
     * Generate field schemas for all inputs
     */
    private function generateFieldSchemas(array $config): array
    {
        $requiredInputs = $this->configValidator->extractRequiredInputs($config);
        $schemas = [];
        
        foreach ($requiredInputs as $input) {
            $schemas[$input] = $this->schemaGenerator->generateInputSchema($config)['properties'][$input] ?? [
                'type' => 'number',
                'description' => "Input field: $input"
            ];
        }
        
        return $schemas;
    }
    
    /**
     * Get field schema
     */
    private function getFieldSchema(string $fieldName, array $config): array
    {
        if (empty($this->fieldSchemas) && !empty($config)) {
            $this->fieldSchemas = $this->generateFieldSchemas($config);
        }
        
        return $this->fieldSchemas[$fieldName] ?? [
            'type' => 'string',
            'description' => "Field: $fieldName"
        ];
    }
    
    /**
     * Convert field value according to schema
     */
    private function convertFieldValue($value, array $schema): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }
        
        $type = $schema['type'] ?? 'string';
        
        try {
            return match($type) {
                'integer' => (int)$value,
                'number' => (float)$value,
                'boolean' => $this->parseBoolean($value),
                'string' => (string)$value,
                default => $value
            };
        } catch (Exception $e) {
            throw new RuleFlowException("Type conversion failed for value '$value' to type '$type'");
        }
    }
    
    /**
     * Parse boolean from various formats
     */
    private function parseBoolean($value): bool
    {
        if (is_bool($value)) return $value;
        
        if (is_string($value)) {
            $lower = strtolower(trim($value));
            return in_array($lower, ['true', '1', 'yes', 'on', 'enabled'], true);
        }
        
        return (bool)$value;
    }
    
    /**
     * Perform field validation
     */
    private function performFieldValidation(string $fieldName, $value, array $schema): array
    {
        $errors = [];
        
        // Required check
        if (($schema['required'] ?? true) && ($value === null || $value === '')) {
            $errors[] = [
                'type' => 'REQUIRED',
                'message' => "Field '$fieldName' is required"
            ];
            return $errors;
        }
        
        // Skip further validation if value is empty and not required
        if ($value === null || $value === '') {
            return $errors;
        }
        
        // Type validation
        if (isset($schema['type'])) {
            $typeError = $this->validateType($value, $schema['type']);
            if ($typeError) {
                $errors[] = $typeError;
            }
        }
        
        // Range validation
        if (is_numeric($value)) {
            if (isset($schema['minimum']) && $value < $schema['minimum']) {
                $errors[] = [
                    'type' => 'MIN_VALUE',
                    'message' => "Value must be at least {$schema['minimum']}"
                ];
            }
            
            if (isset($schema['maximum']) && $value > $schema['maximum']) {
                $errors[] = [
                    'type' => 'MAX_VALUE',
                    'message' => "Value must be at most {$schema['maximum']}"
                ];
            }
        }
        
        // Enum validation
        if (isset($schema['enum']) && !in_array($value, $schema['enum'], true)) {
            $errors[] = [
                'type' => 'INVALID_ENUM',
                'message' => "Value must be one of: " . implode(', ', $schema['enum'])
            ];
        }
        
        // Pattern validation
        if (isset($schema['pattern']) && is_string($value)) {
            if (!preg_match($schema['pattern'], $value)) {
                $errors[] = [
                    'type' => 'PATTERN_MISMATCH',
                    'message' => "Value does not match required pattern"
                ];
            }
        }
        
        // Format validation
        if (isset($schema['format'])) {
            $formatError = $this->validateFormat($value, $schema['format']);
            if ($formatError) {
                $errors[] = $formatError;
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate value type
     */
    private function validateType($value, string $expectedType): ?array
    {
        $actualType = gettype($value);
        
        $valid = match($expectedType) {
            'integer' => is_int($value) || (is_string($value) && ctype_digit($value)),
            'number' => is_numeric($value),
            'boolean' => is_bool($value) || in_array(strtolower((string)$value), ['true', 'false', '1', '0', 'yes', 'no']),
            'string' => is_string($value),
            'array' => is_array($value),
            default => true
        };
        
        if (!$valid) {
            return [
                'type' => 'TYPE_MISMATCH',
                'message' => "Expected $expectedType, got $actualType"
            ];
        }
        
        return null;
    }
    
    /**
     * Validate format
     */
    private function validateFormat($value, string $format): ?array
    {
        $valid = match($format) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'date' => $this->isValidDate($value),
            'uri' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'ipv4' => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false,
            'ipv6' => filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false,
            default => true
        };
        
        if (!$valid) {
            return [
                'type' => 'FORMAT_INVALID',
                'message' => "Invalid $format format"
            ];
        }
        
        return null;
    }
    
    /**
     * Check if string is valid date
     */
    private function isValidDate(string $date): bool
    {
        try {
            new DateTime($date);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Generate field suggestions
     */
    private function generateFieldSuggestions(string $fieldName, $value, array $schema): array
    {
        $suggestions = [];
        
        // Auto-correction suggestions
        if (isset($schema['enum']) && !in_array($value, $schema['enum'], true)) {
            $closest = $this->findClosestMatch($value, $schema['enum']);
            if ($closest) {
                $suggestions[] = [
                    'type' => 'autocorrect',
                    'message' => "Did you mean '$closest'?",
                    'suggested_value' => $closest
                ];
            }
        }
        
        // Formatting suggestions
        if (isset($schema['format'])) {
            $suggestion = $this->getFormatSuggestion($value, $schema['format']);
            if ($suggestion) {
                $suggestions[] = $suggestion;
            }
        }
        
        // Range suggestions
        if (is_numeric($value) && (isset($schema['minimum']) || isset($schema['maximum']))) {
            $rangeSuggestion = $this->getRangeSuggestion($value, $schema);
            if ($rangeSuggestion) {
                $suggestions[] = $rangeSuggestion;
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Find closest match in array
     */
    private function findClosestMatch($value, array $options): ?string
    {
        if (empty($options) || !is_string($value)) {
            return null;
        }
        
        $closest = null;
        $shortestDistance = -1;
        
        foreach ($options as $option) {
            $distance = levenshtein(strtolower((string)$value), strtolower((string)$option));
            
            if ($distance === 0) {
                return $option;
            }
            
            if ($distance <= 3 && ($distance < $shortestDistance || $shortestDistance < 0)) {
                $closest = $option;
                $shortestDistance = $distance;
            }
        }
        
        return $closest;
    }
    
    /**
     * Get format suggestion
     */
    private function getFormatSuggestion($value, string $format): ?array
    {
        return match($format) {
            'email' => $this->getEmailSuggestion($value),
            'date' => $this->getDateSuggestion($value),
            default => null
        };
    }
    
    /**
     * Get email format suggestion
     */
    private function getEmailSuggestion($value): ?array
    {
        if (!is_string($value)) return null;
        
        // Common email corrections
        $corrections = [
            'gmail.co' => 'gmail.com',
            'yahoo.co' => 'yahoo.com',
            'hotmail.co' => 'hotmail.com',
            '@gmial.' => '@gmail.',
            '@yahooo.' => '@yahoo.'
        ];
        
        foreach ($corrections as $wrong => $correct) {
            if (strpos($value, $wrong) !== false) {
                $suggested = str_replace($wrong, $correct, $value);
                return [
                    'type' => 'format_correction',
                    'message' => "Did you mean '$suggested'?",
                    'suggested_value' => $suggested
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Get date format suggestion
     */
    private function getDateSuggestion($value): ?array
    {
        if (!is_string($value)) return null;
        
        // Try to parse and suggest standard format
        $formats = ['Y-m-d', 'm/d/Y', 'd/m/Y', 'Y/m/d'];
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                $standardFormat = $date->format('Y-m-d');
                if ($standardFormat !== $value) {
                    return [
                        'type' => 'format_standardization',
                        'message' => "Suggested standard format: $standardFormat",
                        'suggested_value' => $standardFormat
                    ];
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get range suggestion
     */
    private function getRangeSuggestion($value, array $schema): ?array
    {
        $min = $schema['minimum'] ?? null;
        $max = $schema['maximum'] ?? null;
        
        if ($min !== null && $value < $min) {
            return [
                'type' => 'range_adjustment',
                'message' => "Minimum value is $min",
                'suggested_value' => $min
            ];
        }
        
        if ($max !== null && $value > $max) {
            return [
                'type' => 'range_adjustment',
                'message' => "Maximum value is $max",
                'suggested_value' => $max
            ];
        }
        
        return null;
    }
    
    /**
     * Generate field warnings
     */
    private function generateFieldWarnings(string $fieldName, $value, array $schema): array
    {
        $warnings = [];
        
        // Unusual values
        if (is_numeric($value) && isset($schema['examples'])) {
            $examples = array_filter($schema['examples'], 'is_numeric');
            if (!empty($examples)) {
                $avg = array_sum($examples) / count($examples);
                if (abs($value - $avg) > $avg * 2) {
                    $warnings[] = [
                        'type' => 'unusual_value',
                        'message' => 'This value seems unusually high/low compared to typical values'
                    ];
                }
            }
        }
        
        // Precision warnings
        if (is_float($value) && $value != round($value, 2)) {
            $warnings[] = [
                'type' => 'precision',
                'message' => 'Consider rounding to 2 decimal places for better readability'
            ];
        }
        
        return $warnings;
    }
    
    /**
     * Get pattern suggestions based on field name
     */
    private function getPatternSuggestions(string $fieldName, string $query): array
    {
        $suggestions = [];
        $lowerField = strtolower($fieldName);
        
        // Country suggestions
        if (strpos($lowerField, 'country') !== false) {
            $countries = ['United States', 'Canada', 'United Kingdom', 'Australia', 'Germany', 'France'];
            $suggestions = array_filter($countries, function($country) use ($query) {
                return stripos($country, $query) !== false;
            });
        }
        
        // Currency suggestions
        elseif (strpos($lowerField, 'currency') !== false) {
            $currencies = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD'];
            $suggestions = array_filter($currencies, function($currency) use ($query) {
                return stripos($currency, $query) !== false;
            });
        }
        
        // Status suggestions
        elseif (strpos($lowerField, 'status') !== false) {
            $statuses = ['Active', 'Inactive', 'Pending', 'Completed', 'Cancelled'];
            $suggestions = array_filter($statuses, function($status) use ($query) {
                return stripos($status, $query) !== false;
            });
        }
        
        return array_values($suggestions);
    }
    
    /**
     * Analyze dependencies between fields
     */
    private function analyzeDependencies(array $config): array
    {
        $dependencies = [];
        
        foreach ($config['formulas'] as $formula) {
            if (isset($formula['inputs'])) {
                foreach ($formula['inputs'] as $input) {
                    $normalizedInput = $this->normalizeVariableName($input);
                    if (!isset($dependencies[$normalizedInput])) {
                        $dependencies[$normalizedInput] = [];
                    }
                }
            }
        }
        
        return $dependencies;
    }
    
    /**
     * Get conditional requirements
     */
    private function getConditionalRequirements(array $inputs, array $config): array
    {
        // This would analyze the config to determine conditional field requirements
        // For now, return empty array - can be enhanced based on specific business rules
        return [];
    }
    
    /**
     * Calculate validation score
     */
    private function calculateValidationScore(array $partialResult, array $dependencyResult): float
    {
        $totalFields = count($partialResult['fields']) + count($partialResult['missing_required']);
        if ($totalFields === 0) return 0;
        
        $validFields = count(array_filter($partialResult['fields'], fn($field) => $field['valid']));
        $dependencyPenalty = count($dependencyResult['dependency_errors']) * 0.1;
        
        $score = ($validFields / $totalFields) * 100;
        return max(0, $score - $dependencyPenalty);
    }
    
    /**
     * Get next recommended field
     */
    private function getNextRecommendedField(array $inputs, array $config): ?string
    {
        $requiredFields = $this->configValidator->extractRequiredInputs($config);
        
        foreach ($requiredFields as $field) {
            if (!isset($inputs[$field]) || $inputs[$field] === null || $inputs[$field] === '') {
                return $field;
            }
        }
        
        return null;
    }
    
    /**
     * Estimate completion time
     */
    private function estimateCompletionTime(array $inputs, array $config): array
    {
        $requiredFields = $this->configValidator->extractRequiredInputs($config);
        $remaining = count($requiredFields) - count($inputs);
        
        return [
            'remaining_fields' => $remaining,
            'estimated_minutes' => $remaining * 0.5, // 30 seconds per field
            'completion_percentage' => count($inputs) / count($requiredFields) * 100
        ];
    }
    
    /**
     * Fill missing values with defaults or estimates
     */
    private function fillMissingValues(array $inputs, array $config): array
    {
        $requiredFields = $this->configValidator->extractRequiredInputs($config);
        $fieldSchemas = $this->generateFieldSchemas($config);
        $filled = $inputs;
        
        foreach ($requiredFields as $field) {
            if (!isset($filled[$field]) || $filled[$field] === null || $filled[$field] === '') {
                $schema = $fieldSchemas[$field] ?? [];
                $filled[$field] = $this->getDefaultValue($schema);
            }
        }
        
        return $filled;
    }
    
    /**
     * Get default value for schema
     */
    private function getDefaultValue(array $schema): mixed
    {
        if (isset($schema['default'])) {
            return $schema['default'];
        }
        
        if (isset($schema['examples'][0])) {
            return $schema['examples'][0];
        }
        
        $type = $schema['type'] ?? 'string';
        
        return match($type) {
            'integer' => $schema['minimum'] ?? 0,
            'number' => $schema['minimum'] ?? 0.0,
            'boolean' => false,
            'string' => isset($schema['enum']) ? $schema['enum'][0] : '',
            default => null
        };
    }
    
    /**
     * Calculate preview confidence
     */
    private function calculatePreviewConfidence(array $provided, array $filled): float
    {
        $totalFields = count($filled);
        $providedFields = count($provided);
        
        if ($totalFields === 0) return 0;
        
        return ($providedFields / $totalFields) * 100;
    }
    
    /**
     * Generate overall suggestions
     */
    private function generateOverallSuggestions(array $inputs, array $config): array
    {
        $suggestions = [];
        
        // Suggest completing high-impact fields first
        $requiredFields = $this->configValidator->extractRequiredInputs($config);
        $missingFields = array_diff($requiredFields, array_keys($inputs));
        
        if (!empty($missingFields)) {
            $suggestions[] = [
                'type' => 'completion',
                'message' => 'Complete these fields for full calculation: ' . implode(', ', array_slice($missingFields, 0, 3)),
                'fields' => $missingFields
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * Get format hint message
     */
    private function getFormatHint(string $format): string
    {
        return match($format) {
            'email' => 'Enter a valid email address (e.g., user@example.com)',
            'date' => 'Enter date in YYYY-MM-DD format (e.g., 2024-12-25)',
            'uri' => 'Enter a valid URL (e.g., https://example.com)',
            'ipv4' => 'Enter a valid IPv4 address (e.g., 192.168.1.1)',
            'ipv6' => 'Enter a valid IPv6 address',
            default => "Enter a valid $format"
        };
    }
    
    /**
     * Get value-specific hints
     */
    private function getValueSpecificHints(string $fieldName, $value, array $schema): array
    {
        $hints = [];
        
        // Performance hints for large numbers
        if (is_numeric($value) && $value > 1000000) {
            $hints[] = [
                'type' => 'performance',
                'message' => 'Large numbers may slow down calculations',
                'severity' => 'warning'
            ];
        }
        
        return $hints;
    }
    
    /**
     * Get contextual hints based on configuration
     */
    private function getContextualHints(string $fieldName, array $config): array
    {
        $hints = [];
        
        // Analyze how field is used in formulas
        foreach ($config['formulas'] as $formula) {
            if (isset($formula['inputs']) && in_array($fieldName, $formula['inputs'])) {
                if (isset($formula['formula']) && strpos($formula['formula'], '/') !== false) {
                    $hints[] = [
                        'type' => 'usage',
                        'message' => 'This field is used in division - avoid zero values',
                        'severity' => 'warning'
                    ];
                }
            }
        }
        
        return $hints;
    }
    
    /**
     * Helper methods
     */
    private function normalizeVariableName(string $varName): string
    {
        return substr($varName, 0, 1) === '$' ? substr($varName, 1) : $varName;
    }
}