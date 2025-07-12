<?php

declare(strict_types=1);

require_once 'ValidationCore.php';

/**
 * ValidationAPI - สำหรับ UI interactions, progressive validation
 * ใช้ ValidationCore ทำงานหนัก ส่วนตัวนี้จัดการ UX concerns
 */
class ValidationAPI
{
    private ValidationCore $core;
    
    public function __construct()
    {
        $this->core = new ValidationCore();
    }

    // ============================================
    // UI-FOCUSED METHODS
    // ============================================
    
    /**
     * Initialize validation for a configuration
     */
    public function initializeValidation(array $config): array
    {
        $configValidation = $this->core->validateConfig($config);
        
        if (!$configValidation['valid']) {
            return [
                'success' => false,
                'errors' => $configValidation['errors'],
                'message' => 'Configuration validation failed'
            ];
        }
        
        $requiredFields = $this->core->getRequiredInputs($config);
        $schemas = [];
        
        foreach ($requiredFields as $field) {
            $schemas[$field] = $this->core->getFieldSchema($field, $config);
        }
        
        return [
            'success' => true,
            'schemas' => $schemas,
            'required_fields' => $requiredFields
        ];
    }
    
    /**
     * Validate field สำหรับ real-time validation ใน form
     */
    public function validateField(string $fieldName, $value, array $config): array
    {
        $coreResult = $this->core->validateSingleField($fieldName, $value, $config);
        $schema = $this->core->getFieldSchema($fieldName, $config);
        
        return [
            'field' => $fieldName,
            'valid' => $coreResult['valid'],
            'errors' => $coreResult['errors'],
            'warnings' => $this->getFieldWarnings($value, $schema),
            'suggestions' => $this->generateFieldSuggestions($fieldName, $value, $schema),
            'converted_value' => $coreResult['converted_value']
        ];
    }
    
    /**
     * Progressive validation สำหรับ multi-step forms
     */
    public function validatePartial(array $inputs, array $config): array
    {
        $completeness = $this->core->checkCompleteness($inputs, $config);
        
        return [
            'valid' => $completeness['complete'],
            'overall_progress' => $completeness['progress_percentage'],
            'missing_required' => $completeness['missing_required'],
            'fields' => $completeness['field_results'],
            'suggestions' => $this->generateOverallSuggestions($inputs, $config)
        ];
    }
    
    /**
     * Get validation status with progress
     */
    public function getValidationStatus(array $inputs, array $config): array
    {
        $completeness = $this->core->checkCompleteness($inputs, $config);
        $dependencies = $this->validateDependencies($inputs, $config);
        
        $readyToSubmit = $completeness['complete'] && $dependencies['valid'];
        
        return [
            'ready_to_submit' => $readyToSubmit,
            'validation_score' => $this->calculateValidationScore($completeness, $dependencies),
            'next_recommended_field' => $this->getNextRecommendedField($inputs, $config),
            'completion_estimate' => $this->estimateCompletionTime($inputs, $config),
            'field_validation' => $completeness,
            'dependency_validation' => $dependencies
        ];
    }
    
    /**
     * Generate live preview of results
     */
    public function generateLivePreview(array $inputs, array $config): array
    {
        try {
            // Fill missing values with defaults
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
     * Get suggestions สำหรับ autocomplete
     */
    public function getFieldSuggestions(string $fieldName, array $config, string $query = ''): array
    {
        $schema = $this->core->getFieldSchema($fieldName, $config);
        return $this->generateFieldSuggestions($fieldName, $query, $schema);
    }
    
    /**
     * Batch validate multiple fields
     */
    public function batchValidateFields(array $fieldUpdates, array $currentInputs, array $config): array
    {
        $results = [];
        $updatedInputs = array_merge($currentInputs, $fieldUpdates);
        
        foreach ($fieldUpdates as $field => $value) {
            $results[$field] = $this->validateField($field, $value, $config);
        }
        
        $crossValidation = $this->validateDependencies($updatedInputs, $config);
        
        return [
            'field_results' => $results,
            'cross_validation' => $crossValidation,
            'overall_status' => $this->getValidationStatus($updatedInputs, $config)
        ];
    }

    // ============================================
    // 🛠️ UI HELPER METHODS
    // ============================================
    
    /**
     * Generate field suggestions (private helper)
     */
    private function generateFieldSuggestions(string $fieldName, $currentValue, array $schema): array
    {
        $suggestions = [];
        
        // Enum values
        if (isset($schema['enum'])) {
            $suggestions = array_filter($schema['enum'], function($value) use ($currentValue) {
                return empty($currentValue) || stripos((string)$value, (string)$currentValue) !== false;
            });
        }
        
        // Auto-correction suggestions
        if (isset($schema['enum']) && !in_array($currentValue, $schema['enum'], true)) {
            $closest = $this->findClosestMatch($currentValue, $schema['enum']);
            if ($closest) {
                $suggestions = array_merge($suggestions, [$closest]);
            }
        }
        
        // Common patterns
        $suggestions = array_merge($suggestions, $this->getCommonPatterns($fieldName, $currentValue));
        
        return array_values(array_unique($suggestions));
    }
    
    /**
     * Get field warnings
     */
    private function getFieldWarnings($value, array $schema): array
    {
        $warnings = [];
        
        // Check recommended ranges
        if (isset($schema['recommended_range']) && is_numeric($value)) {
            $min = $schema['recommended_range']['min'] ?? null;
            $max = $schema['recommended_range']['max'] ?? null;
            
            if ($min && $value < $min) {
                $warnings[] = "Value below recommended minimum of {$min}";
            }
            if ($max && $value > $max) {
                $warnings[] = "Value above recommended maximum of {$max}";
            }
        }
        
        // Unusual values warning
        if (is_numeric($value) && isset($schema['examples'])) {
            $examples = array_filter($schema['examples'], 'is_numeric');
            if (!empty($examples)) {
                $avg = array_sum($examples) / count($examples);
                if (abs($value - $avg) > $avg * 2) {
                    $warnings[] = 'This value seems unusually high/low compared to typical values';
                }
            }
        }
        
        return $warnings;
    }
    
    /**
     * Validate dependencies between fields
     */
    private function validateDependencies(array $inputs, array $config): array
    {
        $result = [
            'valid' => true,
            'dependency_errors' => []
        ];
        
        // Basic dependency analysis - can be enhanced
        // For now, just check if all required inputs are present
        $required = $this->core->getRequiredInputs($config);
        foreach ($required as $field) {
            if (!isset($inputs[$field]) || $inputs[$field] === null || $inputs[$field] === '') {
                // This is handled elsewhere, so dependencies are OK
            }
        }
        
        return $result;
    }
    
    /**
     * Calculate validation score
     */
    private function calculateValidationScore(array $completeness, array $dependencies): float
    {
        $baseScore = $completeness['progress_percentage'];
        $penalty = count($dependencies['dependency_errors']) * 10;
        
        return max(0, $baseScore - $penalty);
    }
    
    /**
     * Get next recommended field
     */
    private function getNextRecommendedField(array $inputs, array $config): ?string
    {
        $required = $this->core->getRequiredInputs($config);
        
        foreach ($required as $field) {
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
        $required = $this->core->getRequiredInputs($config);
        $remaining = count($required) - count($inputs);
        
        return [
            'remaining_fields' => $remaining,
            'estimated_minutes' => $remaining * 0.5, // 30 seconds per field
            'completion_percentage' => count($inputs) / count($required) * 100
        ];
    }
    
    /**
     * Fill missing values with defaults
     */
    private function fillMissingValues(array $inputs, array $config): array
    {
        $required = $this->core->getRequiredInputs($config);
        $filled = $inputs;
        
        foreach ($required as $field) {
            if (!isset($filled[$field]) || $filled[$field] === null || $filled[$field] === '') {
                $schema = $this->core->getFieldSchema($field, $config);
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
        
        $required = $this->core->getRequiredInputs($config);
        $missing = array_diff($required, array_keys($inputs));
        
        if (!empty($missing)) {
            $suggestions[] = [
                'type' => 'completion',
                'message' => 'Complete these fields: ' . implode(', ', array_slice($missing, 0, 3)),
                'fields' => $missing
            ];
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
     * Get common patterns based on field name
     */
    private function getCommonPatterns(string $fieldName, $currentValue): array
    {
        $fieldLower = strtolower($fieldName);
        $suggestions = [];
        
        if (strpos($fieldLower, 'country') !== false) {
            $countries = ['Thailand', 'United States', 'Japan', 'Singapore'];
            $suggestions = array_filter($countries, function($country) use ($currentValue) {
                return empty($currentValue) || stripos($country, (string)$currentValue) !== false;
            });
        }
        
        if (strpos($fieldLower, 'currency') !== false) {
            $currencies = ['THB', 'USD', 'EUR', 'JPY'];
            $suggestions = array_filter($currencies, function($currency) use ($currentValue) {
                return empty($currentValue) || stripos($currency, (string)$currentValue) !== false;
            });
        }
        
        return array_values($suggestions);
    }
}