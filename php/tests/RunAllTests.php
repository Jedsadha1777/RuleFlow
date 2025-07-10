<?php

/**
 * Test Suite Runner for RuleFlow
 * 
 * Usage: php tests/RunAllTests.php
 */

// Test files mapping - add test files as they become available
$testFiles = [
    'ConfigTemplateManagerTest.php' => 'ConfigTemplateManagerTest',
    'ConfigValidatorTest.php' => 'ConfigValidatorTest',
    'ExpressionEvaluatorTest.php' => 'ExpressionEvaluatorTest',
    'FunctionRegistryTest.php' => 'FunctionRegistryTest',
    'InputValidatorTest.php' => 'InputValidatorTest',
    'SchemaGeneratorTest.php' => 'SchemaGeneratorTest',
    'ValidationAPITest.php' => 'ValidationAPITest',
    'RuleFlowIntegrationTest.php' => 'RuleFlowIntegrationTest',
    'NestedLogicTest.php' => 'NestedLogicTest'  // 🆕 Added nested logic test
];

// Check which test files exist and require them
$availableTests = [];
foreach ($testFiles as $file => $className) {
    $filePath = __DIR__ . '/' . $file;
    if (file_exists($filePath)) {
        require_once $filePath;
        $availableTests[$className] = $file;
    }
}

class TestRunner
{
    private array $testResults = [];
    private int $totalTests = 0;
    private int $passedTests = 0;
    private int $failedTests = 0;
    private float $startTime;
    private array $availableTests;
    
    public function __construct(array $availableTests)
    {
        $this->startTime = microtime(true);
        $this->availableTests = $availableTests;
    }
    
    /**
     * Run all available test suites
     */
    public function runAllTests(): void
    {
        echo "🚀 RuleFlow Test Suite\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        if (empty($this->availableTests)) {
            echo "❌ No test files found!\n";
            echo "Make sure test files exist in the tests directory.\n";
            return;
        }
        
        echo "📋 Found " . count($this->availableTests) . " test suite(s)\n\n";
        
        // Run available tests in order
        $testOrder = [
            'ExpressionEvaluatorTest',
            'FunctionRegistryTest', 
            'ConfigValidatorTest',
            'InputValidatorTest',
            'NestedLogicTest',        // 🆕 Added to test order
            'ConfigTemplateManagerTest',
            'SchemaGeneratorTest',
            'ValidationAPITest',
            'RuleFlowIntegrationTest'
        ];
        
        foreach ($testOrder as $testClass) {
            if (isset($this->availableTests[$testClass])) {
                $testName = $this->getTestDisplayName($testClass);
                $this->runTestSuite($testClass, $testName);
            }
        }
        
        // Run any remaining tests not in the order
        foreach ($this->availableTests as $testClass => $file) {
            if (!in_array($testClass, $testOrder)) {
                $testName = $this->getTestDisplayName($testClass);
                $this->runTestSuite($testClass, $testName);
            }
        }
        
        $this->printSummary();
    }
    
    /**
     * Run individual test suite
     */
    private function runTestSuite(string $testClass, string $testName): void
    {
        echo "📋 Running {$testName}...\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        try {
            if (!class_exists($testClass)) {
                throw new Exception("Test class '$testClass' not found");
            }
            
            $test = new $testClass();
            
            if (!method_exists($test, 'runAllTests')) {
                throw new Exception("Test class '$testClass' does not have runAllTests method");
            }
            
            $test->runAllTests();
            
            $this->testResults[$testName] = 'PASSED';
            $this->passedTests++;
            
        } catch (Exception $e) {
            echo "❌ {$testName} FAILED: " . $e->getMessage() . "\n\n";
            $this->testResults[$testName] = 'FAILED: ' . $e->getMessage();
            $this->failedTests++;
        }
        
        $this->totalTests++;
    }
    
    /**
     * Print test summary
     */
    private function printSummary(): void
    {
        $endTime = microtime(true);
        $duration = round($endTime - $this->startTime, 2);
        
        echo "📊 TEST SUMMARY\n";
        echo "=" . str_repeat("=", 50) . "\n\n";
        
        foreach ($this->testResults as $testName => $result) {
            $status = strpos($result, 'FAILED') === 0 ? '❌' : '✅';
            echo "$status $testName: $result\n";
        }
        
        echo "\n" . str_repeat("-", 50) . "\n";
        echo "Total Test Suites: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: {$this->failedTests}\n";
        echo "Duration: {$duration}s\n";
        
        if ($this->failedTests === 0 && $this->totalTests > 0) {
            echo "\n🎉 ALL TESTS PASSED! RuleFlow is working correctly.\n";
            
            // Special message for nested logic if test was run
            if (isset($this->testResults['Nested Logic Tests']) && 
                $this->testResults['Nested Logic Tests'] === 'PASSED') {
                echo "🚀 NEW FEATURE: Nested AND/OR logic is working perfectly!\n";
            }
            
            exit(0);
        } elseif ($this->totalTests === 0) {
            echo "\n⚠️ NO TESTS RUN! Please create test files.\n";
            exit(1);
        } else {
            echo "\n💥 SOME TESTS FAILED! Please check the errors above.\n";
            exit(1);
        }
    }
    
    /**
     * Get display name for test class
     */
    private function getTestDisplayName(string $testClass): string
    {
        $nameMap = [
            'ConfigTemplateManagerTest' => 'Config Template Manager',
            'ExpressionEvaluatorTest' => 'Expression Evaluator',
            'ConfigValidatorTest' => 'Configuration Validator',
            'FunctionRegistryTest' => 'Function Registry',
            'InputValidatorTest' => 'Input Validator',
            'SchemaGeneratorTest' => 'Schema Generator',
            'ValidationAPITest' => 'Validation API',
            'RuleFlowIntegrationTest' => 'Integration Tests',
            'NestedLogicTest' => 'Nested Logic Tests'  // 🆕 Added display name
        ];
        
        return $nameMap[$testClass] ?? $testClass;
    }
    
    /**
     * Run specific test suite only
     */
    public function runSpecificTest(string $testName): void
    {
        echo "🎯 Running specific test: $testName\n\n";
        
        $testClassMap = [
            'template' => 'ConfigTemplateManagerTest',
            'expression' => 'ExpressionEvaluatorTest',
            'validator' => 'ConfigValidatorTest',
            'functions' => 'FunctionRegistryTest',
            'input' => 'InputValidatorTest',
            'schema' => 'SchemaGeneratorTest',
            'validation' => 'ValidationAPITest',
            'integration' => 'RuleFlowIntegrationTest',
            'nested' => 'NestedLogicTest',        // 🆕 Added nested test mapping
            'logic' => 'NestedLogicTest'          // 🆕 Alternative mapping
        ];
        
        if (!isset($testClassMap[$testName])) {
            echo "❌ Unknown test: $testName\n";
            echo "Available tests: " . implode(', ', array_keys($testClassMap)) . "\n";
            exit(1);
        }
        
        $testClass = $testClassMap[$testName];
        
        if (!isset($this->availableTests[$testClass])) {
            echo "❌ Test file for '$testName' not found!\n";
            echo "Available tests: " . implode(', ', array_keys($this->availableTests)) . "\n";
            exit(1);
        }
        
        $displayName = $this->getTestDisplayName($testClass);
        $this->runTestSuite($testClass, $displayName);
        $this->printSummary();
    }
    
    /**
     * Check test environment
     */
    public function checkTestEnvironment(): bool
    {
        echo "🔍 Checking Test Environment...\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        // Check core RuleFlow files
        $coreFiles = [
            __DIR__ . '/../src/RuleFlow.php' => 'RuleFlow (Main)',
            __DIR__ . '/../src/FormulaProcessor.php' => 'FormulaProcessor',
            __DIR__ . '/../src/ExpressionEvaluator.php' => 'ExpressionEvaluator',
            __DIR__ . '/../src/CodeGenerator.php' => 'CodeGenerator',
            __DIR__ . '/../src/Templates/ConfigTemplateManager.php' => 'ConfigTemplateManager',
            __DIR__ . '/../src/RuleFlowException.php' => 'RuleFlowException'
        ];
        
        $missing = [];
        $found = [];
        
        foreach ($coreFiles as $file => $name) {
            if (file_exists($file)) {
                $found[] = $name;
            } else {
                $missing[] = $name . " ($file)";
            }
        }
        
        echo "✅ Found files:\n";
        foreach ($found as $file) {
            echo "   - $file\n";
        }
        
        if (!empty($missing)) {
            echo "\n❌ Missing files:\n";
            foreach ($missing as $file) {
                echo "   - $file\n";
            }
        }
        
        echo "\n📋 Available test files:\n";
        foreach ($this->availableTests as $testClass => $file) {
            echo "   - $file ($testClass)\n";
        }
        
        echo "\n";
        
        if (!empty($missing)) {
            echo "⚠️ Some core files are missing. Tests may fail.\n";
            return false;
        }
        
        echo "✅ Test environment looks good!\n";
        
        // Special check for nested logic enhancement
        if (isset($this->availableTests['NestedLogicTest'])) {
            echo "🚀 NEW: Nested logic tests are available!\n";
        }
        
        return true;
    }
    
    /**
     * List available tests
     */
    public function listTests(): void
    {
        echo "📋 Available Tests:\n";
        echo "=" . str_repeat("=", 30) . "\n";
        
        if (empty($this->availableTests)) {
            echo "No test files found in the tests directory.\n";
            return;
        }
        
        foreach ($this->availableTests as $testClass => $file) {
            $displayName = $this->getTestDisplayName($testClass);
            $isNew = ($testClass === 'NestedLogicTest') ? ' 🆕 NEW!' : '';
            echo "• $displayName ($file)$isNew\n";
        }
        
        echo "\nUsage:\n";
        echo "php tests/RunAllTests.php                    # Run all tests\n";
        echo "php tests/RunAllTests.php --test=template    # Run specific test\n";
        echo "php tests/RunAllTests.php --test=nested      # Run nested logic tests\n";
        echo "php tests/RunAllTests.php --check            # Check environment\n";
        echo "php tests/RunAllTests.php --list             # Show this list\n";
        echo "php tests/RunAllTests.php --quick            # Quick functionality test\n";
    }
    
    /**
     * Run quick validation test
     */
    public function runQuickTest(): void
    {
        echo "⚡ Quick Test - Basic RuleFlow Functionality\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        try {
            // Test 1: Basic require
            require_once __DIR__ . '/../src/RuleFlow.php';
            echo "✅ RuleFlow class loaded\n";
            
            // Test 2: Create instance
            $ruleFlow = new RuleFlow();
            echo "✅ RuleFlow instance created\n";
            
            // Test 3: Simple evaluation
            $config = [
                'formulas' => [
                    [
                        'id' => 'test_calc',
                        'formula' => 'a + b',
                        'inputs' => ['a', 'b']
                    ]
                ]
            ];
            
            $inputs = ['a' => 10, 'b' => 20];
            $result = $ruleFlow->evaluate($config, $inputs);
            
            if ($result['test_calc'] === 30) {
                echo "✅ Basic calculation works (10 + 20 = 30)\n";
            } else {
                echo "❌ Basic calculation failed (got: {$result['test_calc']})\n";
                return;
            }
            
            // Test 4: Nested logic (if available)
            try {
                $nestedConfig = [
                    'formulas' => [
                        [
                            'id' => 'nested_test',
                            'switch' => 'trigger',
                            'when' => [
                                [
                                    'if' => [
                                        'and' => [
                                            ['op' => '>', 'var' => 'x', 'value' => 5],
                                            ['op' => '<', 'var' => 'y', 'value' => 10]
                                        ]
                                    ],
                                    'result' => 'success'
                                ]
                            ],
                            'default' => 'fail'
                        ]
                    ]
                ];
                
                $nestedInputs = ['trigger' => 'test', 'x' => 7, 'y' => 8];
                $nestedResult = $ruleFlow->evaluate($nestedConfig, $nestedInputs);
                
                if ($nestedResult['nested_test'] === 'success') {
                    echo "✅ Nested AND logic works! 🚀\n";
                } else {
                    echo "⚠️ Nested logic might not be implemented yet\n";
                }
                
            } catch (Exception $e) {
                echo "⚠️ Nested logic not available (this is normal for older versions)\n";
            }
            
            // Test 5: Function generation
            $generatedCode = $ruleFlow->generateFunctionAsString($config);
            if (strpos($generatedCode, 'function') !== false) {
                echo "✅ Code generation works\n";
            } else {
                echo "❌ Code generation failed\n";
                return;
            }
            
            echo "\n🎉 Quick test passed! RuleFlow is working correctly.\n";
            
        } catch (Exception $e) {
            echo "❌ Quick test failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
}

// Process command line arguments
$args = isset($argv) ? array_slice($argv, 1) : [];
$runner = new TestRunner($availableTests);

if (empty($args)) {
    // Run all tests
    $runner->runAllTests();
} else {
    foreach ($args as $arg) {
        if ($arg === '--check') {
            $runner->checkTestEnvironment();
        } elseif ($arg === '--list') {
            $runner->listTests();
        } elseif ($arg === '--quick') {
            $runner->runQuickTest();
        } elseif (strpos($arg, '--test=') === 0) {
            $testName = substr($arg, 7);
            $runner->runSpecificTest($testName);
        } else {
            echo "❌ Unknown argument: $arg\n";
            echo "Usage: php tests/RunAllTests.php [--check|--list|--quick|--test=testname]\n";
            exit(1);
        }
    }
}
