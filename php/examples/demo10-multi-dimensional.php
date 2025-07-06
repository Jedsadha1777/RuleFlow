<?php

require_once __DIR__ . '/../src/RuleFlow.php';

echo "RuleFlow Demo: Multi-Dimensional Scoring\n";
echo "========================================\n\n";

$ruleFlow = new RuleFlow();

// Real-world case: Employee Bonus Calculation (Performance vs Tenure)
echo "1. Employee Bonus Matrix (Performance vs Tenure)\n";
$bonusConfig = [
    'formulas' => [
        [
            'id' => 'bonus_percentage',
            'scoring' => [
                'ifs' => [
                    'vars' => ['performance_rating', 'years_tenure'],
                    'tree' => [
                        [
                            'if' => ['op' => '>=', 'value' => 90], // Excellent performance
                            'ranges' => [
                                ['if' => ['op' => '>=', 'value' => 5], 'score' => 20, 'level' => 'Top Performer'],
                                ['if' => ['op' => '>=', 'value' => 2], 'score' => 15, 'level' => 'High Achiever'],
                                ['if' => ['op' => '>=', 'value' => 1], 'score' => 12, 'level' => 'Rising Star']
                            ]
                        ],
                        [
                            'if' => ['op' => 'between', 'value' => [75, 89]], // Good performance
                            'ranges' => [
                                ['if' => ['op' => '>=', 'value' => 5], 'score' => 12, 'level' => 'Solid Contributor'],
                                ['if' => ['op' => '>=', 'value' => 2], 'score' => 8, 'level' => 'Good Employee'],
                                ['if' => ['op' => '>=', 'value' => 1], 'score' => 6, 'level' => 'Developing']
                            ]
                        ],
                        [
                            'if' => ['op' => 'between', 'value' => [60, 74]], // Average performance
                            'ranges' => [
                                ['if' => ['op' => '>=', 'value' => 5], 'score' => 6, 'level' => 'Experienced'],
                                ['if' => ['op' => '>=', 'value' => 2], 'score' => 4, 'level' => 'Standard'],
                                ['if' => ['op' => '>=', 'value' => 1], 'score' => 2, 'level' => 'Basic']
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            'id' => 'bonus_amount',
            'formula' => 'base_salary * bonus_percentage / 100',
            'inputs' => ['base_salary', 'bonus_percentage']
        ]
    ]
];

$employees = [
    ['name' => 'Sarah (Star)', 'performance_rating' => 95, 'years_tenure' => 6, 'base_salary' => 85000],
    ['name' => 'Mike (Solid)', 'performance_rating' => 82, 'years_tenure' => 4, 'base_salary' => 70000],
    ['name' => 'Lisa (New)', 'performance_rating' => 88, 'years_tenure' => 1.5, 'base_salary' => 65000],
    ['name' => 'Tom (Veteran)', 'performance_rating' => 72, 'years_tenure' => 8, 'base_salary' => 75000]
];

foreach ($employees as $employee) {
    $inputs = [
        'performance_rating' => $employee['performance_rating'],
        'years_tenure' => $employee['years_tenure'],
        'base_salary' => $employee['base_salary']
    ];
    
    $result = $ruleFlow->evaluate($bonusConfig, $inputs);
    
    echo "{$employee['name']}: Performance {$employee['performance_rating']}, Tenure {$employee['years_tenure']} years\n";
    echo "  Level: {$result['bonus_percentage_level']}\n";
    echo "  Bonus: {$result['bonus_percentage']}% = $" . number_format($result['bonus_amount']) . "\n\n";
}

// Real-world case: Credit Card Limit (Income vs Credit Score)
echo "2. Credit Card Limit Matrix (Income vs Credit Score)\n";
$creditConfig = [
    'formulas' => [
        [
            'id' => 'credit_limit',
            'scoring' => [
                'ifs' => [
                    'vars' => ['credit_score', 'monthly_income'],
                    'tree' => [
                        [
                            'if' => ['op' => '>=', 'value' => 750], // Excellent credit
                            'ranges' => [
                                ['if' => ['op' => '>=', 'value' => 8000], 'score' => 25000, 'tier' => 'Platinum'],
                                ['if' => ['op' => '>=', 'value' => 5000], 'score' => 15000, 'tier' => 'Gold'],
                                ['if' => ['op' => '>=', 'value' => 3000], 'score' => 10000, 'tier' => 'Silver']
                            ]
                        ],
                        [
                            'if' => ['op' => 'between', 'value' => [650, 749]], // Good credit
                            'ranges' => [
                                ['if' => ['op' => '>=', 'value' => 6000], 'score' => 15000, 'tier' => 'Gold'],
                                ['if' => ['op' => '>=', 'value' => 4000], 'score' => 8000, 'tier' => 'Silver'],
                                ['if' => ['op' => '>=', 'value' => 2500], 'score' => 5000, 'tier' => 'Bronze']
                            ]
                        ],
                        [
                            'if' => ['op' => 'between', 'value' => [600, 649]], // Fair credit
                            'ranges' => [
                                ['if' => ['op' => '>=', 'value' => 5000], 'score' => 8000, 'tier' => 'Silver'],
                                ['if' => ['op' => '>=', 'value' => 3000], 'score' => 3000, 'tier' => 'Bronze'],
                                ['if' => ['op' => '>=', 'value' => 2000], 'score' => 1500, 'tier' => 'Starter']
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            'id' => 'annual_fee',
            'switch' => 'credit_limit_tier',
            'when' => [
                ['if' => ['op' => '==', 'value' => 'Platinum'], 'result' => 500],
                ['if' => ['op' => '==', 'value' => 'Gold'], 'result' => 200],
                ['if' => ['op' => '==', 'value' => 'Silver'], 'result' => 100]
            ],
            'default' => 0
        ]
    ]
];

$applicants = [
    ['name' => 'Executive', 'credit_score' => 780, 'monthly_income' => 12000],
    ['name' => 'Manager', 'credit_score' => 720, 'monthly_income' => 6500],
    ['name' => 'Professional', 'credit_score' => 680, 'monthly_income' => 4500],
    ['name' => 'Graduate', 'credit_score' => 630, 'monthly_income' => 3200]
];

foreach ($applicants as $applicant) {
    $inputs = [
        'credit_score' => $applicant['credit_score'],
        'monthly_income' => $applicant['monthly_income']
    ];
    
    $result = $ruleFlow->evaluate($creditConfig, $inputs);
    
    echo "{$applicant['name']}: Score {$applicant['credit_score']}, Income $" . number_format($applicant['monthly_income']) . "\n";
    echo "  Tier: {$result['credit_limit_tier']}\n";
    echo "  Limit: $" . number_format($result['credit_limit']) . "\n";
    echo "  Annual Fee: $" . number_format($result['annual_fee']) . "\n\n";
}

// Real-world case: Product Pricing (Market Demand vs Competition)
echo "3. Dynamic Product Pricing (Demand vs Competition)\n";
$pricingConfig = [
    'formulas' => [
        [
            'id' => 'price_multiplier',
            'scoring' => [
                'ifs' => [
                    'vars' => ['demand_level', 'competitor_price_ratio'],
                    'tree' => [
                        [
                            'if' => ['op' => '==', 'value' => 3], // High demand (3)
                            'ranges' => [
                                ['if' => ['op' => '<=', 'value' => 0.9], 'score' => 1.25, 'strategy' => 'Premium'],
                                ['if' => ['op' => '<=', 'value' => 1.1], 'score' => 1.15, 'strategy' => 'Competitive'],
                                ['if' => ['op' => '>', 'value' => 1.1], 'score' => 1.05, 'strategy' => 'Value']
                            ]
                        ],
                        [
                            'if' => ['op' => '==', 'value' => 2], // Medium demand (2)
                            'ranges' => [
                                ['if' => ['op' => '<=', 'value' => 0.9], 'score' => 1.1, 'strategy' => 'Competitive'],
                                ['if' => ['op' => '<=', 'value' => 1.1], 'score' => 1.0, 'strategy' => 'Match'],
                                ['if' => ['op' => '>', 'value' => 1.1], 'score' => 0.95, 'strategy' => 'Undercut']
                            ]
                        ],
                        [
                            'if' => ['op' => '==', 'value' => 1], // Low demand (1)
                            'ranges' => [
                                ['if' => ['op' => '<=', 'value' => 0.9], 'score' => 1.0, 'strategy' => 'Match'],
                                ['if' => ['op' => '<=', 'value' => 1.1], 'score' => 0.9, 'strategy' => 'Discount'],
                                ['if' => ['op' => '>', 'value' => 1.1], 'score' => 0.8, 'strategy' => 'Clearance']
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            'id' => 'final_price',
            'formula' => 'base_price * price_multiplier',
            'inputs' => ['base_price', 'price_multiplier']
        ]
    ]
];

$products = [
    ['name' => 'Hot Item', 'demand_level' => 3, 'competitor_price_ratio' => 0.85, 'base_price' => 100],
    ['name' => 'Popular', 'demand_level' => 2, 'competitor_price_ratio' => 1.05, 'base_price' => 150],
    ['name' => 'Slow Mover', 'demand_level' => 1, 'competitor_price_ratio' => 1.2, 'base_price' => 80]
];

foreach ($products as $product) {
    $inputs = [
        'demand_level' => $product['demand_level'],
        'competitor_price_ratio' => $product['competitor_price_ratio'],
        'base_price' => $product['base_price']
    ];
    
    $result = $ruleFlow->evaluate($pricingConfig, $inputs);
    
    $demand_text = ['1' => 'Low', '2' => 'Medium', '3' => 'High'][$product['demand_level']];
    echo "{$product['name']}: {$demand_text} demand, vs competitor " . ($product['competitor_price_ratio'] * 100) . "%\n";
    echo "  Strategy: {$result['price_multiplier_strategy']}\n";
    echo "  Price: $" . number_format($product['base_price']) . " → $" . number_format($result['final_price']) . "\n\n";
}

echo "Multi-dimensional scoring complete.\n";
echo "Demonstrated: Employee bonuses, Credit limits, Dynamic pricing\n";

echo "\nDemo completed.\n";