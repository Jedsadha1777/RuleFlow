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
            // Remove currency symbols and parentheses first
            $cleaned = preg_replace('/[^\d.,-]/', '', $value);
            
            // 🔧 Handle different currency formats
            
            // European format: 1.234,56 (dot = thousands, comma = decimal)
            if (preg_match('/^\d{1,3}(\.\d{3})*,\d{2}$/', $cleaned)) {
                // Replace dots (thousands separator) and convert comma to dot
                $normalized = str_replace(['.', ','], ['', '.'], $cleaned);
                return (float)$normalized;
            }
            
            // US format: 1,234.56 (comma = thousands, dot = decimal)
            if (preg_match('/^\d{1,3}(,\d{3})*\.\d{2}$/', $cleaned)) {
                // Remove commas (thousands separator)
                $normalized = str_replace(',', '', $cleaned);
                return (float)$normalized;
            }
            
            // Simple format with commas as thousands: 25,000
            if (preg_match('/^\d{1,3}(,\d{3})+$/', $cleaned)) {
                $normalized = str_replace(',', '', $cleaned);
                return (float)$normalized;
            }
            
            // Simple format with dots as thousands: 25.000
            if (preg_match('/^\d{1,3}(\.\d{3})+$/', $cleaned)) {
                $normalized = str_replace('.', '', $cleaned);
                return (float)$normalized;
            }
            
            // Fallback: remove all non-digit characters except last decimal point
            if (preg_match('/\d/', $cleaned)) {
                // Find the last decimal separator
                $lastDot = strrpos($cleaned, '.');
                $lastComma = strrpos($cleaned, ',');
                
                if ($lastDot !== false && $lastComma !== false) {
                    // Both exist - the later one is decimal separator
                    if ($lastDot > $lastComma) {
                        // Dot is decimal separator
                        $normalized = preg_replace('/[^0-9.]/', '', $cleaned);
                        $normalized = preg_replace('/\.(?=.*\.)/', '', $normalized); // Remove all dots except last
                    } else {
                        // Comma is decimal separator
                        $normalized = str_replace(',', '.', preg_replace('/[^0-9,]/', '', $cleaned));
                        $normalized = preg_replace('/\.(?=.*\.)/', '', $normalized); // Remove all dots except last
                    }
                } else {
                    // Only one type exists
                    $normalized = preg_replace('/[^0-9.,-]/', '', $cleaned);
                    $normalized = str_replace(',', '.', $normalized);
                }
                
                if (is_numeric($normalized)) {
                    return (float)$normalized;
                }
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
        
        $digits = preg_replace('/\D/', '', $value);
        
        $length = strlen($digits);
        if ($length < 7 || $length > 15) {
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
                'strip_tags' => is_string($value) ? $this->removeXSS($value) : $value,
                'htmlspecialchars' => is_string($value) ? htmlspecialchars($value) : $value,
                'remove_scripts' => is_string($value) ? preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $value) : $value,
                default => $value
            };
        }
        
        return $value;
    }

    private function removeXSS(string $value): string
    {
        // Remove script tags and their content completely
        $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $value);
        
        // Remove all HTML tags
        $value = strip_tags($value);
        
        // Remove JavaScript patterns
        $xssPatterns = [
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/eval\s*\(/i',
            '/expression\s*\(/i'
        ];
        
        foreach ($xssPatterns as $pattern) {
            $value = preg_replace($pattern, '', $value);
        }
        
        return $value;
    }
}