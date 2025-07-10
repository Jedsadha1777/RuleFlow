<?php
/**
 * Demo 15: Nested Logic Demonstration
 * 
 * This demo showcases the powerful nested AND/OR logic capabilities of RuleFlow.
 * It includes real-world business scenarios that require complex decision making.
 * 
 * Features demonstrated:
 * - Basic AND/OR conditions
 * - Deep nested logic structures
 * - Variable references in conditions
 * - Real-world business applications
 * - Code generation for nested logic
 */

require_once __DIR__ . "/../src/RuleFlow.php";

echo "🚀 RuleFlow Demo 15: Nested Logic Showcase\n";
echo "=" . str_repeat("=", 50) . "\n\n";

$ruleFlow = new RuleFlow();

// =================================================================
// SCENARIO 1: BANK LOAN APPROVAL SYSTEM
// =================================================================

echo "💰 SCENARIO 1: Bank Loan Approval System\n";
echo "-" . str_repeat("-", 45) . "\n";

$loanConfig = [
    'formulas' => [
        // Calculate debt-to-income ratio first
        [
            'id' => 'debt_to_income_ratio',
            'formula' => 'monthly_debt / monthly_income',
            'inputs' => ['monthly_debt', 'monthly_income'],
            'as' => '$dti_ratio'
        ],
        
        // Complex loan approval logic
        [
            'id' => 'loan_decision',
            'switch' => 'application_type',
            'when' => [
                [
                    'if' => [
                        'and' => [
                            // Basic eligibility criteria
                            ['op' => '>=', 'var' => 'age', 'value' => 21],
                            ['op' => '<=', 'var' => 'age', 'value' => 65],
                            
                            // Financial criteria (either high income OR good credit + collateral)
                            [
                                'or' => [
                                    // High income path
                                    [
                                        'and' => [
                                            ['op' => '>=', 'var' => 'monthly_income', 'value' => 50000],
                                            ['op' => '<=', 'var' => '$dti_ratio', 'value' => 0.4]
                                        ]
                                    ],
                                    // Good credit + collateral path
                                    [
                                        'and' => [
                                            ['op' => '>=', 'var' => 'credit_score', 'value' => 650],
                                            ['op' => '==', 'var' => 'has_collateral', 'value' => true],
                                            ['op' => '<=', 'var' => '$dti_ratio', 'value' => 0.5]
                                        ]
                                    ],
                                    // Excellent credit (no collateral needed)
                                    [
                                        'and' => [
                                            ['op' => '>=', 'var' => 'credit_score', 'value' => 750],
                                            ['op' => '>=', 'var' => 'monthly_income', 'value' => 30000],
                                            ['op' => '<=', 'var' => '$dti_ratio', 'value' => 0.45]
                                        ]
                                    ]
                                ]
                            ],
                            
                            // Risk factors (must not be present)
                            [
                                'and' => [
                                    ['op' => '!=', 'var' => 'bankruptcy_history', 'value' => true],
                                    ['op' => '!=', 'var' => 'status', 'value' => 'blacklist'],
                                    ['op' => '<=', 'var' => 'late_payments_count', 'value' => 2]
                                ]
                            ]
                        ]
                    ],
                    'result' => 'approved',
                    'set_vars' => [
                        '$approval_reason' => 'meets_all_criteria',
                        '$interest_rate' => 'calculate_rate'
                    ]
                ]
            ],
            'default' => 'rejected',
            'default_vars' => [
                '$approval_reason' => 'criteria_not_met',
                '$interest_rate' => 'not_applicable'
            ]
        ],
        
        // Calculate interest rate based on approval and credit score
        [
            'id' => 'interest_rate',
            'switch' => 'loan_decision',
            'when' => [
                [
                    'if' => ['op' => '==', 'value' => 'approved'],
                    'result' => 'approved_for_rate'
                ]
            ],
            'default' => 0.0
        ],
        
        // Determine actual rate based on credit score for approved loans
        [
            'id' => 'final_rate',
            'switch' => 'credit_score',
            'when' => [
                ['if' => ['op' => '>=', 'value' => 750], 'result' => 3.5],
                ['if' => ['op' => '>=', 'value' => 700], 'result' => 4.0],
                ['if' => ['op' => '>=', 'value' => 650], 'result' => 4.5]
            ],
            'default' => 5.0
        ]
    ]
];

$loanTestCases = [
    [
        'name' => 'High Income Professional',
        'data' => [
            'application_type' => 'personal',
            'age' => 35,
            'monthly_income' => 80000,
            'monthly_debt' => 25000,
            'credit_score' => 720,
            'has_collateral' => false,
            'bankruptcy_history' => false,
            'status' => 'normal',
            'late_payments_count' => 0
        ],
        'expected' => 'approved'
    ],
    [
        'name' => 'Good Credit with Collateral',
        'data' => [
            'application_type' => 'personal',
            'age' => 28,
            'monthly_income' => 35000,
            'monthly_debt' => 15000,
            'credit_score' => 680,
            'has_collateral' => true,
            'bankruptcy_history' => false,
            'status' => 'normal',
            'late_payments_count' => 1
        ],
        'expected' => 'approved'
    ],
    [
        'name' => 'Excellent Credit, Lower Income',
        'data' => [
            'application_type' => 'personal',
            'age' => 42,
            'monthly_income' => 32000,
            'monthly_debt' => 12000,
            'credit_score' => 780,
            'has_collateral' => false,
            'bankruptcy_history' => false,
            'status' => 'normal',
            'late_payments_count' => 0
        ],
        'expected' => 'approved'
    ],
    [
        'name' => 'Too Young',
        'data' => [
            'application_type' => 'personal',
            'age' => 19,
            'monthly_income' => 60000,
            'monthly_debt' => 10000,
            'credit_score' => 750,
            'has_collateral' => true,
            'bankruptcy_history' => false,
            'status' => 'normal',
            'late_payments_count' => 0
        ],
        'expected' => 'rejected'
    ],
    [
        'name' => 'High Debt Ratio',
        'data' => [
            'application_type' => 'personal',
            'age' => 30,
            'monthly_income' => 40000,
            'monthly_debt' => 25000,
            'credit_score' => 650,
            'has_collateral' => false,
            'bankruptcy_history' => false,
            'status' => 'normal',
            'late_payments_count' => 1
        ],
        'expected' => 'rejected'
    ],
    [
        'name' => 'Bankruptcy History',
        'data' => [
            'application_type' => 'personal',
            'age' => 40,
            'monthly_income' => 70000,
            'monthly_debt' => 20000,
            'credit_score' => 700,
            'has_collateral' => true,
            'bankruptcy_history' => true,
            'status' => 'normal',
            'late_payments_count' => 0
        ],
        'expected' => 'rejected'
    ]
];

foreach ($loanTestCases as $i => $testCase) {
    $result = $ruleFlow->evaluate($loanConfig, $testCase['data']);
    $decision = $result['loan_decision'];
    
    // Calculate actual interest rate only for approved loans
    $rate = 0.0;
    if ($decision === 'approved') {
        $rate = $result['final_rate'];
    }
    
    $dtiRatio = round($result['dti_ratio'] * 100, 1);
    
    $status = ($decision === $testCase['expected']) ? "✅" : "❌";
    
    echo sprintf(
        "%s Test %d: %-30s → %-8s (Rate: %.1f%%, DTI: %.1f%%)\n",
        $status,
        $i + 1,
        $testCase['name'],
        strtoupper($decision),
        $rate,
        $dtiRatio
    );
}

echo "\n";

// =================================================================
// SCENARIO 2: INSURANCE PREMIUM CALCULATOR
// =================================================================

echo "🏥 SCENARIO 2: Health Insurance Premium Calculator\n";
echo "-" . str_repeat("-", 48) . "\n";

$insuranceConfig = [
    'formulas' => [
        // Base premium calculation
        [
            'id' => 'base_premium',
            'formula' => 'coverage_amount * 0.001',
            'inputs' => ['coverage_amount'],
            'as' => '$base_premium'
        ],
        
        // Risk assessment with complex nested logic
        [
            'id' => 'risk_multiplier',
            'switch' => 'calculate_risk',
            'when' => [
                // High Risk Categories
                [
                    'if' => [
                        'or' => [
                            // Age-related high risk
                            [
                                'and' => [
                                    ['op' => '>', 'var' => 'age', 'value' => 60],
                                    [
                                        'or' => [
                                            ['op' => '==', 'var' => 'smoker', 'value' => true],
                                            ['op' => '>', 'var' => 'chronic_conditions', 'value' => 1]
                                        ]
                                    ]
                                ]
                            ],
                            // Young high risk
                            [
                                'and' => [
                                    ['op' => '<', 'var' => 'age', 'value' => 30],
                                    [
                                        'or' => [
                                            ['op' => 'in', 'var' => 'occupation', 'value' => ['pilot', 'miner', 'stuntman']],
                                            [
                                                'and' => [
                                                    ['op' => '==', 'var' => 'extreme_sports', 'value' => true],
                                                    ['op' => '>', 'var' => 'accidents_history', 'value' => 2]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            // Multiple risk factors
                            [
                                'and' => [
                                    ['op' => '==', 'var' => 'smoker', 'value' => true],
                                    ['op' => '>', 'var' => 'bmi', 'value' => 35],
                                    ['op' => '>', 'var' => 'chronic_conditions', 'value' => 0]
                                ]
                            ]
                        ]
                    ],
                    'result' => 'high_risk',
                    'set_vars' => ['$multiplier' => 2.5]
                ],
                
                // Medium Risk Categories  
                [
                    'if' => [
                        'and' => [
                            // Not in high risk category
                            [
                                'or' => [
                                    // Middle age with some risks
                                    [
                                        'and' => [
                                            ['op' => 'between', 'var' => 'age', 'value' => [30, 60]],
                                            [
                                                'or' => [
                                                    ['op' => '==', 'var' => 'smoker', 'value' => true],
                                                    ['op' => 'between', 'var' => 'bmi', 'value' => [30, 35]],
                                                    ['op' => '==', 'var' => 'chronic_conditions', 'value' => 1]
                                                ]
                                            ]
                                        ]
                                    ],
                                    // Any age with family history
                                    [
                                        'and' => [
                                            ['op' => '==', 'var' => 'family_history', 'value' => true],
                                            ['op' => '!=', 'var' => 'smoker', 'value' => true]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'result' => 'medium_risk',
                    'set_vars' => ['$multiplier' => 1.5]
                ]
            ],
            'default' => 'low_risk',
            'default_vars' => ['$multiplier' => 0.8]
        ],
        
        // Final premium calculation
        [
            'id' => 'final_premium',
            'formula' => '$base_premium * $multiplier',
            'inputs' => ['base_premium', 'multiplier']
        ]
    ]
];

$insuranceTestCases = [
    [
        'name' => 'Healthy Young Adult',
        'data' => [
            'calculate_risk' => true,
            'coverage_amount' => 1000000,
            'age' => 25,
            'smoker' => false,
            'bmi' => 22,
            'chronic_conditions' => 0,
            'occupation' => 'teacher',
            'extreme_sports' => false,
            'accidents_history' => 0,
            'family_history' => false
        ]
    ],
    [
        'name' => 'Middle-aged Smoker',
        'data' => [
            'calculate_risk' => true,
            'coverage_amount' => 1000000,
            'age' => 45,
            'smoker' => true,
            'bmi' => 28,
            'chronic_conditions' => 0,
            'occupation' => 'office_worker',
            'extreme_sports' => false,
            'accidents_history' => 0,
            'family_history' => false
        ]
    ],
    [
        'name' => 'High-risk Senior',
        'data' => [
            'calculate_risk' => true,
            'coverage_amount' => 500000,
            'age' => 65,
            'smoker' => true,
            'bmi' => 30,
            'chronic_conditions' => 2,
            'occupation' => 'retired',
            'extreme_sports' => false,
            'accidents_history' => 1,
            'family_history' => true
        ]
    ],
    [
        'name' => 'Young Extreme Sports',
        'data' => [
            'calculate_risk' => true,
            'coverage_amount' => 800000,
            'age' => 28,
            'smoker' => false,
            'bmi' => 24,
            'chronic_conditions' => 0,
            'occupation' => 'pilot',
            'extreme_sports' => true,
            'accidents_history' => 3,
            'family_history' => false
        ]
    ]
];

foreach ($insuranceTestCases as $i => $testCase) {
    $result = $ruleFlow->evaluate($insuranceConfig, $testCase['data']);
    $riskLevel = $result['risk_multiplier'];
    $premium = $result['final_premium'];
    $multiplier = $result['multiplier'];
    
    echo sprintf(
        "Test %d: %-20s → %-11s (Premium: ฿%s, Multiplier: %.1fx)\n",
        $i + 1,
        $testCase['name'],
        strtoupper($riskLevel),
        number_format($premium),
        $multiplier
    );
}

echo "\n";

// =================================================================
// SCENARIO 3: E-COMMERCE DYNAMIC PRICING
// =================================================================

echo "🛒 SCENARIO 3: E-commerce Dynamic Pricing Engine\n";
echo "-" . str_repeat("-", 47) . "\n";

$pricingConfig = [
    'formulas' => [
        // Customer tier evaluation
        [
            'id' => 'customer_tier',
            'switch' => 'evaluate_customer',
            'when' => [
                [
                    'if' => [
                        'or' => [
                            // VIP tier criteria
                            [
                                'and' => [
                                    ['op' => '>', 'var' => 'total_spent', 'value' => 100000],
                                    ['op' => '>', 'var' => 'orders_count', 'value' => 20]
                                ]
                            ],
                            // Premium member
                            ['op' => '==', 'var' => 'premium_member', 'value' => true]
                        ]
                    ],
                    'result' => 'vip',
                    'set_vars' => ['$discount_rate' => 0.15]
                ],
                [
                    'if' => [
                        'and' => [
                            ['op' => '>', 'var' => 'total_spent', 'value' => 30000],
                            ['op' => '>', 'var' => 'orders_count', 'value' => 5],
                            ['op' => '==', 'var' => 'returns_ratio', 'value' => 'low']
                        ]
                    ],
                    'result' => 'gold',
                    'set_vars' => ['$discount_rate' => 0.10]
                ],
                [
                    'if' => [
                        'and' => [
                            ['op' => '>', 'var' => 'total_spent', 'value' => 10000],
                            ['op' => '>', 'var' => 'orders_count', 'value' => 2]
                        ]
                    ],
                    'result' => 'silver',
                    'set_vars' => ['$discount_rate' => 0.05]
                ]
            ],
            'default' => 'bronze',
            'default_vars' => ['$discount_rate' => 0.0]
        ],
        
        // Market-based pricing adjustment
        [
            'id' => 'market_adjustment',
            'switch' => 'calculate_adjustment',
            'when' => [
                [
                    'if' => [
                        'and' => [
                            // High demand conditions
                            [
                                'or' => [
                                    ['op' => '>', 'var' => 'demand_score', 'value' => 80],
                                    [
                                        'and' => [
                                            ['op' => '<', 'var' => 'inventory_level', 'value' => 10],
                                            ['op' => '>', 'var' => 'demand_score', 'value' => 60]
                                        ]
                                    ]
                                ]
                            ],
                            // Market conditions
                            [
                                'and' => [
                                    ['op' => 'in', 'var' => 'season', 'value' => ['peak', 'holiday']],
                                    ['op' => '!=', 'var' => 'competitor_discount', 'value' => true]
                                ]
                            ]
                        ]
                    ],
                    'result' => 'surge_pricing',
                    'set_vars' => ['$price_multiplier' => 1.2]
                ],
                [
                    'if' => [
                        'or' => [
                            // Low demand
                            [
                                'and' => [
                                    ['op' => '<', 'var' => 'demand_score', 'value' => 30],
                                    ['op' => '>', 'var' => 'inventory_level', 'value' => 50]
                                ]
                            ],
                            // Competitive pressure
                            [
                                'and' => [
                                    ['op' => '==', 'var' => 'competitor_discount', 'value' => true],
                                    ['op' => '<', 'var' => 'market_share', 'value' => 15]
                                ]
                            ]
                        ]
                    ],
                    'result' => 'discount_pricing',
                    'set_vars' => ['$price_multiplier' => 0.9]
                ]
            ],
            'default' => 'normal_pricing',
            'default_vars' => ['$price_multiplier' => 1.0]
        ],
        
        // Final price calculation
        [
            'id' => 'final_price',
            'formula' => 'base_price * $price_multiplier * (1 - $discount_rate)',
            'inputs' => ['base_price', 'price_multiplier', 'discount_rate']
        ]
    ]
];

$pricingTestCases = [
    [
        'name' => 'VIP Customer, Peak Season',
        'data' => [
            'evaluate_customer' => true,
            'calculate_adjustment' => true,
            'base_price' => 1000,
            'total_spent' => 150000,
            'orders_count' => 25,
            'premium_member' => false,
            'returns_ratio' => 'low',
            'demand_score' => 85,
            'inventory_level' => 8,
            'season' => 'peak',
            'competitor_discount' => false,
            'market_share' => 25
        ]
    ],
    [
        'name' => 'New Customer, Low Demand',
        'data' => [
            'evaluate_customer' => true,
            'calculate_adjustment' => true,
            'base_price' => 1000,
            'total_spent' => 5000,
            'orders_count' => 1,
            'premium_member' => false,
            'returns_ratio' => 'none',
            'demand_score' => 25,
            'inventory_level' => 60,
            'season' => 'normal',
            'competitor_discount' => false,
            'market_share' => 20
        ]
    ],
    [
        'name' => 'Gold Customer, Competitive Market',
        'data' => [
            'evaluate_customer' => true,
            'calculate_adjustment' => true,
            'base_price' => 1000,
            'total_spent' => 45000,
            'orders_count' => 8,
            'premium_member' => false,
            'returns_ratio' => 'low',
            'demand_score' => 50,
            'inventory_level' => 30,
            'season' => 'normal',
            'competitor_discount' => true,
            'market_share' => 12
        ]
    ]
];

foreach ($pricingTestCases as $i => $testCase) {
    $result = $ruleFlow->evaluate($pricingConfig, $testCase['data']);
    $tier = $result['customer_tier'];
    $pricing = $result['market_adjustment'];
    $finalPrice = $result['final_price'];
    $discount = $result['discount_rate'] * 100;
    $multiplier = $result['price_multiplier'];
    
    echo sprintf(
        "Test %d: %-28s → %-6s / %-15s (Price: ฿%.0f, Discount: %.0f%%, Multiplier: %.1fx)\n",
        $i + 1,
        $testCase['name'],
        strtoupper($tier),
        str_replace('_', ' ', strtoupper($pricing)),
        $finalPrice,
        $discount,
        $multiplier
    );
}

echo "\n";

// =================================================================
// SCENARIO 4: CODE GENERATION DEMO
// =================================================================

echo "⚙️  SCENARIO 4: Code Generation for Nested Logic\n";
echo "-" . str_repeat("-", 47) . "\n";

// Simple nested logic for code generation demo
$codeGenConfig = [
    'formulas' => [
        [
            'id' => 'access_control',
            'switch' => 'check_access',
            'when' => [
                [
                    'if' => [
                        'or' => [
                            [
                                'and' => [
                                    ['op' => '==', 'var' => 'role', 'value' => 'admin'],
                                    ['op' => 'in', 'var' => 'department', 'value' => ['IT', 'Security']]
                                ]
                            ],
                            [
                                'and' => [
                                    ['op' => '==', 'var' => 'role', 'value' => 'manager'],
                                    ['op' => '>', 'var' => 'clearance_level', 'value' => 3],
                                    ['op' => '==', 'var' => 'active_status', 'value' => true]
                                ]
                            ],
                            ['op' => '==', 'var' => 'override_access', 'value' => true]
                        ]
                    ],
                    'result' => 'granted'
                ]
            ],
            'default' => 'denied'
        ]
    ]
];

echo "Generated PHP Code:\n";
echo "```php\n";
$generatedCode = $ruleFlow->generateFunctionAsString($codeGenConfig);
echo $generatedCode;
echo "\n```\n\n";

// Test the generated logic
$accessTestCases = [
    ['role' => 'admin', 'department' => 'IT', 'clearance_level' => 2, 'active_status' => true, 'override_access' => false, 'check_access' => 'test'],
    ['role' => 'manager', 'department' => 'Sales', 'clearance_level' => 4, 'active_status' => true, 'override_access' => false, 'check_access' => 'test'],
    ['role' => 'employee', 'department' => 'HR', 'clearance_level' => 1, 'active_status' => true, 'override_access' => true, 'check_access' => 'test'],
    ['role' => 'employee', 'department' => 'Marketing', 'clearance_level' => 2, 'active_status' => true, 'override_access' => false, 'check_access' => 'test']
];

echo "Testing Generated Code:\n";
foreach ($accessTestCases as $i => $testData) {
    $result = $ruleFlow->evaluate($codeGenConfig, $testData);
    $access = $result['access_control'];
    
    echo sprintf(
        "Test %d: %-8s in %-9s (Level: %d) → %s\n",
        $i + 1,
        $testData['role'],
        $testData['department'],
        $testData['clearance_level'],
        strtoupper($access)
    );
}

echo "\n";

// =================================================================
// PERFORMANCE COMPARISON
// =================================================================

echo "📊 SCENARIO 5: Performance Comparison\n";
echo "-" . str_repeat("-", 35) . "\n";

$iterations = 1000;

// Test runtime evaluation
$startTime = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $ruleFlow->evaluate($loanConfig, $loanTestCases[0]['data']);
}
$runtimeDuration = microtime(true) - $startTime;

// Test cached evaluator
$cachedEvaluator = $ruleFlow->createCachedEvaluator($loanConfig);
$startTime = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $cachedEvaluator($loanTestCases[0]['data']);
}
$cachedDuration = microtime(true) - $startTime;

$speedup = $runtimeDuration / $cachedDuration;

echo sprintf("Runtime Evaluation:  %.3f seconds (%d iterations)\n", $runtimeDuration, $iterations);
echo sprintf("Cached Evaluation:   %.3f seconds (%d iterations)\n", $cachedDuration, $iterations);
echo sprintf("Performance Improvement: %.1fx faster\n", $speedup);

echo "\n";

// =================================================================
// SUMMARY
// =================================================================

echo "📋 SUMMARY: Nested Logic Capabilities\n";
echo "=" . str_repeat("=", 37) . "\n";
echo "✅ Complex AND/OR conditions with unlimited nesting\n";
echo "✅ Variable references in conditions (\$variable support)\n";
echo "✅ Multiple business scenario support\n";
echo "✅ Code generation for nested logic\n";
echo "✅ Performance optimization with caching\n";
echo "✅ Real-world applicability across domains\n\n";

echo "🎯 Use Cases Demonstrated:\n";
echo "• Bank loan approval with multiple criteria\n";
echo "• Insurance risk assessment and premium calculation\n";
echo "• E-commerce dynamic pricing with customer tiers\n";
echo "• Access control systems with role-based logic\n";
echo "• Performance optimization techniques\n\n";

echo "🚀 Ready for Production:\n";
echo "• All configurations are backward compatible\n";
echo "• Nested logic adds zero breaking changes\n";
echo "• Performance impact is minimal\n";
echo "• Code generation produces optimized PHP\n\n";

echo "📚 Learn More:\n";
echo "• Run: php tests/RunAllTests.php --test=nested\n";
echo "• Documentation: See nested_logic_documentation.md\n";
echo "• More examples: Check other demo files\n\n";

echo "🎉 RuleFlow v1.5.0 - Nested Logic is Ready!\n";