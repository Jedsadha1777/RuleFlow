<?php 

declare(strict_types=1);
require_once __DIR__ . '/RuleFlowHelper.php';

class CodeTemplates
{
    /**
     * Format value for PHP code generation
     */
    private static function formatValue($value): string
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
    public static function escapePhpValue($value): string
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
     * Generate condition code with $ notation support
     */
    public static function generateConditionCode(string $variable, $condition): string
    {
        if (is_array($condition)) {
            if (isset($condition['and'])) {
                $subConditions = [];
                foreach ($condition['and'] as $subCondition) {
                    $subConditions[] = self::generateConditionCode($variable, $subCondition);
                }
                return '(' . implode(' && ', $subConditions) . ')';
            }
            
            if (isset($condition['or'])) {
                $subConditions = [];
                foreach ($condition['or'] as $subCondition) {
                    $subConditions[] = self::generateConditionCode($variable, $subCondition);
                }
                return '(' . implode(' || ', $subConditions) . ')';
            }
            
            // Handle variable reference in condition
            if (isset($condition['var'])) {
                $varKey = RuleFlowHelper::normalizeVariableName($condition['var']);
                $variable = "\$context['$varKey']";
            }
            
            // Handle original single condition logic
            $operator = $condition['op'] ?? '==';
            $value = $condition['value'] ?? null;
        
            return match ($operator) {
                '<' => "$variable < " . self::formatConditionValue($value),
                '<=' => "$variable <= " . self::formatConditionValue($value),
                '>' => "$variable > " . self::formatConditionValue($value),
                '>=' => "$variable >= " . self::formatConditionValue($value),
                '==' => "$variable == " . self::escapePhpValue($value),
                '!=' => "$variable != " . self::escapePhpValue($value),
                'between' => self::generateBetweenCondition($variable, $value),
                'in' => "in_array($variable, " . var_export($value, true) . ", true)",
                'not_in' => "!in_array($variable, " . var_export($value, true) . ", true)",
                'contains' => "strpos($variable, " . self::escapePhpValue($value) . ") !== false",
                'starts_with' => "str_starts_with($variable, " . self::escapePhpValue($value) . ")",
                'ends_with' => "str_ends_with($variable, " . self::escapePhpValue($value) . ")",
                default => throw new RuleFlowException("Unsupported operator: $operator")
            };
        }
        
        // Fallback for simple values
        return "$variable == " . self::escapePhpValue($condition);
    }

    /**
     * Format condition value with $ notation support
     */
    private static function formatConditionValue($value): string
    {
        if (is_string($value) && RuleFlowHelper::isDollarReference($value)) {
            $valueKey = RuleFlowHelper::normalizeVariableName($value);
            return "\$context['$valueKey']";
        }
        
        return self::formatValue($value);
    }

    /**
     * Generate between condition with $ notation support
    */
    private static function generateBetweenCondition(string $variable, $value): string
    {
        if (is_array($value) && count($value) === 2) {
            $min = self::formatConditionValue($value[0]);
            $max = self::formatConditionValue($value[1]);
            
            return "$variable >= $min && $variable <= $max";
        }
        
        throw new RuleFlowException("Between operator requires array with 2 values");
    }
}