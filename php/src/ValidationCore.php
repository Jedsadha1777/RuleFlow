<?php

declare(strict_types=1);

require_once 'ConfigValidator.php';
require_once 'InputValidator.php';
require_once 'SchemaGenerator.php';

/**
 * ValidationCore - รวม config, input, schema validation
 * แยกออกมาจาก ValidationAPI เพื่อไม่ให้ "โยนไปโยนมา"
 */
class ValidationCore
{
    private ConfigValidator $configValidator;
    private InputValidator $inputValidator;
    private SchemaGenerator $schemaGenerator;
    
    // Simple cache
    private array $schemaCache = [];

    public function __construct()
    {
        $this->configValidator = new ConfigValidator();
        $this->inputValidator = new InputValidator();
        $this->schemaGenerator = new SchemaGenerator();
    }

    // ============================================
    // 🔍 CORE VALIDATION METHODS
    // ============================================
    
    /**
     * Validate single field - แค่ validation logic ไม่มี UI concerns
     */
    public function validateSingleField(string $fieldName, $value, array $config): array
    {
        $schema = $this->getFieldSchema($fieldName, $config);
        
        try {
            // Type conversion
            $convertedValue = $this->convertFieldValue($value, $schema);
            
            // Validation
            $errors = $this->performFieldValidation($fieldName, $convertedValue, $schema);
            
            return [
                'field' => $fieldName,
                'value' => $value,
                'converted_value' => $convertedValue,
                'valid' => empty($errors),
                'errors' => $errors
            ];
        } catch (Exception $e) {
            return [
                'field' => $fieldName,
                'value' => $value,
                'converted_value' => $value,
                'valid' => false,
                'errors' => [
                    [
                        'type' => 'VALIDATION_ERROR',
                        'message' => $e->getMessage()
                    ]
                ]
            ];
        }
    }
    
    /**
     * Validate config structure
     */
    public function validateConfig(array $config): array
    {
        try {
            $this->configValidator->validate($config);
            return [
                'valid' => true,
                'errors' => []
            ];
        } catch (RuleFlowException $e) {
            return [
                'valid' => false,
                'errors' => $e->getValidationErrors()
            ];
        }
    }
    
    /**
     * Get field schema with cache
     */
    public function getFieldSchema(string $fieldName, array $config): array
    {
        $cacheKey = md5($fieldName . serialize($config));
        
        if (!isset($this->schemaCache[$cacheKey])) {
            $this->schemaCache[$cacheKey] = $this->generateFieldSchema($fieldName, $config);
        }
        
        return $this->schemaCache[$cacheKey];
    }
    
    /**
     * Extract required inputs from config
     */
    public function getRequiredInputs(array $config): array
    {
        return $this->configValidator->extractRequiredInputs($config);
    }
    
    /**
     * Validate multiple fields at once
     */
    public function validateFields(array $inputs, array $config): array
    {
        $results = [];
        
        foreach ($inputs as $fieldName => $value) {
            $results[$fieldName] = $this->validateSingleField($fieldName, $value, $config);
        }
        
        return $results;
    }
    
    /**
     * Check if all required fields are present and valid
     */
    public function checkCompleteness(array $inputs, array $config): array
    {
        $required = $this->getRequiredInputs($config);
        $provided = array_keys($inputs);
        $missing = array_diff($required, $provided);
        
        $fieldResults = $this->validateFields($inputs, $config);
        $invalidCount = count(array_filter($fieldResults, fn($r) => !$r['valid']));
        
        return [
            'complete' => empty($missing) && $invalidCount === 0,
            'missing_required' => $missing,
            'invalid_count' => $invalidCount,
            'progress_percentage' => count($required) > 0 ? (count($provided) / count($required)) * 100 : 0,
            'field_results' => $fieldResults
        ];
    }

    // ============================================
    // 🔧 PRIVATE HELPER METHODS
    // ============================================
    
    /**
     * Generate field schema
     */
    private function generateFieldSchema(string $fieldName, array $config): array
    {
        // Generate schema for the field from config
        $allSchemas = $this->schemaGenerator->generateInputSchema($config);
        
        return $allSchemas['properties'][$fieldName] ?? [
            'type' => 'number',
            'description' => "Input field: $fieldName"
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
        
        // 🛡️ Input Sanitization ก่อน conversion
        if (is_string($value)) {
            $value = $this->sanitizeInput($value, $schema);
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
     * 🛡️ Sanitize input to prevent XSS and other attacks
     */
    private function sanitizeInput(string $value, array $schema): string
    {
        // Basic HTML/Script tag removal
        $value = strip_tags($value);
        
        // Remove potential XSS patterns
        $xssPatterns = [
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/eval\s*\(/i',
            '/expression\s*\(/i',
            '/vbscript:/i',
            '/data:/i'
        ];
        
        foreach ($xssPatterns as $pattern) {
            $value = preg_replace($pattern, '', $value);
        }
        
        // HTML entities encoding for safety
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        
        // Decode back if it's a safe string type
        if (isset($schema['allow_html']) && $schema['allow_html']) {
            // Only if explicitly allowed
            $value = htmlspecialchars_decode($value, ENT_QUOTES);
        }
        
        return trim($value);
    }
}