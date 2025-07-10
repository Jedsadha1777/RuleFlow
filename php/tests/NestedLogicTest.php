<?php
/**
 * NestedLogicTest.php - Unit tests for nested AND/OR logic conditions
 */

require_once __DIR__ . '/../src/RuleFlow.php';

class NestedLogicTest
{
    private RuleFlow $ruleFlow;
    private int $testCount = 0;
    private int $passCount = 0;

    public function __construct()
    {
        $this->ruleFlow = new RuleFlow();
    }

    public function runAllTests(): void
    {
        echo "🧪 Nested Logic Test Suite\n";
        echo "===========================\n\n";

        $this->testBasicAndCondition();
        $this->testBasicOrCondition();
        $this->testNestedAndOrCondition();
        $this->testComplexNesting();
        $this->testWithVariableReferences();
        $this->testCodeGeneration();

        echo "\n📊 Test Results: {$this->passCount}/{$this->testCount} tests passed\n";
        
        if ($this->passCount === $this->testCount) {
            echo "🎉 All tests PASSED! Nested logic is working correctly.\n";
        } else {
            echo "❌ Some tests FAILED. Check implementation.\n";
        }
    }

    /**
     * Test basic AND condition
     */
    private function testBasicAndCondition(): void
    {
        echo "Test 1: Basic AND condition\n";
        
        $config = [
            'formulas' => [
                [
                    'id' => 'result',
                    'switch' => 'trigger',
                    'when' => [
                        [
                            'if' => [
                                'and' => [
                                    ['op' => '>', 'var' => 'age', 'value' => 18],
                                    ['op' => '>', 'var' => 'income', 'value' => 25000]
                                ]
                            ],
                            'result' => 'qualified'
                        ]
                    ],
                    'default' => 'not_qualified'
                ]
            ]
        ];

        // Should pass AND condition
        $result1 = $this->ruleFlow->evaluate($config, [
            'trigger' => 'test',
            'age' => 25,
            'income' => 30000
        ]);
        $this->assertEqual('qualified', $result1['result'], 'AND condition should pass');

        // Should fail AND condition (age fails)
        $result2 = $this->ruleFlow->evaluate($config, [
            'trigger' => 'test',
            'age' => 16,
            'income' => 30000
        ]);
        $this->assertEqual('not_qualified', $result2['result'], 'AND condition should fail when age < 18');

        // Should fail AND condition (income fails)
        $result3 = $this->ruleFlow->evaluate($config, [
            'trigger' => 'test',
            'age' => 25,
            'income' => 20000
        ]);
        $this->assertEqual('not_qualified', $result3['result'], 'AND condition should fail when income < 25000');

        echo "\n";
    }

    /**
     * Test basic OR condition
     */
    private function testBasicOrCondition(): void
    {
        echo "Test 2: Basic OR condition\n";
        
        $config = [
            'formulas' => [
                [
                    'id' => 'result',
                    'switch' => 'trigger',
                    'when' => [
                        [
                            'if' => [
                                'or' => [
                                    ['op' => '>', 'var' => 'income', 'value' => 50000],
                                    ['op' => '==', 'var' => 'has_guarantor', 'value' => true]
                                ]
                            ],
                            'result' => 'approved'
                        ]
                    ],
                    'default' => 'denied'
                ]
            ]
        ];

        // Should pass OR condition (income high)
        $result1 = $this->ruleFlow->evaluate($config, [
            'trigger' => 'test',
            'income' => 60000,
            'has_guarantor' => false
        ]);
        $this->assertEqual('approved', $result1['result'], 'OR condition should pass with high income');

        // Should pass OR condition (has guarantor)
        $result2 = $this->ruleFlow->evaluate($config, [
            'trigger' => 'test',
            'income' => 30000,
            'has_guarantor' => true
        ]);
        $this->assertEqual('approved', $result2['result'], 'OR condition should pass with guarantor');

        // Should fail OR condition (both fail)
        $result3 = $this->ruleFlow->evaluate($config, [
            'trigger' => 'test',
            'income' => 30000,
            'has_guarantor' => false
        ]);
        $this->assertEqual('denied', $result3['result'], 'OR condition should fail when both conditions fail');

        echo "\n";
    }

    /**
     * Test nested AND/OR combination (your exact example)
     */
    private function testNestedAndOrCondition(): void
    {
        echo "Test 3: Nested AND/OR combination\n";
        
        $config = [
            'formulas' => [
                [
                    'id' => 'loan_decision',
                    'switch' => 'application_type',
                    'when' => [
                        [
                            'if' => [
                                'and' => [
                                    ['op' => '>', 'var' => 'age', 'value' => 25],
                                    [
                                        'or' => [
                                            ['op' => '>', 'var' => 'income', 'value' => 30000],
                                            ['op' => '==', 'var' => 'has_collateral', 'value' => true]
                                        ]
                                    ],
                                    ['op' => '!=', 'var' => 'status', 'value' => 'blacklist']
                                ]
                            ],
                            'result' => 'approved'
                        ]
                    ],
                    'default' => 'rejected'
                ]
            ]
        ];

        // Should approve: meets all criteria
        $result1 = $this->ruleFlow->evaluate($config, [
            'application_type' => 'standard',
            'age' => 30,
            'income' => 35000,
            'has_collateral' => false,
            'status' => 'normal'
        ]);
        $this->assertEqual('approved', $result1['loan_decision'], 'Should approve qualified applicant');

        // Should approve: has collateral (even with low income)
        $result2 = $this->ruleFlow->evaluate($config, [
            'application_type' => 'standard',
            'age' => 28,
            'income' => 25000,
            'has_collateral' => true,
            'status' => 'normal'
        ]);
        $this->assertEqual('approved', $result2['loan_decision'], 'Should approve with collateral');

        // Should reject: age too low
        $result3 = $this->ruleFlow->evaluate($config, [
            'application_type' => 'standard',
            'age' => 22,
            'income' => 40000,
            'has_collateral' => true,
            'status' => 'normal'
        ]);
        $this->assertEqual('rejected', $result3['loan_decision'], 'Should reject if age too low');

        // Should reject: blacklisted
        $result4 = $this->ruleFlow->evaluate($config, [
            'application_type' => 'standard',
            'age' => 30,
            'income' => 40000,
            'has_collateral' => true,
            'status' => 'blacklist'
        ]);
        $this->assertEqual('rejected', $result4['loan_decision'], 'Should reject if blacklisted');

        // Should reject: low income and no collateral
        $result5 = $this->ruleFlow->evaluate($config, [
            'application_type' => 'standard',
            'age' => 30,
            'income' => 25000,
            'has_collateral' => false,
            'status' => 'normal'
        ]);
        $this->assertEqual('rejected', $result5['loan_decision'], 'Should reject low income without collateral');

        echo "\n";
    }

    /**
     * Test complex nesting (OR of ANDs)
     */
    private function testComplexNesting(): void
    {
        echo "Test 4: Complex nesting (OR of ANDs)\n";
        
        $config = [
            'formulas' => [
                [
                    'id' => 'access_level',
                    'switch' => 'check_access',
                    'when' => [
                        [
                            'if' => [
                                'or' => [
                                    [
                                        'and' => [
                                            ['op' => '==', 'var' => 'role', 'value' => 'admin'],
                                            ['op' => '==', 'var' => 'department', 'value' => 'IT']
                                        ]
                                    ],
                                    [
                                        'and' => [
                                            ['op' => '==', 'var' => 'role', 'value' => 'manager'],
                                            ['op' => '>', 'var' => 'experience_years', 'value' => 5]
                                        ]
                                    ],
                                    ['op' => '==', 'var' => 'is_owner', 'value' => true]
                                ]
                            ],
                            'result' => 'full_access'
                        ]
                    ],
                    'default' => 'limited_access'
                ]
            ]
        ];

        // Should grant access: IT admin
        $result1 = $this->ruleFlow->evaluate($config, [
            'check_access' => 'true',
            'role' => 'admin',
            'department' => 'IT',
            'experience_years' => 2,
            'is_owner' => false
        ]);
        $this->assertEqual('full_access', $result1['access_level'], 'IT admin should get full access');

        // Should grant access: experienced manager
        $result2 = $this->ruleFlow->evaluate($config, [
            'check_access' => 'true',
            'role' => 'manager',
            'department' => 'Sales',
            'experience_years' => 8,
            'is_owner' => false
        ]);
        $this->assertEqual('full_access', $result2['access_level'], 'Experienced manager should get full access');

        // Should grant access: owner
        $result3 = $this->ruleFlow->evaluate($config, [
            'check_access' => 'true',
            'role' => 'employee',
            'department' => 'HR',
            'experience_years' => 1,
            'is_owner' => true
        ]);
        $this->assertEqual('full_access', $result3['access_level'], 'Owner should get full access');

        // Should deny access: new manager
        $result4 = $this->ruleFlow->evaluate($config, [
            'check_access' => 'true',
            'role' => 'manager',
            'department' => 'Sales',
            'experience_years' => 3,
            'is_owner' => false
        ]);
        $this->assertEqual('limited_access', $result4['access_level'], 'New manager should get limited access');

        echo "\n";
    }

    /**
     * Test with variable references
     */
    private function testWithVariableReferences(): void
    {
        echo "Test 5: With variable references\n";
        
        $config = [
            'formulas' => [
                [
                    'id' => 'calculate_threshold',
                    'formula' => 'base_salary * 0.3',
                    'inputs' => ['base_salary'],
                    'as' => '$income_threshold'
                ],
                [
                    'id' => 'eligibility',
                    'switch' => 'check_eligibility',
                    'when' => [
                        [
                            'if' => [
                                'and' => [
                                    ['op' => '>', 'var' => 'current_income', 'value' => '$income_threshold'],
                                    [
                                        'or' => [
                                            ['op' => '>=', 'var' => 'credit_score', 'value' => 650],
                                            ['op' => '==', 'var' => 'has_cosigner', 'value' => true]
                                        ]
                                    ]
                                ]
                            ],
                            'result' => 'eligible'
                        ]
                    ],
                    'default' => 'not_eligible'
                ]
            ]
        ];

        // Should be eligible: income above threshold and good credit
        $result1 = $this->ruleFlow->evaluate($config, [
            'base_salary' => 100000,  // threshold will be 30000
            'current_income' => 35000,
            'credit_score' => 700,
            'has_cosigner' => false,
            'check_eligibility' => 'yes'
        ]);
        $this->assertEqual('eligible', $result1['eligibility'], 'Should be eligible with good income and credit');

        // Should be eligible: income above threshold with cosigner (low credit)
        $result2 = $this->ruleFlow->evaluate($config, [
            'base_salary' => 100000,  // threshold will be 30000
            'current_income' => 35000,
            'credit_score' => 600,
            'has_cosigner' => true,
            'check_eligibility' => 'yes'
        ]);
        $this->assertEqual('eligible', $result2['eligibility'], 'Should be eligible with cosigner');

        // Should not be eligible: income too low
        $result3 = $this->ruleFlow->evaluate($config, [
            'base_salary' => 100000,  // threshold will be 30000
            'current_income' => 25000,
            'credit_score' => 750,
            'has_cosigner' => true,
            'check_eligibility' => 'yes'
        ]);
        $this->assertEqual('not_eligible', $result3['eligibility'], 'Should not be eligible with low income');

        echo "\n";
    }

    /**
     * Test code generation with nested conditions
     */
    private function testCodeGeneration(): void
    {
        echo "Test 6: Code generation with nested conditions\n";
        
        $config = [
            'formulas' => [
                [
                    'id' => 'complex_decision',
                    'switch' => 'process',
                    'when' => [
                        [
                            'if' => [
                                'and' => [
                                    ['op' => '>', 'var' => 'score', 'value' => 80],
                                    [
                                        'or' => [
                                            ['op' => '==', 'var' => 'premium_member', 'value' => true],
                                            ['op' => '>=', 'var' => 'years_member', 'value' => 3]
                                        ]
                                    ]
                                ]
                            ],
                            'result' => 'approved'
                        ]
                    ],
                    'default' => 'rejected'
                ]
            ]
        ];

        try {
            $generatedCode = $this->ruleFlow->generateFunctionAsString($config);
            
            // Check if generated code contains logical operators
            $hasAndOperator = strpos($generatedCode, '&&') !== false;
            $hasOrOperator = strpos($generatedCode, '||') !== false;
            $hasParentheses = strpos($generatedCode, '(') !== false && strpos($generatedCode, ')') !== false;
            
            $this->assertTrue($hasAndOperator, 'Generated code should contain && operator');
            $this->assertTrue($hasOrOperator, 'Generated code should contain || operator');
            $this->assertTrue($hasParentheses, 'Generated code should contain parentheses for grouping');
            
            echo "   ✅ Code generation includes nested logic operators\n";
            
            // Test if generated code actually works
            $testInput = [
                'process' => 'test',
                'score' => 85,
                'premium_member' => false,
                'years_member' => 5
            ];
            
            $result = $this->ruleFlow->evaluate($config, $testInput);
            $this->assertEqual('approved', $result['complex_decision'], 'Generated code should work correctly');
            
        } catch (Exception $e) {
            $this->testCount++;
            echo "   ❌ Code generation failed: " . $e->getMessage() . "\n";
            return;
        }

        echo "\n";
    }

    /**
     * Helper method to assert equality
     */
    private function assertEqual($expected, $actual, string $message): void
    {
        $this->testCount++;
        
        if ($expected === $actual) {
            $this->passCount++;
            echo "   ✅ $message\n";
        } else {
            echo "   ❌ $message\n";
            echo "      Expected: $expected\n";
            echo "      Actual: $actual\n";
        }
    }

    /**
     * Helper method to assert boolean true
     */
    private function assertTrue($condition, string $message): void
    {
        $this->testCount++;
        
        if ($condition) {
            $this->passCount++;
            echo "   ✅ $message\n";
        } else {
            echo "   ❌ $message\n";
        }
    }
}

// Run the tests
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new NestedLogicTest();
    $test->runAllTests();
}