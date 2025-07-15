<?php

declare(strict_types=1);

require_once __DIR__ . '/ExpressionParser.php';


class ExpressionEvaluator
{
    private FunctionRegistry $functions;

    public function getFunctionRegistry(): FunctionRegistry
    {
        return $this->functions;
    }
    
    public function __construct(FunctionRegistry $functions)
    {
        $this->functions = $functions;
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
        
        // Ensure result is proper numeric type
        return $this->convertToNumericType($result);
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

        $startPos = $matches[0][1] + strlen($matches[0][0]);
        $openParens = 1;
        $pos = $startPos;
        $length = strlen($expr);

        while ($pos < $length && $openParens > 0) {
            if ($expr[$pos] === '(') {
                $openParens++;
            } elseif ($expr[$pos] === ')') {
                $openParens--;
            }
            $pos++;
        }

        if ($openParens > 0) {
            throw new RuleFlowException("Unmatched parentheses in function call: $funcName");
        }

        $argsStr = substr($expr, $startPos, $pos - $startPos - 1);
        $args = $this->parseArguments($argsStr);
        
        $result = $this->functions->call($funcName, $args);
        
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

        // Evaluate each argument
        $evaluatedArgs = [];
        foreach ($args as $arg) {
            $arg = trim($arg);
            if (is_numeric($arg)) {
                $evaluatedArgs[] = (float)$arg;
            } else {
                try {
                    $evaluatedArgs[] = $this->evaluateNumericExpression($arg);
                } catch (Exception $e) {
                    throw new RuleFlowException("Error evaluating argument '$arg': " . $e->getMessage());
                }
            }
        }

        return $evaluatedArgs;
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
        $tokens = ExpressionParser::processUnaryOperators($tokens); // เพิ่มการประมวลผล unary operators
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
        
        // อัปเดต regex pattern ให้รองรับ negative numbers
        if (!preg_match('/^[0-9+\-*\/\(\)\s\.\*]+$/', $expr)) {
            throw new RuleFlowException("Expression contains unresolved variables or invalid characters: '$expr'");
        }
    }


    /**
     * Evaluate postfix expression - ปรับปรุงให้รองรับ unary operators
     */
    private function evaluatePostfix(array $postfix): float
    {
        $stack = [];

        foreach ($postfix as $token) {
            if (is_numeric($token)) {
                $stack[] = $token;
            } elseif ($token === 'u-') {
                // Unary minus - ต้องการ operand เดียว
                if (count($stack) < 1) {
                    throw new RuleFlowException("Invalid expression: insufficient operands for unary minus");
                }
                
                $a = array_pop($stack);
                $stack[] = -$a;
            } else {
                // Binary operators - ต้องการ operand สองตัว
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

                $stack[] = $result;
            }
        }

        if (count($stack) !== 1) {
            throw new RuleFlowException("Invalid expression: multiple results");
        }

        return $stack[0];
    }

    /**
     * Safe division operation
     */
    private function safeDivision(float $a, float $b): float
    {
        // 1. check special values 
        if (!is_finite($a) || !is_finite($b)) {
            throw new RuleFlowException("Invalid operands: non-finite numbers");
        }
        
        // 2. use epsilon for floating point comparison
        $epsilon = 1e-10;
        if (abs($b) < $epsilon) {
            throw new RuleFlowException("Division by zero (or very small number)");
        }
        
        $result = $a / $b;
        
        // 3. check result
        if (!is_finite($result)) {
            throw new RuleFlowException("Division result is not finite");
        }
        
        return $result;
    }
}