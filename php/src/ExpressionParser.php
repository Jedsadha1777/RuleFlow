<?php 

/**
 * Math Parser - จัดการ parsing expressions
 */
class ExpressionParser
{
    public static function tokenize(string $expr): array
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

    private static array $operatorPrecedence = [
        'u-' => 5,  // เพิ่ม unary minus ด้วย precedence สูงสุด
        '**' => 4,
        '*' => 3,
        '/' => 3,
        '+' => 2,
        '-' => 2
    ];

    private static array $rightAssociative = ['**' => true, 'u-' => true]; 


    public static function convertToPostfix(array $tokens): array
    {
        $stack = [];
        $output = [];

        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $output[] = (float)$token;
            } elseif (array_key_exists($token, self::$operatorPrecedence)) {
                while (
                    !empty($stack) &&
                    end($stack) !== '(' &&
                    array_key_exists(end($stack), self::$operatorPrecedence) &&
                    (
                        (self::$operatorPrecedence[end($stack)] > self::$operatorPrecedence[$token]) ||
                        (self::$operatorPrecedence[end($stack)] == self::$operatorPrecedence[$token] && 
                         !isset(self::$rightAssociative[$token]))
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

    private static function isUnaryMinus(array $tokens, int $index): bool
    {
        // ถ้าเป็น token แรก = unary
        if ($index === 0) {
            return true;
        }
        
        $prevToken = $tokens[$index - 1];
        
        // ถ้า token ก่อนหน้าเป็น operator หรือ '(' = unary
        return in_array($prevToken, ['+', '-', '*', '/', '**', '('], true);
    }

    public static function processUnaryOperators(array $tokens): array
    {
        $processed = [];
        $length = count($tokens);
        
        for ($i = 0; $i < $length; $i++) {
            $token = $tokens[$i];
            
            // ตรวจสอบว่าเป็น unary minus หรือไม่
            if ($token === '-' && self::isUnaryMinus($tokens, $i)) {
                // แปลง unary minus เป็น 'u-' เพื่อแยกจาก binary minus
                $processed[] = 'u-';
            } else {
                $processed[] = $token;
            }
        }
        
        return $processed;
    }

    public static function replaceVariables(string $expr, array $vars): string
    {
        // Remove spaces for easier processing
        $expr = trim($expr);
        
        // FIX: Replace $variable with actual values WITHOUT adding extra $
        $expr = preg_replace_callback('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', function($matches) use ($vars) {
            $varName = $matches[1];
            
            if (isset($vars[$varName])) {
                $value = $vars[$varName];
                
                // FIX: Return the VALUE directly, not with $ prefix
                if (is_numeric($value)) {
                    return (string)$value;  // Return "100", not "$100"
                } else {
                    throw new RuleFlowException("Variable $varName must be numeric, got: " . gettype($value));
                }
            } else {
                throw new RuleFlowException("Variable \$$varName not found in context");
            }
        }, $expr);
        
        // handle variables without $ prefix
        foreach ($vars as $varName => $value) {
            if (is_numeric($value)) {
                // Replace whole word boundaries only
                $expr = preg_replace('/\b' . preg_quote($varName, '/') . '\b/', (string)$value, $expr);
            }
        }
        
        return $expr;
    }

}

?>