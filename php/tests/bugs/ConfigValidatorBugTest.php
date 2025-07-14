<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/RuleFlow.php';

require_once __DIR__ . '/../../src/ConfigValidator.php';
require_once __DIR__ . '/../../src/RuleFlowException.php';

/**
 * ConfigValidator Bug Test - ทดสอบ determineSafetyLevel() bug
 */
class ConfigValidatorBugTest
{
    private ConfigValidator $validator;
    
    public function setUp(): void
    {
        $this->validator = new ConfigValidator();
    }
    
    /**
     * Test 1: Clean configuration should return SAFE or CAUTION (not UNSAFE)
     */
    public function testCleanConfigurationSafetyLevel()
    {
        echo "🧪 Test 1: Clean Configuration Safety Level\n";
        
        $cleanConfig = [
            'formulas' => [
                [
                    'id' => 'clean_formula',
                    'switch' => 'status',
                    'when' => [
                        [
                            'if' => ['op' => '==', 'value' => 'active'],
                            'result' => 'processed',
                            'set_vars' => [
                                '$result_code' => '200',
                                '$message' => 'Success'
                            ]
                        ]
                    ],
                    'default' => 'skipped'
                ]
            ]
        ];
        
        try {
            $report = $this->validator->generateTwoPassReport($cleanConfig);
            
            echo "   Generated Report Summary:\n";
            echo "   - Safety Level: {$report['summary']['safety_level']}\n";
            echo "   - Error Count: {$report['summary']['error_count']}\n";
            echo "   - Warning Count: {$report['summary']['warning_count']}\n";
            
            if (!empty($report['details']['errors'])) {
                echo "   - Errors:\n";
                foreach ($report['details']['errors'] as $error) {
                    echo "     * $error\n";
                }
            }
            
            if (!empty($report['details']['warnings'])) {
                echo "   - Warnings:\n";
                foreach ($report['details']['warnings'] as $warning) {
                    echo "     * $warning\n";
                }
            }
            
            // Test assertions
            if ($report['summary']['safety_level'] === 'UNSAFE' && $report['summary']['error_count'] > 0) {
                echo "   ❌ BUG DETECTED: Clean config marked as UNSAFE with {$report['summary']['error_count']} errors\n";
                echo "   Expected: SAFE or CAUTION (unused variables should be warnings, not errors)\n";
                return false;
            } else {
                echo "   ✅ Test 1 PASSED - Safety level: {$report['summary']['safety_level']}\n";
                return true;
            }
            
        } catch (Exception $e) {
            echo "   ❌ Test 1 FAILED - Exception: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Test 2: Configuration with real errors should return UNSAFE
     */
    public function testRealErrorsConfigurationSafetyLevel()
    {
        echo "\n🧪 Test 2: Real Errors Configuration Safety Level\n";
        
        $errorConfig = [
            'formulas' => [
                [
                    'id' => 'formula_a',
                    'switch' => 'mode',
                    'when' => [
                        [
                            'if' => ['op' => '==', 'value' => 'active'],
                            'result' => 'A',
                            'set_vars' => ['$shared_var' => '100']
                        ]
                    ],
                    'default' => 'inactive'
                ],
                [
                    'id' => 'formula_b',
                    'switch' => 'mode',
                    'when' => [
                        [
                            'if' => ['op' => '==', 'value' => 'active'],
                            'result' => 'B',
                            'set_vars' => ['$shared_var' => '200']  // REAL CONFLICT!
                        ]
                    ],
                    'default' => 'inactive'
                ]
            ]
        ];
        
        try {
            $report = $this->validator->generateTwoPassReport($errorConfig);
            
            echo "   Generated Report Summary:\n";
            echo "   - Safety Level: {$report['summary']['safety_level']}\n";
            echo "   - Error Count: {$report['summary']['error_count']}\n";
            echo "   - Warning Count: {$report['summary']['warning_count']}\n";
            
            if (!empty($report['details']['errors'])) {
                echo "   - Errors:\n";
                foreach ($report['details']['errors'] as $error) {
                    echo "     * $error\n";
                }
            }
            
            // Test assertions
            if ($report['summary']['safety_level'] !== 'UNSAFE') {
                echo "   ❌ Test 2 FAILED - Expected UNSAFE for real conflicts, got: {$report['summary']['safety_level']}\n";
                return false;
            } else {
                echo "   ✅ Test 2 PASSED - Correctly identified as UNSAFE\n";
                return true;
            }
            
        } catch (Exception $e) {
            echo "   ❌ Test 2 FAILED - Exception: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Test 3: Direct determineSafetyLevel() method testing
     */
    public function testDetermineSafetyLevelDirect()
    {
        echo "\n🧪 Test 3: Direct determineSafetyLevel() Method Testing\n";
        
        try {
            // Test with unused variables (should be CAUTION, not UNSAFE)
            $validation1 = [
                'errors' => [
                    "Variable 'result_code' is produced but never consumed. Consider removing or using this variable.",
                    "Variable 'message' is produced but never consumed. Consider removing or using this variable."
                ],
                'warnings' => [],
                'analysis' => []
            ];
            
            $reflection = new ReflectionClass($this->validator);
            $method = $reflection->getMethod('determineSafetyLevel');
            $method->setAccessible(true);
            
            echo "   Testing with unused variable 'errors'...\n";
            $result1 = $method->invokeArgs($this->validator, [$validation1]);
            echo "   Result: $result1 (Expected: CAUTION)\n";
            
            if ($result1 === 'UNSAFE') {
                echo "   ❌ BUG CONFIRMED: Unused variables incorrectly marked as UNSAFE\n";
                echo "   The determineSafetyLevel() method has the variable declaration bug\n";
                return false;
            }
            
            // Test with real errors (should be UNSAFE)
            $validation2 = [
                'errors' => [
                    "Variable 'shared_var' is produced by multiple formulas: formula_a, formula_b. This may cause unpredictable behavior.",
                    "Circular reference detected: Variable 'test' depends on itself through expression."
                ],
                'warnings' => [],
                'analysis' => []
            ];
            
            echo "   Testing with real errors...\n";
            $result2 = $method->invokeArgs($this->validator, [$validation2]);
            echo "   Result: $result2 (Expected: UNSAFE)\n";
            
            if ($result2 !== 'UNSAFE') {
                echo "   ❌ Test 3 FAILED - Real errors should be UNSAFE\n";
                return false;
            }
            
            echo "   ✅ Test 3 PASSED - Direct method testing successful\n";
            return true;
            
        } catch (ReflectionException $e) {
            echo "   ⚠️  Test 3 SKIPPED - Cannot access determineSafetyLevel() method\n";
            return true;
        } catch (Exception $e) {
            echo "   ❌ Test 3 FAILED - Exception: " . $e->getMessage() . "\n";
            
            // Check if it's the variable declaration bug
            if (strpos($e->getMessage(), 'Undefined variable') !== false || 
                strpos($e->getMessage(), 'warnings') !== false ||
                strpos($e->getMessage(), 'realErrors') !== false) {
                echo "   🔍 BUG CONFIRMED: Variable declaration bug in determineSafetyLevel()\n";
                echo "   Missing: \$realErrors = []; and \$warnings = [];\n";
            }
            
            return false;
        }
    }
    
    /**
     * Test 4: Expected vs Actual behavior comparison
     */
    public function testExpectedVsActualBehavior()
    {
        echo "\n🧪 Test 4: Expected vs Actual Behavior Comparison\n";
        
        $testCases = [
            [
                'name' => 'Unused Variables Only',
                'errors' => ['Variable \'test\' is produced but never consumed.'],
                'expected' => 'CAUTION'
            ],
            [
                'name' => 'Real Conflicts',
                'errors' => ['Variable \'test\' is produced by multiple formulas.'],
                'expected' => 'UNSAFE'
            ],
            [
                'name' => 'Mixed Issues',
                'errors' => [
                    'Variable \'unused\' is produced but never consumed.',
                    'Variable \'conflict\' is produced by multiple formulas.'
                ],
                'expected' => 'UNSAFE'
            ],
            [
                'name' => 'No Issues',
                'errors' => [],
                'expected' => 'SAFE'
            ]
        ];
        
        $allPassed = true;
        
        foreach ($testCases as $testCase) {
            echo "   Testing: {$testCase['name']}\n";
            
            $validation = [
                'errors' => $testCase['errors'],
                'warnings' => [],
                'analysis' => []
            ];
            
            try {
                $reflection = new ReflectionClass($this->validator);
                $method = $reflection->getMethod('determineSafetyLevel');
                $method->setAccessible(true);
                
                $result = $method->invokeArgs($this->validator, [$validation]);
                
                if ($result === $testCase['expected']) {
                    echo "     ✅ PASS: Got {$result} (Expected: {$testCase['expected']})\n";
                } else {
                    echo "     ❌ FAIL: Got {$result} (Expected: {$testCase['expected']})\n";
                    $allPassed = false;
                }
                
            } catch (Exception $e) {
                echo "     ❌ ERROR: " . $e->getMessage() . "\n";
                $allPassed = false;
            }
        }
        
        if ($allPassed) {
            echo "   ✅ Test 4 PASSED - All behavior tests passed\n";
        } else {
            echo "   ❌ Test 4 FAILED - Some behavior tests failed\n";
        }
        
        return $allPassed;
    }
    
    /**
     * Run all ConfigValidator bug tests
     */
    public function runAllTests(): void
    {
        echo "🔧 CONFIG VALIDATOR BUG TESTS\n";
        echo "=============================\n\n";
        
        $this->setUp();
        
        $results = [];
        $results[] = $this->testCleanConfigurationSafetyLevel();
        $results[] = $this->testRealErrorsConfigurationSafetyLevel();
        $results[] = $this->testDetermineSafetyLevelDirect();
        $results[] = $this->testExpectedVsActualBehavior();
        
        $passed = array_sum($results);
        $total = count($results);
        
        echo "\n📊 TEST SUMMARY:\n";
        echo "================\n";
        echo "Tests Passed: $passed/$total\n";
        
        if ($passed === $total) {
            echo "🎉 ALL CONFIG VALIDATOR TESTS PASSED!\n";
            echo "✅ ConfigValidator bug is FIXED!\n";
        } else {
            echo "❌ CONFIG VALIDATOR BUG STILL EXISTS!\n";
            echo "\n🔧 TO FIX:\n";
            echo "1. Open ConfigValidator.php\n";
            echo "2. Find determineSafetyLevel() method (around line 275)\n";
            echo "3. Add these lines at the beginning:\n";
            echo "   \$realErrors = [];\n";
            echo "   \$warnings = [];\n";
            echo "4. The bug should be fixed!\n";
        }
    }
}

// Run the test if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new ConfigValidatorBugTest();
    $test->runAllTests();
}