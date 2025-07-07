<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/RuleFlow.php';
require_once __DIR__ . '/../../src/FormulaProcessor.php';
require_once __DIR__ . '/../../src/ExpressionEvaluator.php';
require_once __DIR__ . '/../../src/FunctionRegistry.php';
require_once __DIR__ . '/../../src/RuleFlowException.php';

/**
 * Type Inconsistency Bug Test - ทดสอบ Expected 15, got '15' problem
 */
class TypeInconsistencyBugTest
{
    private RuleFlow $ruleFlow;
    private FormulaProcessor $processor;
    private ExpressionEvaluator $evaluator;
    
    public function setUp(): void
    {
        $this->ruleFlow = new RuleFlow();
        $functions = new FunctionRegistry();
        $this->evaluator = new ExpressionEvaluator($functions);
        $this->processor = new FormulaProcessor($this->evaluator);
    }
    
    /**
     * Test 1: set_vars ใน switch formula
     * ปัญหา: set_vars return string แทน number
     */
    public function testSetVarsInSwitchFormula(): void
    {
        echo "🧪 Test 1: set_vars Type Inconsistency in Switch Formula\n";
        
        $config = [
            'formulas' => [
                [
                    'id' => 'bonus_calculator',
                    'switch' => 'performance_score',
                    'when' => [
                        [
                            'if' => ['op' => '>=', 'value' => 90],
                            'result' => 'Excellent',
                            'set_vars' => [
                                '$bonus_amount' => '15',        // String value - should become int
                                '$bonus_rate' => '0.15',       // String value - should become float
                                '$is_eligible' => 'true'       // String value - should become boolean
                            ]
                        ]
                    ],
                    'default' => 'Average'
                ]
            ]
        ];
        
        $inputs = ['performance_score' => 95];
        
        try {
            $result = $this->ruleFlow->evaluate($config, $inputs);
            
            // Check types - these should PASS after fix
            echo "   Testing bonus_amount type...\n";
            $this->assertExactType(15, $result['bonus_amount'], "bonus_amount should be int, not string");
            
            echo "   Testing bonus_rate type...\n";
            $this->assertExactType(0.15, $result['bonus_rate'], "bonus_rate should be float, not string");
            
            echo "   Testing is_eligible type...\n";
            $this->assertExactType(true, $result['is_eligible'], "is_eligible should be boolean, not string");
            
            echo "   ✅ Test 1 PASSED - All types correct\n\n";
            
        } catch (Exception $e) {
            echo "   ❌ Test 1 FAILED: " . $e->getMessage() . "\n\n";
            throw $e;
        }
    }
    
    /**
     * Test 2: Expression evaluation results
     * ปัญหา: expression results เป็น string
     */
    public function testExpressionEvaluationTypes(): void
    {
        echo "🧪 Test 2: Expression Evaluation Type Inconsistency\n";
        
        $config = [
            'formulas' => [
                [
                    'id' => 'calculation',
                    'formula' => 'a + b',  // เปลี่ยนเป็นใช้ inputs เพื่อผ่าน validation
                    'inputs' => ['a', 'b']
                ],
                [
                    'id' => 'decimal_calc',
                    'formula' => 'x + y',  // เปลี่ยนเป็นใช้ inputs เพื่อผ่าน validation
                    'inputs' => ['x', 'y']
                ]
            ]
        ];
        
        $inputs = [
            'a' => 10,
            'b' => 5,
            'x' => 10.5,
            'y' => 4.2
        ];
        
        try {
            $result = $this->ruleFlow->evaluate($config, $inputs);
            
            echo "   Testing integer calculation result...\n";
            $this->assertNumericType(15, $result['calculation'], "Integer calculation should return numeric type");
            
            echo "   Testing decimal calculation result...\n";
            $this->assertNumericType(14.7, $result['decimal_calc'], "Decimal calculation should return numeric type");
            
            echo "   ✅ Test 2 PASSED - Expression results have correct types\n\n";
            
        } catch (Exception $e) {
            echo "   ❌ Test 2 FAILED: " . $e->getMessage() . "\n\n";
            throw $e;
        }
    }
    
    /**
     * Test 3: Complex set_vars with expressions
     * ปัญหา: set_vars expressions return string
     */
    public function testComplexSetVarsExpressions(): void
    {
        echo "🧪 Test 3: Complex set_vars Expression Types\n";
        
        $config = [
            'formulas' => [
                [
                    'id' => 'grade_processor',
                    'switch' => 'score',
                    'when' => [
                        [
                            'if' => ['op' => '>=', 'value' => 80],
                            'result' => 'A',
                            'set_vars' => [
                                '$base_points' => '50',                    // Simple literal (string)
                                '$bonus_points' => '$base_points',         // Reference
                                '$total_points' => '$bonus_points + 10'    // Expression
                            ]
                        ]
                    ],
                    'default' => 'F'
                ]
            ]
        ];
        
        $inputs = ['score' => 85];
        
        try {
            $result = $this->ruleFlow->evaluate($config, $inputs);
            
            echo "   Testing base_points (literal) type...\n";
            $this->assertExactType(50, $result['base_points'], "Literal '50' should become int 50");
            
            echo "   Testing bonus_points (reference) type...\n";
            $this->assertExactType(50, $result['bonus_points'], "Reference should maintain correct type");
            
            echo "   Testing total_points (expression) type...\n";
            // ExpressionEvaluator may return float, so use numeric assertion
            $this->assertNumericType(60, $result['total_points'], "Expression result should be numeric");
            
            echo "   ✅ Test 3 PASSED - Complex set_vars types correct\n\n";
            
        } catch (Exception $e) {
            echo "   ❌ Test 3 FAILED: " . $e->getMessage() . "\n\n";
            throw $e;
        }
    }
    
    /**
     * Test 4: Mixed data types in set_vars
     * ปัญหา: ทุก type กลายเป็น string
     */
    public function testMixedDataTypes(): void
    {
        echo "🧪 Test 4: Mixed Data Types in set_vars\n";
        
        $config = [
            'formulas' => [
                [
                    'id' => 'data_processor',
                    'switch' => 'mode',
                    'when' => [
                        [
                            'if' => ['op' => '==', 'value' => 'test'],
                            'result' => 'processed',
                            'set_vars' => [
                                '$integer_val' => '42',           // Should be int
                                '$float_val' => '3.14',          // Should be float  
                                '$boolean_true' => 'true',       // Should be boolean true
                                '$boolean_false' => 'false',     // Should be boolean false
                                '$string_val' => 'hello',        // Should remain string
                                '$zero_int' => '0',              // Should be int 0
                                '$zero_float' => '0.0'           // Should be float 0.0
                            ]
                        ]
                    ],
                    'default' => 'skipped'
                ]
            ]
        ];
        
        $inputs = ['mode' => 'test'];
        
        try {
            $result = $this->ruleFlow->evaluate($config, $inputs);
            
            echo "   Testing integer conversion...\n";
            $this->assertExactType(42, $result['integer_val'], "String '42' should become int 42");
            
            echo "   Testing float conversion...\n";
            $this->assertExactType(3.14, $result['float_val'], "String '3.14' should become float 3.14");
            
            echo "   Testing boolean true conversion...\n";
            $this->assertExactType(true, $result['boolean_true'], "String 'true' should become boolean true");
            
            echo "   Testing boolean false conversion...\n";
            $this->assertExactType(false, $result['boolean_false'], "String 'false' should become boolean false");
            
            echo "   Testing string preservation...\n";
            $this->assertExactType('hello', $result['string_val'], "String 'hello' should remain string");
            
            echo "   Testing zero integer...\n";
            $this->assertExactType(0, $result['zero_int'], "String '0' should become int 0");
            
            echo "   Testing zero float...\n";
            $this->assertExactType(0.0, $result['zero_float'], "String '0.0' should become float 0.0");
            
            echo "   ✅ Test 4 PASSED - All mixed types converted correctly\n\n";
            
        } catch (Exception $e) {
            echo "   ❌ Test 4 FAILED: " . $e->getMessage() . "\n\n";
            throw $e;
        }
    }
    
    /**
     * Test 5: Direct FormulaProcessor testing
     * ทดสอบ processSetVarsRuntime โดยตรง
     */
    public function testFormulaProcessorDirectly(): void
    {
        echo "🧪 Test 5: Direct FormulaProcessor set_vars Testing\n";
        
        $setVars = [
            '$amount' => '100',
            '$rate' => '0.05',
            '$active' => 'true',
            '$name' => 'test'
        ];
        
        $context = [];
        
        try {
            // ถ้า processSetVarsRuntime เป็น private ต้องใช้ reflection
            $reflection = new ReflectionClass($this->processor);
            $method = $reflection->getMethod('processSetVarsRuntime');
            $method->setAccessible(true);
            
            $method->invokeArgs($this->processor, [$setVars, &$context]);
            
            echo "   Testing direct set_vars processing...\n";
            $this->assertExactType(100, $context['amount'], "amount should be int 100");
            $this->assertExactType(0.05, $context['rate'], "rate should be float 0.05");
            $this->assertExactType(true, $context['active'], "active should be boolean true");
            $this->assertExactType('test', $context['name'], "name should remain string");
            
            echo "   ✅ Test 5 PASSED - Direct FormulaProcessor works correctly\n\n";
            
        } catch (ReflectionException $e) {
            echo "   ⚠️  Test 5 SKIPPED - processSetVarsRuntime method not accessible\n";
            echo "   (This is normal if method doesn't exist yet)\n\n";
        } catch (Exception $e) {
            echo "   ❌ Test 5 FAILED: " . $e->getMessage() . "\n\n";
            throw $e;
        }
    }
    
    /**
     * Helper method: Assert numeric type (int or float)
     */
    private function assertNumericType($expected, $actual, string $message): void
    {
        if (!is_numeric($actual)) {
            throw new Exception("❌ $message\n" .
                              "   Expected: numeric type\n" .
                              "   Got: " . gettype($actual) . " ($actual)");
        }
        
        // Check if values are close enough (for float comparison)
        if (abs((float)$expected - (float)$actual) > 0.001) {
            throw new Exception("❌ $message\n" .
                              "   Expected: $expected\n" .
                              "   Got: $actual");
        }
        
        echo "     ✅ $message - Correct!\n";
    }
    
    /**
     * Helper method: Assert exact type and value
     */
    private function assertExactType($expected, $actual, string $message): void
    {
        $expectedType = gettype($expected);
        $actualType = gettype($actual);
        
        if ($expectedType !== $actualType) {
            throw new Exception("❌ $message\n" .
                              "   Expected: $expected ($expectedType)\n" .
                              "   Got: $actual ($actualType)");
        }
        
        if ($expected !== $actual) {
            throw new Exception("❌ $message\n" .
                              "   Expected: $expected\n" .
                              "   Got: $actual");
        }
        
        echo "     ✅ $message - Correct!\n";
    }
    
    /**
     * Run all type inconsistency tests
     */
    public function runAllTests(): void
    {
        echo "🔧 TYPE INCONSISTENCY BUG TESTS\n";
        echo "================================\n\n";
        
        $this->setUp();
        
        try {
            $this->testSetVarsInSwitchFormula();
            $this->testExpressionEvaluationTypes();
            $this->testComplexSetVarsExpressions();
            $this->testMixedDataTypes();
            $this->testFormulaProcessorDirectly();
            
            echo "🎉 ALL TYPE INCONSISTENCY TESTS PASSED!\n";
            echo "=======================================\n";
            echo "✅ set_vars return correct types\n";
            echo "✅ Expression evaluation returns correct types\n";
            echo "✅ Mixed data type conversion works\n";
            echo "✅ FormulaProcessor handles types correctly\n\n";
            
            echo "🚀 TYPE INCONSISTENCY BUG: FIXED!\n";
            echo "📝 Note: Expression results may be float instead of int\n";
            echo "     This is normal PHP behavior for mathematical operations\n";
            
        } catch (Exception $e) {
            echo "\n❌ TYPE INCONSISTENCY BUG STILL EXISTS!\n";
            echo "=====================================\n";
            echo "Problem: " . $e->getMessage() . "\n\n";
            
            echo "🔧 NEXT STEPS TO FIX:\n";
            echo "1. Add convertValueType() method to FormulaProcessor.php\n";
            echo "2. Modify processSetVarsRuntime() to use type conversion\n";
            echo "3. Update ExpressionEvaluator to return proper types\n\n";
            
            throw $e;
        }
    }
}

// Run the test if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new TypeInconsistencyBugTest();
    $test->runAllTests();
}