<?php


require_once __DIR__ . "/../src/RuleFlow.php";

echo "🚀 RuleFlow Demo: Multi-Dimensional Scoring with \$ Variables\n";
echo "===========================================================\n\n";

$ruleFlow = new RuleFlow();

// =====================================================
// เคส 1: Employee Performance Bonus (ผลงาน vs อายุงาน)
// =====================================================
echo "📊 Case 1: Employee Performance Bonus Matrix\n";
echo "Performance Rating vs Years of Service\n";
echo "-" . str_repeat("-", 50) . "\n";

$bonusConfig = [
    'formulas' => [
        // คำนวณ base performance score
        [
            'id' => 'performance_score',
            'formula' => 'performance_rating * 0.8 + customer_satisfaction * 0.2',
            'inputs' => ['performance_rating', 'customer_satisfaction'],
            'as' => '$perf_score'
        ],
        // Multi-dimensional scoring
        [
            'id' => 'bonus_percentage',
            'scoring' => [
                'ifs' => [
                    'vars' => ['$perf_score', 'years_service'],
                    'tree' => [
                        [
                            'if' => ['op' => '>=', 'value' => 90], // Excellent performance
                            'ranges' => [
                                ['if' => ['op' => '>=', 'value' => 5], 'score' => 25, 'level' => 'Top Performer'],
                                ['if' => ['op' => '>=', 'value' => 2], 'score' => 20, 'level' => 'High Achiever'],
                                ['if' => ['op' => '>=', 'value' => 1], 'score' => 15, 'level' => 'Rising Star']
                            ]
                        ],
                        [
                            'if' => ['op' => 'between', 'value' => [75, 89]], // Good performance
                            'ranges' => [
                                ['if' => ['op' => '>=', 'value' => 5], 'score' => 18, 'level' => 'Veteran'],
                                ['if' => ['op' => '>=', 'value' => 2], 'score' => 12, 'level' => 'Solid Contributor'],
                                ['if' => ['op' => '>=', 'value' => 1], 'score' => 8, 'level' => 'Good Employee']
                            ]
                        ],
                        [
                            'if' => ['op' => 'between', 'value' => [60, 74]], // Average performance
                            'ranges' => [
                                ['if' => ['op' => '>=', 'value' => 5], 'score' => 10, 'level' => 'Steady Worker'],
                                ['if' => ['op' => '>=', 'value' => 2], 'score' => 6, 'level' => 'Standard'],
                                ['if' => ['op' => '>=', 'value' => 1], 'score' => 3, 'level' => 'Basic']
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            'id' => 'bonus_amount',
            'formula' => 'base_salary * (bonus_percentage / 100)',
            'inputs' => ['base_salary', 'bonus_percentage']
        ]
    ]
];

$employees = [
    ['name' => 'Alice', 'performance_rating' => 95, 'customer_satisfaction' => 88, 'years_service' => 6, 'base_salary' => 80000],
    ['name' => 'Bob', 'performance_rating' => 82, 'customer_satisfaction' => 90, 'years_service' => 3, 'base_salary' => 65000],
    ['name' => 'Carol', 'performance_rating' => 70, 'customer_satisfaction' => 75, 'years_service' => 8, 'base_salary' => 75000],
    ['name' => 'David', 'performance_rating' => 88, 'customer_satisfaction' => 85, 'years_service' => 1, 'base_salary' => 55000]
];

foreach ($employees as $emp) {
    $result = $ruleFlow->evaluate($bonusConfig, $emp);
    
    echo sprintf(
        "👤 %s: Performance %.1f, Service %d years → %s (%.1f%% = $%s)\n",
        $emp['name'],
        $result['perf_score'],
        $emp['years_service'],
        $result['bonus_percentage_level'] ?? 'N/A',
        $result['bonus_percentage'],
        number_format($result['bonus_amount'])
    );
}

// =====================================================
// เคส 2: Credit Card Limit (รายได้ vs Credit Score)
// =====================================================
echo "\n💳 Case 2: Credit Card Limit Assessment\n";
echo "Income Level vs Credit Score Matrix\n";
echo "-" . str_repeat("-", 50) . "\n";

$creditConfig = [
    'formulas' => [
        // แปลงรายได้เป็น income tier
        [
            'id' => 'income_tier',
            'switch' => 'annual_income',
            'when' => [
                ['if' => ['op' => '>=', 'value' => 100000], 'result' => 5],
                ['if' => ['op' => '>=', 'value' => 75000], 'result' => 4],
                ['if' => ['op' => '>=', 'value' => 50000], 'result' => 3],
                ['if' => ['op' => '>=', 'value' => 30000], 'result' => 2],
                ['if' => ['op' => '>=', 'value' => 20000], 'result' => 1]
            ],
            'default' => 0,
            'as' => '$income_level'
        ],
        // Multi-dimensional credit limit
        [
            'id' => 'credit_limit',
            'scoring' => [
                'ifs' => [
                    'vars' => ['credit_score', '$income_level'],
                    'tree' => [
                        [
                            'if' => ['op' => '>=', 'value' => 750], // Excellent credit
                            'ranges' => [
                                ['if' => ['op' => '>=', 'value' => 4], 'score' => 200000, 'tier' => 'Platinum Plus'],
                                ['if' => ['op' => '>=', 'value' => 3], 'score' => 150000, 'tier' => 'Platinum'],
                                ['if' => ['op' => '>=', 'value' => 2], 'score' => 100000, 'tier' => 'Gold'],
                                ['if' => ['op' => '>=', 'value' => 1], 'score' => 75000, 'tier' => 'Silver']
                            ]
                        ],
                        [
                            'if' => ['op' => 'between', 'value' => [650, 749]], // Good credit
                            'ranges' => [
                                ['if' => ['op' => '>=', 'value' => 4], 'score' => 120000, 'tier' => 'Gold Plus'],
                                ['if' => ['op' => '>=', 'value' => 3], 'score' => 90000, 'tier' => 'Gold'],
                                ['if' => ['op' => '>=', 'value' => 2], 'score' => 60000, 'tier' => 'Silver'],
                                ['if' => ['op' => '>=', 'value' => 1], 'score' => 40000, 'tier' => 'Bronze']
                            ]
                        ],
                        [
                            'if' => ['op' => 'between', 'value' => [550, 649]], // Fair credit
                            'ranges' => [
                                ['if' => ['op' => '>=', 'value' => 3], 'score' => 50000, 'tier' => 'Silver'],
                                ['if' => ['op' => '>=', 'value' => 2], 'score' => 30000, 'tier' => 'Bronze'],
                                ['if' => ['op' => '>=', 'value' => 1], 'score' => 20000, 'tier' => 'Starter']
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            'id' => 'interest_rate',
            'switch' => 'credit_score',
            'when' => [
                ['if' => ['op' => '>=', 'value' => 750], 'result' => 12.9],
                ['if' => ['op' => '>=', 'value' => 650], 'result' => 16.9],
                ['if' => ['op' => '>=', 'value' => 550], 'result' => 21.9]
            ],
            'default' => 29.9
        ]
    ]
];

$applicants = [
    ['name' => 'Emma', 'credit_score' => 780, 'annual_income' => 120000],
    ['name' => 'Frank', 'credit_score' => 720, 'annual_income' => 65000],
    ['name' => 'Grace', 'credit_score' => 680, 'annual_income' => 45000],
    ['name' => 'Henry', 'credit_score' => 590, 'annual_income' => 85000],
    ['name' => 'Ivy', 'credit_score' => 620, 'annual_income' => 25000]
];

foreach ($applicants as $app) {
    $result = $ruleFlow->evaluate($creditConfig, $app);
    
    echo sprintf(
        "👤 %s: Score %d, Income $%s → %s ($%s limit, %.1f%% APR)\n",
        $app['name'],
        $app['credit_score'],
        number_format($app['annual_income']),
        $result['credit_limit_tier'] ?? 'N/A',
        number_format($result['credit_limit']),
        $result['interest_rate']
    );
}

// =====================================================
// เคส 3: Investment Risk Assessment
// =====================================================
echo "\n📈 Case 3: Investment Risk Assessment Matrix\n";
echo "Risk Tolerance vs Investment Experience\n";
echo "-" . str_repeat("-", 50) . "\n";

$investmentConfig = [
    'formulas' => [
        // คำนวณ composite risk score
        [
            'id' => 'risk_assessment',
            'formula' => '(age * 0.3) + (investment_experience * 10) + (risk_appetite * 5)',
            'inputs' => ['age', 'investment_experience', 'risk_appetite'],
            'as' => '$risk_factor'
        ],
        // Portfolio allocation based on risk factor and income
        [
            'id' => 'portfolio_allocation',
            'scoring' => [
                'ifs' => [
                    'vars' => ['$risk_factor', 'annual_income'],
                    'tree' => [
                        [
                            'if' => ['op' => '>=', 'value' => 80], // High risk tolerance
                            'ranges' => [
                                [
                                    'if' => ['op' => '>=', 'value' => 100000], 
                                    'score' => 70, 
                                    'profile' => 'Aggressive Growth',
                                    'stocks_pct' => 70,
                                    'bonds_pct' => 20,
                                    'alternatives_pct' => 10
                                ],
                                [
                                    'if' => ['op' => '>=', 'value' => 50000], 
                                    'score' => 60, 
                                    'profile' => 'Growth',
                                    'stocks_pct' => 60,
                                    'bonds_pct' => 30,
                                    'alternatives_pct' => 10
                                ]
                            ]
                        ],
                        [
                            'if' => ['op' => 'between', 'value' => [50, 79]], // Moderate risk
                            'ranges' => [
                                [
                                    'if' => ['op' => '>=', 'value' => 75000], 
                                    'score' => 50, 
                                    'profile' => 'Balanced Growth',
                                    'stocks_pct' => 50,
                                    'bonds_pct' => 40,
                                    'alternatives_pct' => 10
                                ],
                                [
                                    'if' => ['op' => '>=', 'value' => 30000], 
                                    'score' => 40, 
                                    'profile' => 'Moderate',
                                    'stocks_pct' => 40,
                                    'bonds_pct' => 50,
                                    'alternatives_pct' => 10
                                ]
                            ]
                        ],
                        [
                            'if' => ['op' => '<', 'value' => 50], // Conservative
                            'ranges' => [
                                [
                                    'if' => ['op' => '>=', 'value' => 50000], 
                                    'score' => 30, 
                                    'profile' => 'Conservative',
                                    'stocks_pct' => 30,
                                    'bonds_pct' => 60,
                                    'alternatives_pct' => 10
                                ],
                                [
                                    'if' => ['op' => '<', 'value' => 50000], 
                                    'score' => 20, 
                                    'profile' => 'Capital Preservation',
                                    'stocks_pct' => 20,
                                    'bonds_pct' => 70,
                                    'alternatives_pct' => 10
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            'id' => 'recommended_amount',
            'formula' => 'annual_income * 0.15',
            'inputs' => ['annual_income']
        ]
    ]
];

$investors = [
    ['name' => 'John', 'age' => 35, 'investment_experience' => 5, 'risk_appetite' => 8, 'annual_income' => 120000],
    ['name' => 'Sarah', 'age' => 28, 'investment_experience' => 2, 'risk_appetite' => 6, 'annual_income' => 75000],
    ['name' => 'Mike', 'age' => 55, 'investment_experience' => 8, 'risk_appetite' => 4, 'annual_income' => 95000],
    ['name' => 'Lisa', 'age' => 45, 'investment_experience' => 1, 'risk_appetite' => 3, 'annual_income' => 60000]
];

foreach ($investors as $inv) {
    $result = $ruleFlow->evaluate($investmentConfig, $inv);
    
    echo sprintf(
        "👤 %s: Age %d, Exp %d, Risk %d → %s (S:%d%% B:%d%% A:%d%%, Invest: $%s/year)\n",
        $inv['name'],
        $inv['age'],
        $inv['investment_experience'],
        $inv['risk_appetite'],
        $result['portfolio_allocation_profile'] ?? 'N/A',
        $result['portfolio_allocation_stocks_pct'] ?? 0,
        $result['portfolio_allocation_bonds_pct'] ?? 0,
        $result['portfolio_allocation_alternatives_pct'] ?? 0,
        number_format($result['recommended_amount'])
    );
}

// =====================================================
// เคส 4: E-commerce Dynamic Pricing
// =====================================================
echo "\n🛒 Case 4: Dynamic Pricing Strategy\n";
echo "Demand Level vs Competitor Position\n";
echo "-" . str_repeat("-", 50) . "\n";

$pricingConfig = [
    'formulas' => [
        // คำนวณ market demand index
        [
            'id' => 'demand_index',
            'formula' => '(search_volume / 1000) + (conversion_rate * 100) + (inventory_turnover * 10)',
            'inputs' => ['search_volume', 'conversion_rate', 'inventory_turnover'],
            'as' => '$market_demand'
        ],
        // Dynamic pricing matrix
        [
            'id' => 'pricing_strategy',
            'scoring' => [
                'ifs' => [
                    'vars' => ['$market_demand', 'competitor_price_ratio'],
                    'tree' => [
                        [
                            'if' => ['op' => '>=', 'value' => 80], // High demand
                            'ranges' => [
                                [
                                    'if' => ['op' => '<=', 'value' => 0.9], 
                                    'score' => 1.4, 
                                    'strategy' => 'Premium Surge',
                                    'price_adjustment' => 'increase_40pct'
                                ],
                                [
                                    'if' => ['op' => '<=', 'value' => 1.1], 
                                    'score' => 1.25, 
                                    'strategy' => 'Market Leader',
                                    'price_adjustment' => 'increase_25pct'
                                ],
                                [
                                    'if' => ['op' => '>', 'value' => 1.1], 
                                    'score' => 1.1, 
                                    'strategy' => 'Competitive Edge',
                                    'price_adjustment' => 'increase_10pct'
                                ]
                            ]
                        ],
                        [
                            'if' => ['op' => 'between', 'value' => [40, 79]], // Medium demand
                            'ranges' => [
                                [
                                    'if' => ['op' => '<=', 'value' => 0.95], 
                                    'score' => 1.15, 
                                    'strategy' => 'Value Plus',
                                    'price_adjustment' => 'increase_15pct'
                                ],
                                [
                                    'if' => ['op' => '<=', 'value' => 1.05], 
                                    'score' => 1.0, 
                                    'strategy' => 'Market Rate',
                                    'price_adjustment' => 'maintain'
                                ],
                                [
                                    'if' => ['op' => '>', 'value' => 1.05], 
                                    'score' => 0.95, 
                                    'strategy' => 'Competitive Price',
                                    'price_adjustment' => 'decrease_5pct'
                                ]
                            ]
                        ],
                        [
                            'if' => ['op' => '<', 'value' => 40], // Low demand
                            'ranges' => [
                                [
                                    'if' => ['op' => '<=', 'value' => 1.0], 
                                    'score' => 0.9, 
                                    'strategy' => 'Clearance Sale',
                                    'price_adjustment' => 'decrease_10pct'
                                ],
                                [
                                    'if' => ['op' => '>', 'value' => 1.0], 
                                    'score' => 0.8, 
                                    'strategy' => 'Fire Sale',
                                    'price_adjustment' => 'decrease_20pct'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            'id' => 'final_price',
            'formula' => 'base_price * pricing_strategy',
            'inputs' => ['base_price', 'pricing_strategy']
        ]
    ]
];

$products = [
    ['name' => 'Hot Gaming Laptop', 'search_volume' => 15000, 'conversion_rate' => 0.12, 'inventory_turnover' => 8, 'competitor_price_ratio' => 0.85, 'base_price' => 1500],
    ['name' => 'Popular Smartphone', 'search_volume' => 8000, 'conversion_rate' => 0.08, 'inventory_turnover' => 6, 'competitor_price_ratio' => 1.05, 'base_price' => 800],
    ['name' => 'Standard Tablet', 'search_volume' => 3000, 'conversion_rate' => 0.06, 'inventory_turnover' => 4, 'competitor_price_ratio' => 1.15, 'base_price' => 400],
    ['name' => 'Old Camera Model', 'search_volume' => 500, 'conversion_rate' => 0.03, 'inventory_turnover' => 2, 'competitor_price_ratio' => 1.3, 'base_price' => 600]
];

foreach ($products as $product) {
    $result = $ruleFlow->evaluate($pricingConfig, $product);
    
    $demand_level = $result['market_demand'] >= 80 ? 'High' : 
                   ($result['market_demand'] >= 40 ? 'Medium' : 'Low');
    
    echo sprintf(
        "📱 %s: %s demand (%.1f), vs competitor %.0f%% → %s ($%s → $%s)\n",
        $product['name'],
        $demand_level,
        $result['market_demand'],
        $product['competitor_price_ratio'] * 100,
        $result['pricing_strategy_strategy'] ?? 'N/A',
        number_format($product['base_price']),
        number_format($result['final_price'])
    );
}

echo "\n✅ Multi-Dimensional Scoring Demo Complete!\n";
echo "📋 Summary of Use Cases:\n";
echo "   • Employee Bonus Matrix (Performance × Service Years)\n";
echo "   • Credit Card Limits (Credit Score × Income Level)\n";
echo "   • Investment Allocation (Risk Factor × Income)\n";
echo "   • Dynamic Pricing (Market Demand × Competitor Position)\n\n";
echo "💡 Key Features Demonstrated:\n";
echo "   • \$ Variable calculations and storage\n";
echo "   • Multi-dimensional scoring trees\n";
echo "   • Nested range conditions\n";
echo "   • Complex business logic matrices\n";
echo "   • Variable setting within scoring rules\n";

?>