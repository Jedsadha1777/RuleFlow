<?php

require_once __DIR__ . '/../src/FunctionRegistry.php';
require_once __DIR__ . '/../src/RuleFlowException.php';

class FunctionRegistryTest
{
    private FunctionRegistry $registry;
    
    public function setUp(): void
    {
        $this->registry = new FunctionRegistry();
    }
    
    /**
     * Test built-in math functions
     */
    public function testMathFunctions(): void
    {
        // Basic math
        $this->assertEquals(5, $this->registry->call('abs', [-5]));
        $this->assertEquals(1, $this->registry->call('min', [3, 7, 1]));
        $this->assertEquals(7, $this->registry->call('max', [3, 7, 1]));
        $this->assertEquals(4, $this->registry->call('round', [3.7]));
        $this->assertEquals(4, $this->registry->call('sqrt', [16]));
        
        // Advanced math
        $this->assertEquals(8, $this->registry->call('pow', [2, 3]));
        $this->assertEquals(1, $this->registry->call('log', [M_E]));
        $this->assertEquals(1, $this->registry->call('sin', [M_PI/2]));
        
        echo "✅ Math functions passed\n";
    }
    
    /**
     * Test statistics functions
     */
    public function testStatisticsFunctions(): void
    {
        $this->assertEquals(10, $this->registry->call('sum', [1, 2, 3, 4]));
        $this->assertEquals(4, $this->registry->call('avg', [2, 4, 6]));
        $this->assertEquals(3, $this->registry->call('count', [1, 2, 3]));
        $this->assertEquals(3, $this->registry->call('median', [1, 3, 5]));
        $this->assertEquals(4, $this->registry->call('median', [1, 3, 5, 7]));
        
        echo "✅ Statistics functions passed\n";
    }
    
    /**
     * Test business functions
     */
    public function testBusinessFunctions(): void
    {
        // Percentage
        $this->assertEquals(50, $this->registry->call('percentage', [25, 50]));
        
        // Compound interest: P(1+r)^t
        $result = $this->registry->call('compound_interest', [1000, 0.05, 2]);
        $this->assertEquals(1102.5, $result);
        
        // Simple interest: P(1+rt)
        $result = $this->registry->call('simple_interest', [1000, 0.05, 2]);
        $this->assertEquals(1100, $result);
        
        // Discount
        $result = $this->registry->call('discount', [100, 20]); // 20% off
        $this->assertEquals(80, $result);
        
        // Markup
        $result = $this->registry->call('markup', [100, 25]); // 25% markup
        $this->assertEquals(125, $result);
        
        // Loan payment
        $result = $this->registry->call('pmt', [10000, 0.01, 12]); // Monthly payment
        $this->assertGreaterThan(800, $result);
        $this->assertLessThan(900, $result);
        
        echo "✅ Business functions passed\n";
    }
    
    /**
     * Test utility functions
     */
    public function testUtilityFunctions(): void
    {
        // Clamp
        $this->assertEquals(5, $this->registry->call('clamp', [3, 5, 10])); // Min bound
        $this->assertEquals(10, $this->registry->call('clamp', [15, 5, 10])); // Max bound
        $this->assertEquals(7, $this->registry->call('clamp', [7, 5, 10])); // In range
        
        // Normalize
        $this->assertEquals(0.5, $this->registry->call('normalize', [5, 0, 10]));
        $this->assertEquals(0, $this->registry->call('normalize', [0, 0, 10]));
        $this->assertEquals(1, $this->registry->call('normalize', [10, 0, 10]));
        
        // Coalesce (first non-null value)
        $this->assertEquals(5, $this->registry->call('coalesce', [null, '', 5, 10]));
        $this->assertEquals(0, $this->registry->call('coalesce', [null, '', null])); // Default to 0
        
        // If null
        $this->assertEquals(10, $this->registry->call('if_null', [null, 10]));
        $this->assertEquals(5, $this->registry->call('if_null', [5, 10]));
        
        // Age calculation
        $this->assertEquals(30, $this->registry->call('age_from_year', [1994, 2024]));
        
        // BMI
        $result = $this->registry->call('bmi', [70, 1.75]); // 70kg, 1.75m
        $this->assertEquals(22.86, round($result, 2));
        
        // Percentile
        $this->assertEquals(50, $this->registry->call('percentile', [15, 10, 20])); // Middle
        $this->assertEquals(0, $this->registry->call('percentile', [10, 10, 20])); // Min
        $this->assertEquals(100, $this->registry->call('percentile', [20, 10, 20])); // Max
        
        echo "✅ Utility functions passed\n";
    }
    
    /**
     * Test custom function registration
     */
    public function testCustomFunctionRegistration(): void
    {
        // Register custom function
        $this->registry->register('double', function($x) {
            return $x * 2;
        });
        
        $result = $this->registry->call('double', [5]);
        $this->assertEquals(10, $result);
        
        // Register complex custom function
        $this->registry->register('compound_formula', function($a, $b, $c) {
            return ($a + $b) * $c;
        });
        
        $result = $this->registry->call('compound_formula', [2, 3, 4]);
        $this->assertEquals(20, $result);
        
        echo "✅ Custom function registration passed\n";
    }
    
    /**
     * Test error handling
     */
    public function testErrorHandling(): void
    {
        // Unknown function
        try {
            $this->registry->call('unknown_function', [1, 2, 3]);
            $this->fail('Should throw exception for unknown function');
        } catch (RuleFlowException $e) {
            $this->assertStringContains('Unknown function', $e->getMessage());
            $this->assertEquals('unknown_function', $e->getFunctionName());
        }
        
        // Division by zero in percentage
        try {
            $this->registry->call('percentage', [50, 0]);
            $this->fail('Should throw exception for division by zero');
        } catch (RuleFlowException $e) {
            $this->assertStringContains('zero denominator', $e->getMessage());
        }
        
        // Negative square root
        try {
            $this->registry->call('sqrt', [-4]);
            $this->fail('Should throw exception for negative sqrt');
        } catch (RuleFlowException $e) {
            $this->assertStringContains('negative number', $e->getMessage());
        }
        
        // Logarithm of zero/negative
        try {
            $this->registry->call('log', [0]);
            $this->fail('Should throw exception for log of zero');
        } catch (RuleFlowException $e) {
            $this->assertStringContains('non-positive number', $e->getMessage());
        }
        
        // Invalid loan parameters
        try {
            $this->registry->call('pmt', [-1000, 0.05, 12]); // Negative principal
            $this->fail('Should throw exception for invalid loan parameters');
        } catch (RuleFlowException $e) {
            $this->assertStringContains('Invalid loan parameters', $e->getMessage());
        }
        
        echo "✅ Error handling passed\n";
    }
    
    /**
     * Test function availability
     */
    public function testFunctionAvailability(): void
    {
        $functions = $this->registry->getAvailableFunctions();
        
        // Should have basic math functions
        $this->assertContains('abs', $functions);
        $this->assertContains('min', $functions);
        $this->assertContains('max', $functions);
        $this->assertContains('sqrt', $functions);
        
        // Should have statistics functions
        $this->assertContains('avg', $functions);
        $this->assertContains('sum', $functions);
        $this->assertContains('median', $functions);
        
        // Should have business functions
        $this->assertContains('percentage', $functions);
        $this->assertContains('compound_interest', $functions);
        
        // Should have utility functions
        $this->assertContains('clamp', $functions);
        $this->assertContains('bmi', $functions);
        
        echo "✅ Function availability passed\n";
    }
    
    /**
     * Test function categorization
     */
    public function testFunctionCategorization(): void
    {
        $categories = $this->registry->getFunctionsByCategory();
        
        $this->assertArrayHasKey('Math', $categories);
        $this->assertArrayHasKey('Statistics', $categories);
        $this->assertArrayHasKey('Business', $categories);
        $this->assertArrayHasKey('Utility', $categories);
        
        $this->assertContains('abs', $categories['Math']);
        $this->assertContains('avg', $categories['Statistics']);
        $this->assertContains('percentage', $categories['Business']);
        $this->assertContains('clamp', $categories['Utility']);
        
        echo "✅ Function categorization passed\n";
    }
    
    /**
     * Test edge cases
     */
    public function testEdgeCases(): void
    {
        // Empty arguments for functions that allow it
        $this->assertEquals(0, $this->registry->call('coalesce', []));
        
        // Single argument for min/max
        $this->assertEquals(5, $this->registry->call('min', [5]));
        $this->assertEquals(5, $this->registry->call('max', [5]));
        
        // Zero rate for compound interest
        $result = $this->registry->call('compound_interest', [1000, 0, 5]);
        $this->assertEquals(1000, $result); // Should return principal
        
        // Zero rate for loan payment
        $result = $this->registry->call('pmt', [1200, 0, 12]);
        $this->assertEquals(100, $result); // Should be principal/periods
        
        // Min equals max for clamp
        $this->assertEquals(5, $this->registry->call('clamp', [10, 5, 5]));
        
        echo "✅ Edge cases passed\n";
    }
    
    /**
     * Test real-world scenarios
     */
    public function testRealWorldScenarios(): void
    {
        // BMI categories
        $underweight = $this->registry->call('bmi', [45, 1.70]); // 15.57
        $normal = $this->registry->call('bmi', [70, 1.75]); // 22.86  
        $overweight = $this->registry->call('bmi', [85, 1.75]); // 27.76
        
        $this->assertLessThan(18.5, $underweight);
        $this->assertGreaterThan(18.5, $normal);
        $this->assertLessThan(25, $normal);
        $this->assertGreaterThan(25, $overweight);
        
        // Mortgage calculation (approximate)
        $monthlyRate = 0.05 / 12; // 5% annual = 0.416% monthly
        $payment = $this->registry->call('pmt', [300000, $monthlyRate, 360]); // 30 years
        $this->assertGreaterThan(1500, $payment);
        $this->assertLessThan(2000, $payment);
        
        // Investment growth
        $initial = 10000;
        $rate = 0.07; // 7% annual
        $years = 10;
        $future_value = $this->registry->call('compound_interest', [$initial, $rate, $years]);
        $this->assertGreaterThan(19000, $future_value); // Should roughly double
        $this->assertLessThan(21000, $future_value);
        
        // Grade calculation
        $scores = [85, 92, 78, 88, 90];
        $average = $this->registry->call('avg', $scores);
        $this->assertEquals(86.6, round($average, 1));
        
        echo "✅ Real-world scenarios passed\n";
    }
    
    // Helper assertion methods - FIXED VERSION
    private function assertEquals($expected, $actual, string $message = ''): void
    {
        // Handle floating point comparison
        if (is_float($expected) || is_float($actual)) {
            if (abs((float)$expected - (float)$actual) > 0.01) {
                throw new Exception("Assertion failed: Expected $expected, got $actual. $message");
            }
        } else {
            // Use strict comparison for non-float values
            if ($expected !== $actual) {
                throw new Exception("Assertion failed: Expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ". $message");
            }
        }
    }
    
    private function assertGreaterThan($expected, $actual): void
    {
        if ((float)$actual <= (float)$expected) {
            throw new Exception("Assertion failed: $actual should be greater than $expected");
        }
    }
    
    private function assertLessThan($expected, $actual): void
    {
        if ((float)$actual >= (float)$expected) {
            throw new Exception("Assertion failed: $actual should be less than $expected");
        }
    }
    
    private function assertContains($needle, array $haystack): void
    {
        if (!in_array($needle, $haystack, true)) {
            throw new Exception("Assertion failed: '$needle' not found in array");
        }
    }
    
    private function assertArrayHasKey($key, array $array): void
    {
        if (!array_key_exists($key, $array)) {
            throw new Exception("Assertion failed: Key '$key' not found in array");
        }
    }
    
    private function assertStringContains(string $needle, string $haystack): void
    {
        if (strpos($haystack, $needle) === false) {
            throw new Exception("Assertion failed: '$needle' not found in '$haystack'");
        }
    }
    
    private function fail(string $message): void
    {
        throw new Exception("Test failed: $message");
    }
    
    /**
     * Run all tests
     */
    public function runAllTests(): void
    {
        echo "🧪 Running FunctionRegistry Tests...\n\n";
        
        $this->setUp();
        
        try {
            $this->testMathFunctions();
            $this->testStatisticsFunctions();
            $this->testBusinessFunctions();
            $this->testUtilityFunctions();
            $this->testCustomFunctionRegistration();
            $this->testErrorHandling();
            $this->testFunctionAvailability();
            $this->testFunctionCategorization();
            $this->testEdgeCases();
            $this->testRealWorldScenarios();
            
            echo "\n🎉 All FunctionRegistry tests passed!\n\n";
        } catch (Exception $e) {
            echo "\n❌ Test failed: " . $e->getMessage() . "\n\n";
            throw $e;
        }
    }
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new FunctionRegistryTest();
    $test->runAllTests();
}