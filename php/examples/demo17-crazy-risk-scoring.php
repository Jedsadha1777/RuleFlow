<?php
/**
 * Example 17: Crazy Risk Scoring with Nested Logic & Mathematical Chaos
 * 
 * Demonstrates:
 * - Complex mathematical formulas with trigonometric functions
 * - Deeply nested AND/OR conditions
 * - Variable chaining and dependencies
 * - Conditional variable setting
 * - Absurd business logic that somehow works
 */

require_once __DIR__ . '/../src/RuleFlow.php';

$ruleFlow = new RuleFlow();

// Configuration: Risk Assessment System (แบบ HR คิดเอง)
$config = [
    'formulas' => [
        // Step 1: Calculate base risk using ridiculous formula
        [
            'id' => 'age_factor',
            'formula' => 'sqrt(age * 100)',
            'inputs' => ['age'],
            'as' => '$age_factor'
        ],
        
        [
            'id' => 'income_factor', 
            'formula' => 'sqrt(income) / 100',
            'inputs' => ['income'],
            'as' => '$income_factor'
        ],
        
        [
            'id' => 'credit_factor',
            'formula' => 'credit_score / 100',
            'inputs' => ['credit_score'],
            'as' => '$credit_factor'
        ],
        
        // Step 2: Combine factors with crazy formula
        [
            'id' => 'risk_base',
            'formula' => '($age_factor * $income_factor) * sin($credit_factor)',
            'inputs' => ['$age_factor', '$income_factor', '$credit_factor'],
            'as' => '$base'
        ],
        
        // Step 3: Month chaos (เปลี่ยนจาก % เป็น /)
        [
            'id' => 'month_chaos',
            'formula' => 'floor(month / 3) + 1',
            'inputs' => ['month'],
            'as' => '$month_factor'
        ],
        
        // Step 4: Loan size impact
        [
            'id' => 'loan_impact',
            'formula' => 'min(10, loan_amount / 100000)',
            'inputs' => ['loan_amount'],
            'as' => '$loan_factor'
        ],
        
        // Step 5: Calculate chaos
        [
            'id' => 'chaos_factor',
            'formula' => '$month_factor * $loan_factor',
            'inputs' => ['$month_factor', '$loan_factor'],
            'as' => '$chaos'
        ],
        
        // Step 6: Final score
        [
            'id' => 'final_score',
            'formula' => 'max(0, min(100, $base * $chaos))',
            'inputs' => ['$base', '$chaos'],
            'as' => '$score'
        ],
        
        // Step 7: Complex nested logic for risk categorization
        [
            'id' => 'risk_level',
            'switch' => 'trigger',
            'when' => [
                // VIP: High score + (High income OR (Good credit AND Young))
                [
                    'if' => [
                        'and' => [
                            ['op' => '>', 'var' => '$score', 'value' => 70],
                            [
                                'or' => [
                                    ['op' => '>', 'var' => 'income', 'value' => 100000],
                                    [
                                        'and' => [
                                            ['op' => '>=', 'var' => 'credit_score', 'value' => 700],
                                            ['op' => '<', 'var' => 'age', 'value' => 40]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'result' => 'VIP',
                    'set_vars' => [
                        '$rate' => 3.5,
                        '$max_loan_multiplier' => 10
                    ]
                ],
                // NORMAL: Medium score OR (High chaos AND Employed)
                [
                    'if' => [
                        'or' => [
                            ['op' => 'between', 'var' => '$score', 'value' => [30, 70]],
                            [
                                'and' => [
                                    ['op' => '>', 'var' => '$chaos', 'value' => 3],
                                    ['op' => '>=', 'var' => 'years_employed', 'value' => 2]
                                ]
                            ]
                        ]
                    ],
                    'result' => 'NORMAL',
                    'set_vars' => [
                        '$rate' => 5.5,
                        '$max_loan_multiplier' => 5
                    ]
                ],
                // BRONZE: Low score but employed
                [
                    'if' => [
                        'and' => [
                            ['op' => '>=', 'var' => '$score', 'value' => 10],
                            ['op' => '>=', 'var' => 'years_employed', 'value' => 1]
                        ]
                    ],
                    'result' => 'BRONZE',
                    'set_vars' => [
                        '$rate' => 8.5,
                        '$max_loan_multiplier' => 3
                    ]
                ]
            ],
            'default' => 'REJECT',
            'set_vars' => [
                '$rate' => 0,
                '$max_loan_multiplier' => 0
            ]
        ],
        
        // Step 8: Calculate max loan amount
        [
            'id' => 'max_loan',
            'formula' => 'income * $max_loan_multiplier',
            'inputs' => ['income', '$max_loan_multiplier']
        ],
        
        // Step 9: Calculate monthly payment
        [
            'id' => 'monthly_payment',
            'formula' => 'loan_amount * $rate / 100 / 12',
            'inputs' => ['loan_amount', '$rate']
        ]
    ]
];

// Test Case 1: Young professional with good credit
echo "===========================================\n";
echo "Example 17: Crazy Risk Scoring System\n";
echo "===========================================\n\n";

$customer1 = [
    'trigger' => 'evaluate',
    'age' => 32,
    'income' => 120000,
    'credit_score' => 750,
    'month' => 9,  // September affects chaos factor!
    'loan_amount' => 500000,
    'years_employed' => 3
];

echo "Test Case 1: Young Professional\n";
echo "--------------------------------\n";

try {
    $result1 = $ruleFlow->evaluate($config, $customer1);
    
    // Check structure and use correct keys
    if (is_array($result1)) {
        echo "📊 Input:\n";
        echo "  Age: {$customer1['age']}\n";
        echo "  Income: $" . number_format($customer1['income']) . "\n";
        echo "  Credit Score: {$customer1['credit_score']}\n";
        echo "  Loan Amount: $" . number_format($customer1['loan_amount']) . "\n\n";
        
        echo "🎲 Crazy Calculations:\n";
        echo "  Age Factor: " . number_format($result1['age_factor'] ?? 0, 2) . "\n";
        echo "  Income Factor: " . number_format($result1['income_factor'] ?? 0, 2) . "\n";
        echo "  Credit Factor: " . number_format($result1['credit_factor'] ?? 0, 2) . "\n";
        echo "  Risk Base: " . number_format($result1['risk_base'] ?? 0, 2) . "\n";
        echo "  Month Chaos: " . number_format($result1['month_chaos'] ?? 0, 2) . "\n";
        echo "  Loan Impact: " . number_format($result1['loan_impact'] ?? 0, 2) . "\n";
        echo "  Chaos Factor: " . number_format($result1['chaos_factor'] ?? 0, 2) . "\n";
        echo "  Final Score: " . number_format($result1['final_score'] ?? 0, 2) . "\n\n";
        
        echo "✅ Decision:\n";
        echo "  Risk Level: " . ($result1['risk_level'] ?? 'N/A') . "\n";
        echo "  Interest Rate: " . ($result1['rate'] ?? 0) . "%\n";
        echo "  Max Loan: $" . number_format($result1['max_loan'] ?? 0) . "\n";
        echo "  Monthly Payment: $" . number_format($result1['monthly_payment'] ?? 0, 2) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test Case 2: Older person with lower income
echo "\n\nTest Case 2: Senior Citizen\n";
echo "--------------------------------\n";

$customer2 = [
    'trigger' => 'evaluate',
    'age' => 58,
    'income' => 65000,
    'credit_score' => 680,
    'month' => 12,  // December = different chaos!
    'loan_amount' => 200000,
    'years_employed' => 15
];

try {
    $result2 = $ruleFlow->evaluate($config, $customer2);
    
    if (is_array($result2)) {
        echo "📊 Input:\n";
        echo "  Age: {$customer2['age']}\n";
        echo "  Income: $" . number_format($customer2['income']) . "\n";
        echo "  Credit Score: {$customer2['credit_score']}\n";
        echo "  Loan Amount: $" . number_format($customer2['loan_amount']) . "\n\n";
        
        echo "🎲 Crazy Calculations:\n";
        echo "  Age Factor: " . number_format($result2['age_factor'] ?? 0, 2) . "\n";
        echo "  Income Factor: " . number_format($result2['income_factor'] ?? 0, 2) . "\n";
        echo "  Credit Factor: " . number_format($result2['credit_factor'] ?? 0, 2) . "\n";
        echo "  Risk Base: " . number_format($result2['risk_base'] ?? 0, 2) . "\n";
        echo "  Month Chaos: " . number_format($result2['month_chaos'] ?? 0, 2) . "\n";
        echo "  Loan Impact: " . number_format($result2['loan_impact'] ?? 0, 2) . "\n";
        echo "  Chaos Factor: " . number_format($result2['chaos_factor'] ?? 0, 2) . "\n";
        echo "  Final Score: " . number_format($result2['final_score'] ?? 0, 2) . "\n\n";
        
        echo "✅ Decision:\n";
        echo "  Risk Level: " . ($result2['risk_level'] ?? 'N/A') . "\n";
        echo "  Interest Rate: " . ($result2['rate'] ?? 0) . "%\n";
        echo "  Max Loan: $" . number_format($result2['max_loan'] ?? 0) . "\n";
        echo "  Monthly Payment: $" . number_format($result2['monthly_payment'] ?? 0, 2) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n===========================================\n";
echo "💡 Why this example is crazy:\n";
echo "-------------------------------------------\n";
echo "1. Age Factor = sqrt(age * 100) ???\n";
echo "2. Income Factor = sqrt(income) / 100 ???\n";
echo "3. Risk = (age * income) * sin(credit/100) ???\n";
echo "4. Month affects loan approval: floor(month/3) + 1\n";
echo "5. VIP: score>70 AND (income>100k OR (credit>=700 AND age<40))\n";
echo "6. Maximum loan = income × 10 for VIPs (insane!)\n";
echo "7. All wrapped in nested logic 3 levels deep\n";
echo "\nYet it runs perfectly! Try this in BPMN... 😂\n";
echo "===========================================\n";