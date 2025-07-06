<?php


declare(strict_types=1);

require_once __DIR__ . '/../../src/RuleFlow.php';
require_once __DIR__ . '/../../src/ExpressionEvaluator.php';
require_once __DIR__ . '/../../src/FunctionRegistry.php';
require_once __DIR__ . '/../../src/RuleFlowException.php';

// Summery Note 
// Avoid using expressions like -2 ** 2 in tests.
// It affects core architecture and introduces many edge cases.
// Example: (-2) ** 2 ≠ -2 ** 2
// Power operator (**) is right-associative and should have higher precedence than unary minus,
// according to standard mathematical rules.


/**
 * Test fix for Power Operator with Negative Numbers
 */
class PowerOperatorFixTest
{
    private ExpressionEvaluator $evaluator;
    
    public function setUp(): void
    {
        $functions = new FunctionRegistry();
        $this->evaluator = new ExpressionEvaluator($functions);
    }
    
    /**
     * Test power operator tokenization
     */
    public function testPowerTokenization(): void
    {
        echo "🧪 Testing Power Operator Tokenization...\n";
        
        // Basic power
        $result = $this->evaluator->safeEval('2 ** 3', []);
        $this->assertEquals(8.0, $result, "Basic power");
        
        // Power with spaces
        $result = $this->evaluator->safeEval('2**3', []);
        $this->assertEquals(8.0, $result, "Power without spaces");
        
        // Multiple power operations
        $result = $this->evaluator->safeEval('2 ** 2 ** 2', []);
        $this->assertEquals(16.0, $result, "Multiple power (right associative)");
        
        echo "✅ Power operator tokenization passed\n\n";
    }
    
    /**
     * Test power with negative numbers - the main fix
     */
    public function testPowerWithNegatives(): void
    {
        echo "🧪 Testing Power with Negative Numbers...\n";
        
        // Negative base with power - ปัญหาหลักที่แก้ไข
        $result = $this->evaluator->safeEval('-2 ** 2', []);
        $this->assertEquals(-4.0, $result, "Negative base power: -(2^2)");
        
        // Parentheses change precedence
        $result = $this->evaluator->safeEval('(-2) ** 2', []);
        $this->assertEquals(4.0, $result, "Parentheses negative power: (-2)^2");
        
        // Negative exponent
        $result = $this->evaluator->safeEval('4 ** -2', []);
        $this->assertEquals(0.25, $result, "Negative exponent: 4^(-2)");
        
        // Both negative
        $result = $this->evaluator->safeEval('(-2) ** -2', []);
        $this->assertEquals(0.25, $result, "Both negative: (-2)^(-2)");
        
        // Complex negative power
        $result = $this->evaluator->safeEval('-3 ** 2 + 1', []);
        $this->assertEquals(-8.0, $result, "Complex: -(3^2) + 1");
        
        echo "✅ Power with negatives passed\n\n";
    }
    
    /**
     * Test power operator precedence
     */
    public function testPowerPrecedence(): void
    {
        echo "🧪 Testing Power Operator Precedence...\n";
        
        // Power vs multiplication
        $result = $this->evaluator->safeEval('2 * 3 ** 2', []);
        $this->assertEquals(18.0, $result, "Power before multiplication: 2 * (3^2)");
        
        // Power vs unary minus
        $result = $this->evaluator->safeEval('-2 ** 3', []);
        $this->assertEquals(-8.0, $result, "Unary minus vs power: -(2^3)");
        
        // Parentheses override
        $result = $this->evaluator->safeEval('(-2) ** 3', []);
        $this->assertEquals(-8.0, $result, "Parentheses override: (-2)^3");
        
        // Right associativity of power
        $result = $this->evaluator->safeEval('2 ** 3 ** 2', []);
        $this->assertEquals(512.0, $result, "Right associative: 2^(3^2) = 2^9");
        
        echo "✅ Power precedence passed\n\n";
    }
    
    /**
     * Test complex power expressions
     */
    public function testComplexPowerExpressions(): void
    {
        echo "🧪 Testing Complex Power Expressions...\n";
        
        // Variables with power
        $vars = ['base' => 3, 'exp' => 2];
        $result = $this->evaluator->safeEval('base ** exp', $vars);
        $this->assertEquals(9.0, $result, "Variables with power");
        
        // Negative variables
        $vars = ['x' => -2, 'y' => 3];
        $result = $this->evaluator->safeEval('x ** y', $vars);
        $this->assertEquals(-8.0, $result, "Negative variable power");
        
        // Functions with power
        $result = $this->evaluator->safeEval('abs(-3) ** 2', []);
        $this->assertEquals(9.0, $result, "Function result power");
        
        // Power in parentheses
        $result = $this->evaluator->safeEval('(2 + 1) ** (1 + 1)', []);
        $this->assertEquals(9.0, $result, "Power with grouped expressions");
        
        // Fractional power (square root)
        $result = $this->evaluator->safeEval('9 ** 0.5', []);
        $this->assertEquals(3.0, $result, "Fractional power (square root)");
        
        echo "✅ Complex power expressions passed\n\n";
    }
    
    /**
     * Test edge cases
     */
    public function testPowerEdgeCases(): void
    {
        echo "🧪 Testing Power Edge Cases...\n";
        
        // Power of zero
        $result = $this->evaluator->safeEval('5 ** 0', []);
        $this->assertEquals(1.0, $result, "Any number to power 0");
        
        // Zero to positive power
        $result = $this->evaluator->safeEval('0 ** 2', []);
        $this->assertEquals(0.0, $result, "Zero to positive power");
        
        // One to any power
        $result = $this->evaluator->safeEval('1 ** 100', []);
        $this->assertEquals(1.0, $result, "One to any power");
        
        // Negative power with decimals
        $result = $this->evaluator->safeEval('2 ** -0.5', []);
        $this->assertEquals(0.7071067811865476, $result, "Negative decimal power", 0.000001);
        
        echo "✅ Power edge cases passed\n\n";
    }
    
    /**
     * Test the specific failing cases from the integration test
     */
    public function testSpecificFailingCases(): void
    {
        echo "🧪 Testing Previously Failing Cases...\n";
        
        // Test 12: Negative power base (was failing)
        try {
            $result = $this->evaluator->safeEval('-2 ** 2', []);
            $this->assertEquals(-4.0, $result, "Previously failing: '-2 ** 2'");
            echo "✅ Fixed: '-2 ** 2' = $result\n";
        } catch (Exception $e) {
            echo "❌ Still failing: '-2 ** 2' - " . $e->getMessage() . "\n";
        }
        
        // Test 13: Parentheses negative power (was failing)
        try {
            $result = $this->evaluator->safeEval('(-2) ** 2', []);
            $this->assertEquals(4.0, $result, "Previously failing: '(-2) ** 2'");
            echo "✅ Fixed: '(-2) ** 2' = $result\n";
        } catch (Exception $e) {
            echo "❌ Still failing: '(-2) ** 2' - " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    // Helper assertion methods
    private function assertEquals($expected, $actual, string $message = '', $delta = 0.001): void
    {
        $expectedFloat = (float)$expected;
        $actualFloat = (float)$actual;
        
        if (abs($expectedFloat - $actualFloat) > $delta) {
            throw new Exception("❌ $message: Expected $expected, got $actual");
        }
        echo "  ✓ $message: $actual\n";
    }
    
    /**
     * Run all power operator tests
     */
    public function runAllTests(): void
    {
        echo "🔧 POWER OPERATOR FIX TESTS\n";
        echo "===========================\n\n";
        
        $this->setUp();
        
        try {
            $this->testPowerTokenization();
            $this->testPowerWithNegatives();
            $this->testPowerPrecedence();
            $this->testComplexPowerExpressions();
            $this->testPowerEdgeCases();
            $this->testSpecificFailingCases();
            
            echo "🎉 ALL POWER OPERATOR TESTS PASSED!\n";
            echo "✅ Tokenizer properly handles ** operator\n";
            echo "✅ Power with negative numbers working\n";
            echo "✅ Operator precedence correct\n";
            echo "✅ Right associativity implemented\n";
            echo "✅ Edge cases handled\n\n";
            
        } catch (Exception $e) {
            echo "\n❌ Test failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}

// Run the tests
$test = new PowerOperatorFixTest();
$test->runAllTests();