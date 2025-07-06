<?php

declare(strict_types=1);

/**
 * Enhanced Input Validator with advanced features
 */
class InputValidator
{
    /**
     * Validate input boundaries and constraints
     */

    public function validateBoundaries(array $inputs, array $constraints): array
    {
        $errors = [];
        
        foreach ($constraints as $field => $rules) {
            if (!isset($inputs[$field])) continue;
            
            $value = $inputs[$field];
            
            // Numeric boundaries
            if (isset($rules['min']) && is_numeric($value) && $value < $rules['min']) {
                $errors[] = [
                    'field' => $field,
                    'type' => 'BOUNDARY_ERROR', 
                    'message' => "Field '$field' must be >= {$rules['min']}",
                    'current_value' => $value,
                    'constraint' => 'minimum'
                ];
            }
            
            if (isset($rules['max']) && is_numeric($value) && $value > $rules['max']) {
                $errors[] = [
                    'field' => $field,
                    'type' => 'BOUNDARY_ERROR',
                    'message' => "Field '$field' must be <= {$rules['max']}",
                    'current_value' => $value,
                    'constraint' => 'maximum'
                ];
            }
            
            // String length
            if (isset($rules['minLength']) && is_string($value) && strlen($value) < $rules['minLength']) {
                $errors[] = [
                    'field' => $field,
                    'type' => 'LENGTH_ERROR',
                    'message' => "Field '$field' must be at least {$rules['minLength']} characters",
                    'current_length' => strlen($value)
                ];
            }
            
            // Enum validation
            if (isset($rules['enum']) && !in_array($value, $rules['enum'], true)) {
                $errors[] = [
                    'field' => $field,
                    'type' => 'ENUM_ERROR',
                    'message' => "Field '$field' must be one of: " . implode(', ', $rules['enum']),
                    'current_value' => $value,
                    'allowed_values' => $rules['enum']
                ];
            }
            
            // Pattern validation
            if (isset($rules['pattern']) && is_string($value) && !preg_match($rules['pattern'], $value)) {
                $errors[] = [
                    'field' => $field,
                    'type' => 'PATTERN_ERROR',
                    'message' => "Field '$field' does not match required pattern",
                    'current_value' => $value,
                    'pattern' => $rules['pattern']
                ];
            }
        }
        
        return $errors;
    }
    
    /**
     * Advanced type conversion
     */
    public function convertAdvancedTypes(array $inputs, array $typeMap): array
    {
        $converted = [];
        
        foreach ($inputs as $field => $value) {
            $targetType = $typeMap[$field] ?? 'auto';
            
            try {
                $converted[$field] = $this->convertSingleValue($value, $targetType);
            } catch (Exception $e) {
                throw new RuleFlowException("Type conversion failed for field '$field': " . $e->getMessage());
            }
        }
        
        return $converted;
    }
    
    private function convertSingleValue($value, string $targetType): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        return match($targetType) {
            'integer', 'int' => $this->parseInteger($value),
            'float', 'number' => $this->parseFloat($value),
            'boolean', 'bool' => $this->parseBoolean($value),
            'date' => $this->parseDate($value),
            'percentage' => $this->parsePercentage($value),
            'currency' => $this->parseCurrency($value),
            'email' => $this->validateEmail($value),
            'phone' => $this->formatPhone($value),
            'string' => (string)$value,
            'auto' => $this->autoDetectType($value),
            default => $value
        };
    }
    
    private function parseInteger($value): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        
        if (is_string($value)) {
            $cleaned = preg_replace('/[^\d-]/', '', $value);
            if (is_numeric($cleaned)) {
                return (int)$cleaned;
            }
        }
        
        throw new Exception("Cannot convert '$value' to integer");
    }
    
    private function parseFloat($value): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        
        if (is_string($value)) {
            $cleaned = preg_replace('/[^\d.-]/', '', $value);
            if (is_numeric($cleaned)) {
                return (float)$cleaned;
            }
        }
        
        throw new Exception("Cannot convert '$value' to float");
    }
    
    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $lower = strtolower(trim($value));
            return in_array($lower, ['true', '1', 'yes', 'on', 'enabled'], true);
        }
        
        return (bool)$value;
    }
    
    private function parseDate($value): ?DateTime
    {
        if ($value instanceof DateTime) {
            return $value;
        }
        
        if (is_string($value)) {
            try {
                return new DateTime($value);
            } catch (Exception $e) {
                throw new Exception("Invalid date format: '$value'");
            }
        }
        
        if (is_numeric($value)) {
            return new DateTime("@$value"); // Unix timestamp
        }
        
        throw new Exception("Cannot convert '$value' to date");
    }
    
    private function parsePercentage($value): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        
        if (is_string($value) && str_ends_with($value, '%')) {
            $number = substr($value, 0, -1);
            if (is_numeric($number)) {
                return (float)$number;
            }
        }
        
        throw new Exception("Invalid percentage format: '$value'");
    }
    
    private function parseCurrency($value): float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        
        if (is_string($value)) {
            // Remove currency symbols and commas
            $cleaned = preg_replace('/[^\d.-]/', '', $value);
            if (is_numeric($cleaned)) {
                return (float)$cleaned;
            }
        }
        
        throw new Exception("Invalid currency format: '$value'");
    }
    
    private function validateEmail($value): string
    {
        if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format: '$value'");
        }
        
        return strtolower(trim($value));
    }
    
    private function formatPhone($value): string
    {
        if (!is_string($value)) {
            $value = (string)$value;
        }
        
        // Remove all non-digits
        $digits = preg_replace('/\D/', '', $value);
        
        // Basic validation (at least 10 digits)
        if (strlen($digits) < 10) {
            throw new Exception("Invalid phone number: '$value'");
        }
        
        return $digits;
    }
    
    private function autoDetectType($value): mixed
    {
        // Try to detect the best type automatically
        if (is_numeric($value)) {
            return str_contains((string)$value, '.') ? (float)$value : (int)$value;
        }
        
        if (is_string($value)) {
            $lower = strtolower(trim($value));
            if (in_array($lower, ['true', 'false', 'yes', 'no'], true)) {
                return $this->parseBoolean($value);
            }
        }
        
        return $value;
    }
    
    /**
     * Apply default values to missing inputs
     */
    public function applyDefaults(array $inputs, array $defaults): array
    {
        foreach ($defaults as $field => $defaultValue) {
            if (!isset($inputs[$field]) || $inputs[$field] === null || $inputs[$field] === '') {
                $inputs[$field] = $defaultValue;
            }
        }
        
        return $inputs;
    }
    
    /**
     * Sanitize input values
     */
    public function sanitizeInputs(array $inputs, array $sanitizationRules = []): array
    {
        $sanitized = [];
        
        foreach ($inputs as $field => $value) {
            $rules = $sanitizationRules[$field] ?? ['trim'];
            $sanitized[$field] = $this->applySanitization($value, $rules);
        }
        
        return $sanitized;
    }
    
    private function applySanitization($value, array $rules): mixed
    {
        foreach ($rules as $rule) {
            $value = match($rule) {
                'trim' => is_string($value) ? trim($value) : $value,
                'lowercase' => is_string($value) ? strtolower($value) : $value,
                'uppercase' => is_string($value) ? strtoupper($value) : $value,
                'strip_tags' => is_string($value) ? strip_tags($value) : $value,
                'htmlspecialchars' => is_string($value) ? htmlspecialchars($value) : $value,
                default => $value
            };
        }
        
        return $value;
    }
}