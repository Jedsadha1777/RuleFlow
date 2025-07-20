<?php
declare(strict_types=1);

require_once __DIR__ . '/ExpressionParser.php';

class ExpressionEvaluator
{
    private FunctionRegistry $functions;
    
    private ?int $autoRoundPrecision = 10; // Default precision 10 decimal places
    private float $autoRoundThreshold = 1e-10; // Threshold for detecting precision issues

    public function getFunctionRegistry(): FunctionRegistry
    {
        return $this->functions;
    }
    
    public function __construct(FunctionRegistry $functions)
    {
        $this->functions = $functions;
    }

    public function setAutoRounding(int $precision = 10): void
    {
        $this->autoRoundPrecision = $precision;
    }

    public function disableAutoRounding(): void
    {
        $this->autoRoundPrecision = null;
    }

    /**
     * Safely evaluate mathematical expressions
     */
    public function safeEval(string $expr, array $vars): float
    {
        $expr = ExpressionParser::replaceVariables($expr, $vars);
        $expr = $this->processFunctions($expr);
        $this->validateFinalExpression($expr);

        $tokens = ExpressionParser::tokenize($expr);
        $tokens = ExpressionParser::processUnaryOperators($tokens);
        $postfix = ExpressionParser::convertToPostfix($tokens);
        
        $result = $this->evaluatePostfix($postfix);
        
        // 🎯 Apply automatic rounding to final result
        $result = $this->applyAutoRounding($result);
        
        // Ensure result is proper numeric type
        return $this->convertToNumericType($result);
    }

    // 🆕 Core automatic rounding logic
    private function applyAutoRounding(float $value): float
    {
        if ($this->autoRoundPrecision === null || !is_finite($value)) {
            return $value;
        }

        // Calculate rounded value
        $factor = pow(10, $this->autoRoundPrecision);
        $rounded = round($value * $factor) / $factor;
        $difference = abs($value - $rounded);
        
        // If difference is very small (floating point precision issue), return rounded value
        if ($difference < $this->autoRoundThreshold) {
            return $rounded;
        }
        
        return $value;
    }

    /**
     * Convert result to proper numeric type
     */
    private function convertToNumericType($value): float
    {
        if (is_string($value) && is_numeric($value)) {
            return (float)$value;
        }
        
        if (is_int($value)) {
            return (float)$value;
        }
        
        if (is_float($value)) {
            return $value;
        }
        
        // Fallback for any other type
        return (float)$value;
    }

    /**
     * Evaluate $ expression at runtime
     */
    public function evaluateDollarExpression(string $expr, array $context): float
    {
        // Replace $variable with actual values
        $evalExpr = preg_replace_callback('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', function($matches) use ($context) {
            $varName = $matches[1];
            $value = $context[$varName] ?? 0;
            return is_numeric($value) ? (string)$value : '0';
        }, $expr);
        
        $result = $this->safeEval($evalExpr, []);
        return $this->convertToNumericType($result);
    }

    /**
     * Process function calls in expression
     */
    private function processFunctions(string $expr): string
    {
        $availableFunctions = $this->functions->getAvailableFunctions();
        
        foreach ($availableFunctions as $func) {
            while (preg_match('/\b' . $func . '\s*\(/', $expr)) {
                $expr = $this->processFunction($expr, $func);
            }
        }

        return $expr;
    }

    /**
     * Process a single function call with proper parentheses matching
     */
    private function processFunction(string $expr, string $funcName): string
    {
        $pattern = '/\b' . $funcName . '\s*\(/';
        
        if (!preg_match($pattern, $expr, $matches, PREG_OFFSET_CAPTURE)) {
            return $expr;
        }
        
        $startPos = (int)$matches[0][1];
        $openParenPos = $startPos + strlen($matches[0][0]) - 1;
        
        // 🔧 แก้ไข: Find matching closing parenthesis correctly
        $parenCount = 1;
        $pos = $openParenPos + 1;
        $endPos = strlen($expr);
        
        while ($pos < $endPos && $parenCount > 0) {
            if ($expr[$pos] === '(') {
                $parenCount++;
            } elseif ($expr[$pos] === ')') {
                $parenCount--;
            }
            $pos++;
        }
        
        if ($parenCount !== 0) {
            throw new RuleFlowException("Unmatched parentheses in function call: $funcName");
        }
        
        // Extract complete function call and arguments
        $funcCallLength = $pos - $startPos;
        $argsString = substr($expr, $openParenPos + 1, $pos - $openParenPos - 2);
        
        // Parse and evaluate arguments
        $args = $this->parseArguments($argsString);
        
        try {
            $result = $this->functions->call($funcName, $args);
            $result = $this->applyAutoRounding($result);
            
            return substr_replace($expr, (string)$result, $startPos, $funcCallLength);
        } catch (Exception $e) {
            throw new RuleFlowException("Function '$funcName' call failed: " . $e->getMessage());
        }
    }

    /**
     * Parse function arguments with support for nested functions and expressions
     */
    private function parseArguments(string $argsString): array
    {
        if (trim($argsString) === '') {
            return [];
        }
        
        $args = [];
        $currentArg = '';
        $parenDepth = 0;
        $inQuotes = false;
        $quoteChar = '';
        
        for ($i = 0; $i < strlen($argsString); $i++) {
            $char = $argsString[$i];
            
            if (!$inQuotes) {
                if ($char === '"' || $char === "'") {
                    $inQuotes = true;
                    $quoteChar = $char;
                    $currentArg .= $char;
                } elseif ($char === '(') {
                    $parenDepth++;
                    $currentArg .= $char;
                } elseif ($char === ')') {
                    $parenDepth--;
                    $currentArg .= $char;
                } elseif ($char === ',' && $parenDepth === 0) {
                    $args[] = $this->evaluateArgument(trim($currentArg));
                    $currentArg = '';
                } else {
                    $currentArg .= $char;
                }
            } else {
                $currentArg .= $char;
                if ($char === $quoteChar && ($i === 0 || $argsString[$i - 1] !== '\\')) {
                    $inQuotes = false;
                    $quoteChar = '';
                }
            }
        }
        
        if ($currentArg !== '') {
            $args[] = $this->evaluateArgument(trim($currentArg));
        }
        
        return $args;
    }

    /**
     * Evaluate a single function argument
     */
    private function evaluateArgument(string $arg): mixed
    {
        // Check if it's a string literal
        if (preg_match('/^["\'](.*)["\']\s*$/', $arg, $matches)) {
            return $matches[1];
        }
        
        // Check if it's a boolean
        if ($arg === 'true') return true;
        if ($arg === 'false') return false;
        
        // Check if it's a simple number
        if (is_numeric($arg)) {
            return (float)$arg;
        }
        
        // If it contains functions or variables, evaluate as expression
        try {
            $result = $this->evaluateNumericExpression($arg);
            return $this->applyAutoRounding($result);
        } catch (Exception $e) {
            // If evaluation fails, treat as literal string
            return $arg;
        }
    }

    /**
     * Evaluate numeric expression (no variables, no functions)
     */
    private function evaluateNumericExpression(string $expr): float
    {
        if (preg_match('/[a-zA-Z_]/', $expr)) {
            throw new RuleFlowException("Invalid numeric expression: '$expr' contains unresolved variables");
        }

        $tokens = ExpressionParser::tokenize($expr);
        $tokens = ExpressionParser::processUnaryOperators($tokens);
        $postfix = ExpressionParser::convertToPostfix($tokens);
        
        return $this->evaluatePostfix($postfix);
    }

    /**
     * Validate final expression after variable replacement
     */
    private function validateFinalExpression(string $expr): void
    {
        $availableFunctions = $this->functions->getAvailableFunctions();
        foreach ($availableFunctions as $func) {
            if (strpos($expr, $func) !== false) {
                return;
            }
        }

        if (preg_match('/\$/', $expr)) {
            throw new RuleFlowException("Expression contains unresolved variables or invalid characters: '$expr'");
        }
        
        if (!preg_match('/^[0-9+\-*\/\(\)\s\.\*]+$/', $expr)) {
            throw new RuleFlowException("Expression contains unresolved variables or invalid characters: '$expr'");
        }
    }

    /**
     * Evaluate postfix expression with automatic rounding
     */
    private function evaluatePostfix(array $postfix): float
    {
        $stack = [];

        foreach ($postfix as $token) {
            if (is_numeric($token)) {
                $stack[] = $token;
            } elseif ($token === 'u-') {
                // Unary minus
                if (count($stack) < 1) {
                    throw new RuleFlowException("Invalid expression: insufficient operands for unary minus");
                }
                
                $a = array_pop($stack);
                $stack[] = -$a;
            } else {
                // Binary operators
                if (count($stack) < 2) {
                    throw new RuleFlowException("Invalid expression: insufficient operands");
                }

                $b = array_pop($stack);
                $a = array_pop($stack);

                $result = match ($token) {
                    '+' => $a + $b,
                    '-' => $a - $b,
                    '*' => $a * $b,
                    '/' => $this->safeDivision($a, $b),
                    '**' => pow($a, $b),
                    default => throw new RuleFlowException("Unknown operator: {$token}")
                };

                // 🎯 Apply automatic rounding to intermediate results
                $stack[] = $this->applyAutoRounding($result);
            }
        }

        if (count($stack) !== 1) {
            throw new RuleFlowException("Invalid expression: multiple results");
        }

        return $stack[0];
    }

    /**
     * Safe division operation with automatic rounding
     */
    private function safeDivision(float $a, float $b): float
    {
        // Check special values 
        if (!is_finite($a) || !is_finite($b)) {
            throw new RuleFlowException("Invalid operands: non-finite numbers");
        }
        
        // Use epsilon for floating point comparison
        $epsilon = 1e-10;
        if (abs($b) < $epsilon) {
            throw new RuleFlowException("Division by zero (or very small number)");
        }
        
        $result = $a / $b;
        
        // Check result
        if (!is_finite($result)) {
            throw new RuleFlowException("Division result is not finite");
        }
        
        return $result;
    }
}