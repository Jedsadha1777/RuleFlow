<?php

require_once __DIR__ . '/../src/RuleFlow.php';

class RuleFlowIntegrationTest
{
    private RuleFlow $engine;
    
    public function setUp(): void
    {
        $this->engine = new RuleFlow();
    }
    
    /**
     * Test basic BMI calculator workflow
     */
    public function testBMICalculatorWorkflow(): void
    {
        $config = [
            'formulas' => [
                [
                    'id' => 'bmi_calculation',
                    'formula' => 'weight / ((height / 100) ** 2)',
                    'inputs' => ['weight', 'height'],
                    'as' => '$bmi'
                ],
                [
                    'id' => 'category',
                    'switch' => '$bmi',
                    'when' => [
                        ['if' => ['op' => '<', 'value' => 18.5], 'result' => 'Underweight'],
                        ['if' => ['op' => 'between', 'value' => [18.5, 24.9]], 'result' => 'Normal'],
                        ['if' => ['op' => '>=', 'value' => 25], 'result' => 'Overweight']
                    ],
                    'default' => 'Unknown'
                ]
            ]
        ];
        
        $inputs = ['weight' => 70, 'height' => 175];
        $result = $this->engine->evaluate($config, $inputs);
        
        $this->assertEquals(22.86, round($result['bmi'], 2));
        $this->assertEquals('Normal', $result['category']);
        
        echo "✅ BMI calculator workflow passed\n";
    }
    
    /**
     * Test credit scoring system
     */
    public function testCreditScoringWorkflow(): void
    {
        $config = [
            'formulas' => [
                [
                    'id' => 'credit_assessment',
                    'rules' => [
                        [
                            'var' => 'income',
                            'ranges' => [
                                ['if' => ['op' => '>=', 'value' => 100000], 'score' => 40],
                                ['if' => ['op' => '>=', 'value' => 50000], 'score' => 25],
                                ['if' => ['op' => '>=', 'value' => 30000], 'score' => 15]
                            ]
                        ],
                        [
                            'var' => 'age',
                            'ranges' => [
                                ['if' => ['op' => 'between', 'value' => [25, 45]], 'score' => 20],
                                ['if' => ['op' => 'between', 'value' => [46, 60]], 'score' => 15],
                                ['if' => ['op' => '>=', 'value' => 18], 'score' => 10]
                            ]
                        ],
                        [
                            'var' => 'employment_years',
                            'ranges' => [
                                ['if' => ['op' => '>=', 'value' => 5], 'score' => 20],
                                ['if' => ['op' => '>=', 'value' => 2], 'score' => 15],
                                ['if' => ['op' => '>=', 'value' => 1], 'score' => 10]
                            ]
                        ],
                        [
                            'var' => 'has_property',
                            'if' => ['op' => '==', 'value' => 1],
                            'score' => 20
                        ]
                    ]
                ],
                [
                    'id' => 'loan_decision',
                    'switch' => 'credit_assessment',
                    'when' => [
                        [
                            'if' => ['op' => '>=', 'value' => 80],
                            'result' => 'Approved',
                            'set_vars' => [
                                '$interest_rate' => 5.5, 
                                '$max_amount' => 1000000
                            ]
                        ],
                        [
                            'if' => ['op' => '>=', 'value' => 60],
                            'result' => 'Approved',
                            'set_vars' => [
                                '$interest_rate' => 7.0, 
                                '$max_amount' => 500000
                            ]
                        ],
                        [
                            'if' => ['op' => '>=', 'value' => 40],
                            'result' => 'Conditional',
                            'set_vars' => [
                                '$interest_rate' => 9.0, 
                                '$max_amount' => 200000
                            ]
                        ]
                    ],
                    'default' => 'Rejected'
                ]
            ]
        ];
        
        $inputs = [
            'income' => 75000,
            'age' => 35,
            'employment_years' => 8,
            'has_property' => 1
        ];
        
        $result = $this->engine->evaluate($config, $inputs);
        
        $this->assertEquals(85, $result['credit_assessment']); // 25+20+20+20
        $this->assertEquals('Approved', $result['loan_decision']);
        $this->assertEquals(5.5, $result['interest_rate']);
        $this->assertEquals(1000000, $result['max_amount']);
        
        echo "✅ Credit scoring workflow passed\n";
    }
    
    /**
     * Test multi-dimensional scoring
     */
    public function testMultiDimensionalScoring(): void
    {
        $config = [
            'formulas' => [
                [
                    'id' => 'risk_assessment',
                    'scoring' => [
                        'ifs' => [
                            'vars' => ['age', 'income'],
                            'tree' => [
                                [
                                    'if' => ['op' => 'between', 'value' => [25, 45]],
                                    'ranges' => [
                                        [
                                            'if' => ['op' => '>=', 'value' => 50000],
                                            'score' => 100,
                                            'risk_level' => 'low',
                                            'set_vars' => ['$approved' => true]
                                        ],
                                        [
                                            'if' => ['op' => '>=', 'value' => 30000],
                                            'score' => 75,
                                            'risk_level' => 'medium'
                                        ]
                                    ]
                                ],
                                [
                                    'if' => ['op' => '>', 'value' => 45],
                                    'ranges' => [
                                        [
                                            'if' => ['op' => '>=', 'value' => 60000],
                                            'score' => 90,
                                            'risk_level' => 'low'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        // Test case 1: Young with good income
        $inputs1 = ['age' => 30, 'income' => 60000];
        $result1 = $this->engine->evaluate($config, $inputs1);
        
        $this->assertEquals(100, $result1['risk_assessment']);
        $this->assertEquals('low', $result1['risk_assessment_risk_level']);
        $this->assertEquals(true, $result1['approved']);
        
        // Test case 2: Older with high income
        $inputs2 = ['age' => 50, 'income' => 70000];
        $result2 = $this->engine->evaluate($config, $inputs2);
        
        $this->assertEquals(90, $result2['risk_assessment']);
        $this->assertEquals('low', $result2['risk_assessment_risk_level']);
        
        echo "✅ Multi-dimensional scoring passed\n";
    }
    
    /**
     * Test complex business calculation with functions
     */
    public function testComplexBusinessCalculation(): void
    {
        $config = [
            'formulas' => [
                [
                    'id' => 'monthly_income',
                    'formula' => 'annual_salary / 12',
                    'inputs' => ['annual_salary'],
                    'as' => '$monthly'
                ],
                [
                    'id' => 'debt_ratio',
                    'formula' => 'percentage(monthly_debt, monthly)',
                    'inputs' => ['monthly_debt', 'monthly'],
                    'as' => '$debt_percentage'
                ],
                [
                    'id' => 'max_loan_calc',
                    'formula' => 'monthly * 0.28 * 12 * 30', // 28% of monthly income for 30 years
                    'inputs' => ['monthly'],
                    'as' => '$max_loan'
                ],
                [
                    'id' => 'loan_eligibility',
                    'switch' => '$debt_percentage',
                    'when' => [
                        [
                            'if' => ['op' => '<=', 'value' => 36],
                            'result' => 'Excellent',
                            'set_vars' => ['$loan_multiplier' => 1.0]
                        ],
                        [
                            'if' => ['op' => '<=', 'value' => 43],
                            'result' => 'Good',
                            'set_vars' => ['$loan_multiplier' => 0.8]
                        ]
                    ],
                    'default' => 'Poor',
                    'default_vars' => ['$loan_multiplier' => 0.5]
                ],
                [
                    'id' => 'final_loan_amount',
                    'formula' => 'max_loan * loan_multiplier',
                    'inputs' => ['max_loan', 'loan_multiplier'],
                    'as' => '$approved_amount'
                ]
            ]
        ];
        
        $inputs = [
            'annual_salary' => 80000,
            'monthly_debt' => 2000
        ];
        
        $result = $this->engine->evaluate($config, $inputs);
        
        $this->assertEquals(6666.67, round($result['monthly'], 2));
        $this->assertEquals(30, round($result['debt_percentage']));
        $this->assertEquals('Excellent', $result['loan_eligibility']);
        $this->assertEquals(1.0, $result['loan_multiplier']);
        $this->assertGreaterThan(224000, $result['approved_amount']);
        
        echo "✅ Complex business calculation passed\n";
    }
    
    /**
     * Test code generation
     */
    public function testCodeGeneration(): void
    {
        $config = [
            'formulas' => [
                [
                    'id' => 'simple_calc',
                    'formula' => 'a + b * 2',
                    'inputs' => ['a', 'b']
                ],
                [
                    'id' => 'conditional',
                    'switch' => 'simple_calc',
                    'when' => [
                        ['if' => ['op' => '>', 'value' => 10], 'result' => 'High'],
                        ['if' => ['op' => '>', 'value' => 5], 'result' => 'Medium']
                    ],
                    'default' => 'Low'
                ]
            ]
        ];
        
        $generatedCode = $this->engine->generateFunctionAsString($config);
        
        // Check that code contains expected elements
        $this->assertStringContains('function(array $inputs): array', $generatedCode);
        $this->assertStringContains('$context = $inputs', $generatedCode);
        $this->assertStringContains('simple_calc', $generatedCode);
        $this->assertStringContains('return $context', $generatedCode);
        
        // Test that generated code actually works
        $generatedFunction = eval("return $generatedCode;");
        $result = $generatedFunction(['a' => 3, 'b' => 4]);
        
        $this->assertEquals(11, $result['simple_calc']); // 3 + 4*2
        $this->assertEquals('High', $result['conditional']);
        
        echo "✅ Code generation passed\n";
    }
    
    /**
     * Test input validation and error handling
     */
    public function testInputValidationAndErrorHandling(): void
    {
        $config = [
            'formulas' => [
                [
                    'id' => 'calculation',
                    'formula' => 'x + y',
                    'inputs' => ['x', 'y']
                ]
            ]
        ];
        
        // Test missing inputs
        try {
            $this->engine->evaluate($config, ['x' => 5]); // Missing y
            $this->fail('Should throw exception for missing input');
        } catch (RuleFlowException $e) {
            $this->assertStringContains('Missing input', $e->getMessage());
            
            // เช็ค getMissingInput method อย่างปลอดภัย
            $missingInput = null;
            if (method_exists($e, 'getMissingInput')) {
                $missingInput = $e->getMissingInput();
            }
            
            if ($missingInput !== null) {
                $this->assertEquals('y', $missingInput);
            } else {
                // หาก getMissingInput ไม่มีหรือ return null ให้เช็คใน message แทน
                $messageContainsY = strpos($e->getMessage(), 'y') !== false;
                if (!$messageContainsY) {
                    throw new Exception("Missing input 'y' should be mentioned in error message: " . $e->getMessage());
                }
            }
        }
        
        // Test invalid configuration
        $invalidConfig = [
            'formulas' => [
                [
                    // Missing 'id' field
                    'formula' => 'a + b',
                    'inputs' => ['a', 'b']
                ]
            ]
        ];
        
        $errors = $this->engine->validateConfig($invalidConfig);
        $this->assertNotEmpty($errors);
        $this->assertStringContains('Missing required \'id\' field', $errors[0]);
        
        echo "✅ Input validation and error handling passed\n";
    }

    //  assertTrue ที่หายไป
    private function assertTrue(bool $condition, string $message = ''): void
    {
        if (!$condition) {
            throw new Exception("Assertion failed: Expected true. $message");
        }
    }
    
    /**
     * Test custom functions
     */
    public function testCustomFunctions(): void
    {
        // Register custom function
        $this->engine->registerFunction('triple', function($x) {
            return $x * 3;
        });
        
        $config = [
            'formulas' => [
                [
                    'id' => 'custom_calc',
                    'formula' => 'triple(value) + 10',
                    'inputs' => ['value']
                ]
            ]
        ];
        
        $result = $this->engine->evaluate($config, ['value' => 5]);
        $this->assertEquals(25, $result['custom_calc']); // 5*3 + 10
        
        // Check that custom function is listed
        $functions = $this->engine->getAvailableFunctions();
        $this->assertContains('triple', $functions);
        
        echo "✅ Custom functions passed\n";
    }
    
    /**
     * Test complete workflow with $ notation
     */
    public function testDollarNotationWorkflow(): void
    {
        $config = [
            'formulas' => [
                [
                    'id' => 'base_calculation',
                    'formula' => 'price * quantity',
                    'inputs' => ['price', 'quantity'],
                    'as' => '$subtotal'
                ],
                [
                    'id' => 'tax_calculation',
                    'formula' => 'subtotal * tax_rate',
                    'inputs' => ['subtotal', 'tax_rate'],
                    'as' => '$tax_amount'
                ],
                [
                    'id' => 'discount_check',
                    'switch' => '$subtotal',
                    'when' => [
                        [
                            'if' => ['op' => '>=', 'value' => 100],
                            'result' => 'Premium',
                            'set_vars' => ['$discount_rate' => 0.1]
                        ],
                        [
                            'if' => ['op' => '>=', 'value' => 50],
                            'result' => 'Standard',
                            'set_vars' => ['$discount_rate' => 0.05]
                        ]
                    ],
                    'default' => 'Basic',
                    'default_vars' => ['$discount_rate' => 0]
                ],
                [
                    'id' => 'final_total',
                    'formula' => 'subtotal + tax_amount - (subtotal * discount_rate)',
                    'inputs' => ['subtotal', 'tax_amount', 'discount_rate'],
                    'as' => '$total'
                ]
            ]
        ];
        
        $inputs = [
            'price' => 25,
            'quantity' => 5,
            'tax_rate' => 0.08
        ];
        
        $result = $this->engine->evaluate($config, $inputs);
        
        $this->assertEquals(125, $result['subtotal']); // 25 * 5
        $this->assertEquals(10, $result['tax_amount']); // 125 * 0.08
        $this->assertEquals('Premium', $result['discount_check']); // >= 100
        $this->assertEquals(0.1, $result['discount_rate']); // Premium discount
        $this->assertEquals(122.5, $result['total']); // 125 + 10 - 12.5
        
        echo "✅ Dollar notation workflow passed\n";
    }
    
    // Helper assertion methods
    private function assertEquals($expected, $actual, string $message = ''): void
    {
        if (is_float($expected) || is_float($actual)) {
            if (abs($expected - $actual) > 0.01) {
                throw new Exception("Assertion failed: Expected $expected, got $actual. $message");
            }
        } else {
            if ($expected !== $actual) {
                throw new Exception("Assertion failed: Expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ". $message");
            }
        }
    }
    
    private function assertGreaterThan($expected, $actual): void
    {
        if ($actual <= $expected) {
            throw new Exception("Assertion failed: $actual should be greater than $expected");
        }
    }
    
    private function assertStringContains(string $needle, string $haystack): void
    {
        if (strpos($haystack, $needle) === false) {
            throw new Exception("Assertion failed: '$needle' not found in '$haystack'");
        }
    }
    
    private function assertNotEmpty(array $array): void
    {
        if (empty($array)) {
            throw new Exception("Assertion failed: Array should not be empty");
        }
    }
    
    private function assertContains($needle, array $haystack): void
    {
        if (!in_array($needle, $haystack)) {
            throw new Exception("Assertion failed: '$needle' not found in array");
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
        echo "🧪 Running RuleFlow Integration Tests...\n\n";
        
        $this->setUp();
        
        try {
            $this->testBMICalculatorWorkflow();
            $this->testCreditScoringWorkflow();
            $this->testMultiDimensionalScoring();
            $this->testComplexBusinessCalculation();
            $this->testCodeGeneration();
            $this->testInputValidationAndErrorHandling();
            $this->testCustomFunctions();
            $this->testDollarNotationWorkflow();
            
            echo "\n🎉 All Integration tests passed!\n\n";
        } catch (Exception $e) {
            echo "\n❌ Test failed: " . $e->getMessage() . "\n\n";
            throw $e;
        }
    }
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new RuleFlowIntegrationTest();
    $test->runAllTests();
}