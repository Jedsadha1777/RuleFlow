<?php


class RuleFlowHelper
{
    
    public static function normalizeVariableName(string $varName): string
    {
        return substr($varName, 0, 1) === '$' ? substr($varName, 1) : $varName;
    }

  
    public static function isDollarReference(string $value): bool
    {
        return preg_match('/^\$[a-zA-Z_][a-zA-Z0-9_]*$/', trim($value)) === 1;
    }

    public static function isDollarExpression(string $value): bool
    {
        $trimmed = trim($value);
        return preg_match('/\$[a-zA-Z_][a-zA-Z0-9_]*/', $trimmed) && 
               !self::isDollarReference($trimmed) &&
               (preg_match('/[\+\-\*\/\(\)\s]/', $trimmed) || 
                preg_match('/\$[a-zA-Z_][a-zA-Z0-9_]*.*\$[a-zA-Z_][a-zA-Z0-9_]*/', $trimmed));
    }

    public static function isValidInputVariable(string $varName): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $varName) &&  substr($varName, 0, 1) !== '$';
    }

}