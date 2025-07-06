<?php

declare(strict_types=1);


class ExpressionEvaluator
{
    private FunctionRegistry $functions;
    
    private array $operatorPrecedence = [
        'u-' => 5,  // เพิ่ม unary minus ด้วย precedence สูงสุด
        '**' => 4,
        '*' => 3,
        '/' => 3,
        '+' => 2,
        '-' => 2
    ];

    private array $rightAssociative = ['**' => true, 'u-' => true]; // unary minus เป็น right associative

    public function __construct(FunctionRegistry $functions)
    {
        $this->functions = $functions;
    }

    /**
     * Safely evaluate mathematical expressions
     */
    public function safeEval(string $expr, array $vars): float
    {
        $expr = $this->replaceVariables($expr, $vars);
        $expr = $this->processFunctions($expr);
        $this->validateFinalExpression($expr);

        $tokens = $this->tokenize($expr);
        $tokens = $this->processUnaryOperators($tokens); // เพิ่มการประมวลผล unary operators
        $postfix = $this->convertToPostfix($tokens);
        
        return $this->evaluatePostfix($postfix);
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
        
        return $this->safeEval($evalExpr, []);
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

        $tokens = $this->tokenize($expr);
        $tokens = $this->processUnaryOperators($tokens); // เพิ่มการประมวลผล unary operators
        $postfix = $this->convertToPostfix($tokens);
        
        return $this->evaluatePostfix($postfix);
    }

    /**
     * Replace variables with their values
     */
    private function replaceVariables(string $expr, array $vars): string
    {
        foreach ($vars as $key => $value) {
            if (!is_numeric($value)) {
                throw new RuleFlowException("Variable {$key} must be numeric, got: " . gettype($value));
            }
            $expr = preg_replace('/\b' . preg_quote($key, '/') . '\b/', (string)$value, $expr);
        }

        return $expr;
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
        
        // อัปเดต regex pattern ให้รองรับ negative numbers
        if (!preg_match('/^[0-9+\-*\/\(\)\s\.\*]+$/', $expr)) {
            throw new RuleFlowException("Expression contains unresolved variables or invalid characters: '$expr'");
        }
    }

    /**
     * Tokenize expression - ปรับปรุงให้รองรับ negative numbers และ power operator
     */
    private function tokenize(string $expr): array
    {
        // จัดการ ** operator ก่อน (ป้องกัน * * แยกกัน)
        $expr = str_replace('**', ' ** ', $expr);
        
        // ใส่ space รอบ operators และ parentheses
        $expr = preg_replace('/([+\-*\/\(\)])/', ' $1 ', $expr);
        
        // แก้ไข ** ที่อาจถูกแยกออกจากขั้นตอนข้างต้น
        $expr = preg_replace('/\*\s+\*/', '**', $expr);
        
        return array_filter(
            preg_split('/\s+/', trim($expr)),
            fn($token) => $token !== ''
        );
    }

    /**
     * Process unary operators - ฟังก์ชันใหม่เพื่อจัดการ unary minus
     */
    private function processUnaryOperators(array $tokens): array
    {
        $processed = [];
        $length = count($tokens);
        
        for ($i = 0; $i < $length; $i++) {
            $token = $tokens[$i];
            
            // ตรวจสอบว่าเป็น unary minus หรือไม่
            if ($token === '-' && $this->isUnaryMinus($tokens, $i)) {
                // แปลง unary minus เป็น 'u-' เพื่อแยกจาก binary minus
                $processed[] = 'u-';
            } else {
                $processed[] = $token;
            }
        }
        
        return $processed;
    }

    /**
     * ตรวจสอบว่า '-' ตัวนี้เป็น unary minus หรือไม่
     */
    private function isUnaryMinus(array $tokens, int $index): bool
    {
        // ถ้าเป็น token แรก = unary
        if ($index === 0) {
            return true;
        }
        
        $prevToken = $tokens[$index - 1];
        
        // ถ้า token ก่อนหน้าเป็น operator หรือ '(' = unary
        return in_array($prevToken, ['+', '-', '*', '/', '**', '('], true);
    }

    /**
     * Convert infix expression to postfix using Shunting-yard algorithm
     * ปรับปรุงให้รองรับ unary operators
     */
    private function convertToPostfix(array $tokens): array
    {
        $stack = [];
        $output = [];

        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $output[] = (float)$token;
            } elseif (array_key_exists($token, $this->operatorPrecedence)) {
                while (
                    !empty($stack) &&
                    end($stack) !== '(' &&
                    array_key_exists(end($stack), $this->operatorPrecedence) &&
                    (
                        ($this->operatorPrecedence[end($stack)] > $this->operatorPrecedence[$token]) ||
                        ($this->operatorPrecedence[end($stack)] == $this->operatorPrecedence[$token] && 
                         !isset($this->rightAssociative[$token]))
                    )
                ) {
                    $output[] = array_pop($stack);
                }
                $stack[] = $token;
            } elseif ($token === '(') {
                $stack[] = $token;
            } elseif ($token === ')') {
                while (!empty($stack) && end($stack) !== '(') {
                    $output[] = array_pop($stack);
                }
                if (empty($stack)) {
                    throw new RuleFlowException("Mismatched parentheses");
                }
                array_pop($stack);
            } else {
                throw new RuleFlowException("Unknown token: {$token}");
            }
        }

        while (!empty($stack)) {
            if (in_array(end($stack), ['(', ')'], true)) {
                throw new RuleFlowException("Mismatched parentheses");
            }
            $output[] = array_pop($stack);
        }

        return $output;
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