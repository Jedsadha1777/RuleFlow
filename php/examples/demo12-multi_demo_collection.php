<?php 
require_once __DIR__ . "/../src/RuleFlow.php";

/**
 * Demo 1: Simple BMI Calculator
 */
function demo1_bmi_calculator() {
    echo "\n=== DEMO 1: BMI Calculator ===\n";
    
    $config = [
        'formulas' => [
            [
                'id' => 'bmi_calculation',
                'formula' => 'weight / (height ** 2)',
                'inputs' => ['weight', 'height'],
                'as' => '$bmi'
            ],
            [
                'id' => 'bmi_category',
                'switch' => '$bmi',
                'when' => [
                    [
                        'if' => ['op' => '<', 'value' => 18.5],
                        'result' => 'Underweight'
                    ],
                    [
                        'if' => ['op' => 'between', 'value' => [18.5, 24.9]],
                        'result' => 'Normal'
                    ],
                    [
                        'if' => ['op' => 'between', 'value' => [25, 29.9]],
                        'result' => 'Overweight'
                    ]
                ],
                'default' => 'Obese'
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    $inputs = [
        'weight' => 70,  // kg
        'height' => 1.75  // meters
    ];
    
    $result = $ruleFlow->evaluate($config, $inputs);
    
    echo "Input: Weight={$inputs['weight']}kg, Height={$inputs['height']}m\n";
    echo "BMI: " . round($result['bmi'], 2) . "\n";
    echo "Category: {$result['bmi_category']}\n";

    echo "\n====PHP===\n";
    echo $ruleFlow->generateFunctionAsString($config);
    echo "\n====END===\n\n";
}

/**
 * Demo 2: Credit Scoring System
 */
function demo2_credit_scoring() {
    echo "\n=== DEMO 2: Credit Scoring System ===\n";
    
    $config = [
        'formulas' => [
            [
                'id' => 'credit',
                'rules' => [
                    [
                        'var' => 'income',
                        'ranges' => [
                            ['if' => ['op' => '>=', 'value' => 100000], 'result' => 40],
                            ['if' => ['op' => '>=', 'value' => 50000], 'result' => 25],
                            ['if' => ['op' => '>=', 'value' => 30000], 'result' => 15]
                        ]
                    ],
                    [
                        'var' => 'age',
                        'ranges' => [
                            ['if' => ['op' => 'between', 'value' => [25, 45]], 'result' => 20],
                            ['if' => ['op' => 'between', 'value' => [46, 60]], 'result' => 15],
                            ['if' => ['op' => '>=', 'value' => 18], 'result' => 10]
                        ]
                    ],
                    [
                        'var' => 'employment_years',
                        'ranges' => [
                            ['if' => ['op' => '>=', 'value' => 5], 'result' => 20],
                            ['if' => ['op' => '>=', 'value' => 2], 'result' => 15],
                            ['if' => ['op' => '>=', 'value' => 1], 'result' => 10]
                        ]
                    ],
                    [
                        'var' => 'has_property',
                        'if' => ['op' => '==', 'value' => 1],
                        'result' => 20
                    ]
                ]
            ],
            [
                'id' => 'loan_decision',
                'switch' => '$credit',
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
    
    $ruleFlow = new RuleFlow();
    
    $inputs = [
        'income' => 75000,
        'age' => 35,
        'employment_years' => 8,
        'has_property' => 1
    ];
    
    $result = $ruleFlow->evaluate($config, $inputs);
    
    echo "Applicant Profile:\n";
    echo "- Income: ฿{$inputs['income']}\n";
    echo "- Age: {$inputs['age']} years\n";
    echo "- Employment: {$inputs['employment_years']} years\n";
    echo "- Has Property: " . ($inputs['has_property'] ? 'Yes' : 'No') . "\n";
    echo "\nCredit Score: {$result['credit']}/100\n";
    echo "Decision: {$result['loan_decision']}\n";
    if (isset($result['interest_rate'])) {
        echo "Interest Rate: {$result['interest_rate']}%\n";
        echo "Max Loan Amount: ฿{$result['max_amount']}\n";
    }

    echo "\n====PHP===\n";
    echo $ruleFlow->generateFunctionAsString($config);
    echo "\n====END===\n\n";
}

/**
 * Demo 3: Blood Pressure Assessment
 */
function demo3_blood_pressure() {
    echo "\n=== DEMO 3: Blood Pressure Assessment ===\n";
    
    $config = [
        'formulas' => [
            [
                'id' => 'gender_score',
                'rules' => [
                    [
                        'var' => 'gender',
                        'if' => ['op' => '==', 'value' => 'female'],
                        'result' => 1
                    ]
                ]
            ],
            [
                'id' => 'age_systolic_assessment',
                'scoring' => [
                    'ifs' => [
                        'vars' => ['age', 'systolic'],
                        'tree' => [
                            [
                                'if' => ['op' => 'between', 'value' => [30, 50]],
                                'ranges' => [
                                    [
                                        'if' => ['op' => '<', 'value' => 120],
                                        'score' => 0,
                                        'risk_level' => 'low'
                                    ],
                                    [
                                        'if' => ['op' => 'between', 'value' => [120, 139]],
                                        'score' => 5,
                                        'risk_level' => 'medium'
                                    ],
                                    [
                                        'if' => ['op' => '>=', 'value' => 140],
                                        'score' => 10,
                                        'risk_level' => 'high',
                                        'set_vars' => ['$requires_medication' => true]
                                    ]
                                ]
                            ],
                            [
                                'if' => ['op' => '>', 'value' => 50],
                                'ranges' => [
                                    [
                                        'if' => ['op' => '<', 'value' => 120],
                                        'score' => 2,
                                        'risk_level' => 'low'
                                    ],
                                    [
                                        'if' => ['op' => 'between', 'value' => [120, 139]],
                                        'score' => 8,
                                        'risk_level' => 'medium'
                                    ],
                                    [
                                        'if' => ['op' => '>=', 'value' => 140],
                                        'score' => 15,
                                        'risk_level' => 'high',
                                        'set_vars' => ['$requires_medication' => true]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                'id' => 'total_risk_score',
                'formula' => 'gender_score + age_systolic_assessment',
                'inputs' => ['gender_score', 'age_systolic_assessment'],
                'as' => '$final_risk_score'
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    $inputs = [
        'gender' => 'female',
        'age' => 45,
        'systolic' => 135
    ];
    
    $result = $ruleFlow->evaluate($config, $inputs);
    
    echo "Patient: {$inputs['gender']}, {$inputs['age']} years old\n";
    echo "Systolic BP: {$inputs['systolic']} mmHg\n";
    echo "Risk Score: {$result['final_risk_score']}\n";
    echo "Risk Level: {$result['age_systolic_assessment_risk_level']}\n";
    if (isset($result['requires_medication'])) {
        echo "Requires Medication: Yes\n";
    }

    echo "\n====PHP===\n";
    echo $ruleFlow->generateFunctionAsString($config);
    echo "\n====END===\n\n";
}

/**
 * Demo 4: E-commerce Discount System
 */
function demo4_discount_system() {
    echo "\n=== DEMO 4: E-commerce Discount System ===\n";
    
    $config = [
        'formulas' => [
            [
                'id' => 'customer_tier',
                'switch' => 'total_spent',
                'when' => [
                    [
                        'if' => ['op' => '>=', 'value' => 100000],
                        'result' => 'platinum',
                        'set_vars' => ['$base_discount' => 15]
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 50000],
                        'result' => 'gold',
                        'set_vars' => ['$base_discount' => 10]
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 10000],
                        'result' => 'silver',
                        'set_vars' => ['$base_discount' => 5]
                    ]
                ],
                'default' => 'bronze',
                'default_vars' => ['$base_discount' => 0]
            ],
            [
                'id' => 'bonus_discount',
                'rules' => [
                    [
                        'var' => 'is_birthday_month',
                        'if' => ['op' => '==', 'value' => 1],
                        'result' => 5
                    ],
                    [
                        'var' => 'order_amount',
                        'ranges' => [
                            ['if' => ['op' => '>=', 'value' => 5000], 'result' => 3],
                            ['if' => ['op' => '>=', 'value' => 2000], 'result' => 2],
                            ['if' => ['op' => '>=', 'value' => 1000], 'result' => 1]
                        ]
                    ],
                    [
                        'var' => 'is_first_order',
                        'if' => ['op' => '==', 'value' => 1],
                        'result' => 10
                    ]
                ]
            ],
            [
                'id' => 'final_discount_calc',
                'formula' => 'min(base_discount + bonus_discount, 25)',
                'inputs' => ['base_discount', 'bonus_discount'],
                'as' => '$discount_percent'
            ],
            [
                'id' => 'discount_amount_calc',
                'formula' => 'order_amount * discount_percent / 100',
                'inputs' => ['order_amount', 'discount_percent'],
                'as' => '$discount_amount'
            ],
            [
                'id' => 'final_price_calc',
                'formula' => 'order_amount - discount_amount',
                'inputs' => ['order_amount', 'discount_amount'],
                'as' => '$final_price'
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    $inputs = [
        'total_spent' => 75000,
        'order_amount' => 3500,
        'is_birthday_month' => 1,
        'is_first_order' => 0
    ];
    
    $result = $ruleFlow->evaluate($config, $inputs);
    
    echo "Customer Profile:\n";
    echo "- Total Spent: ฿{$inputs['total_spent']}\n";
    echo "- Current Order: ฿{$inputs['order_amount']}\n";
    echo "- Birthday Month: " . ($inputs['is_birthday_month'] ? 'Yes' : 'No') . "\n";
    echo "- First Order: " . ($inputs['is_first_order'] ? 'Yes' : 'No') . "\n";
    echo "\nTier: {$result['customer_tier']}\n";
    echo "Discount: {$result['discount_percent']}%\n";
    echo "Discount Amount: ฿{$result['discount_amount']}\n";
    echo "Final Price: ฿{$result['final_price']}\n";

    echo "\n====PHP===\n";
    echo $ruleFlow->generateFunctionAsString($config);
    echo "\n====END===\n\n";
}

/**
 * Demo 5: Academic Grading System (CORRECTED)
 */
function demo5_grading_system() {
    echo "\n=== DEMO 5: Academic Grading System ===\n";
    
    $config = [
        'formulas' => [
            [
                'id' => 'weighted_score_calc',
                'formula' => '(midterm * 0.3) + (final * 0.4) + (assignments * 0.2) + (attendance * 0.1)',
                'inputs' => ['midterm', 'final', 'assignments', 'attendance'],
                'as' => '$total_score'
            ],
            [
                'id' => 'attendance_bonus',
                'switch' => 'attendance',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 100],
                        'result' => 2,
                        'set_vars' => ['$has_bonus' => true, '$bonus_reason' => 'Perfect Attendance']
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 95],
                        'result' => 1,
                        'set_vars' => ['$has_bonus' => true, '$bonus_reason' => 'Excellent Attendance']
                    ]
                ],
                'default' => 0
            ],
            [
                'id' => 'final_score_with_bonus',
                'formula' => 'min(total_score + attendance_bonus, 100)',
                'inputs' => ['total_score', 'attendance_bonus'],
                'as' => '$final_score'
            ],
            [
                'id' => 'letter_grade',
                'switch' => '$final_score',
                'when' => [
                    [
                        'if' => ['op' => '>=', 'value' => 80],
                        'result' => 'A',
                        'set_vars' => ['$gpa' => 4.0, '$status' => 'Excellent']
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 75],
                        'result' => 'B+',
                        'set_vars' => ['$gpa' => 3.5, '$status' => 'Very Good']
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 70],
                        'result' => 'B',
                        'set_vars' => ['$gpa' => 3.0, '$status' => 'Good']
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 65],
                        'result' => 'C+',
                        'set_vars' => ['$gpa' => 2.5, '$status' => 'Fair']
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 60],
                        'result' => 'C',
                        'set_vars' => ['$gpa' => 2.0, '$status' => 'Satisfactory']
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 55],
                        'result' => 'D+',
                        'set_vars' => ['$gpa' => 1.5, '$status' => 'Pass']
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 50],
                        'result' => 'D',
                        'set_vars' => ['$gpa' => 1.0, '$status' => 'Pass']
                    ]
                ],
                'default' => 'F',
                'default_vars' => ['$gpa' => 0.0, '$status' => 'Fail']
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    $inputs = [
        'midterm' => 85,
        'final' => 78,
        'assignments' => 90,
        'attendance' => 95
    ];
    
    $result = $ruleFlow->evaluate($config, $inputs);
    
    echo "Student Scores:\n";
    echo "- Midterm: {$inputs['midterm']}/100 (30%)\n";
    echo "- Final: {$inputs['final']}/100 (40%)\n";
    echo "- Assignments: {$inputs['assignments']}/100 (20%)\n";
    echo "- Attendance: {$inputs['attendance']}/100 (10%)\n";
    echo "\nWeighted Score: " . round($result['total_score'], 2) . "/100\n";
    if ($result['attendance_bonus'] > 0) {
        echo "Attendance Bonus: +{$result['attendance_bonus']} ({$result['bonus_reason']})\n";
        echo "Final Score: " . round($result['final_score'], 2) . "/100\n";
    }
    echo "Letter Grade: {$result['letter_grade']}\n";
    echo "GPA: {$result['gpa']}\n";
    echo "Status: {$result['status']}\n";

    echo "\n====PHP===\n";
    echo $ruleFlow->generateFunctionAsString($config);
    echo "\n====END===\n\n";
}

/**
 * Demo 6: Advanced Product Pricing (CORRECTED)
 */
function demo6_dynamic_pricing() {
    echo "\n=== DEMO 6: Dynamic Product Pricing ===\n";
    
    $config = [
        'formulas' => [
            [
                'id' => 'base_price_calc',
                'formula' => 'cost * markup_multiplier',
                'inputs' => ['cost', 'markup_multiplier'],
                'as' => '$base_price'
            ],
            [
                'id' => 'demand_adjustment',
                'switch' => 'demand_level',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'high'],
                        'result' => 'surge',
                        'set_vars' => [
                            '$demand_multiplier' => 1.2,
                            '$priority_fee' => '$base_price * 0.1'
                        ]
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'medium'],
                        'result' => 'normal',
                        'set_vars' => [
                            '$demand_multiplier' => 1.0,
                            '$priority_fee' => 0
                        ]
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'low'],
                        'result' => 'discount',
                        'set_vars' => [
                            '$demand_multiplier' => 0.85,
                            '$priority_fee' => 0
                        ]
                    ]
                ],
                'default' => 'normal',
                'default_vars' => [
                    '$demand_multiplier' => 1.0,
                    '$priority_fee' => 0
                ]
            ],
            [
                'id' => 'seasonal_pricing',
                'rules' => [
                    [
                        'var' => 'is_peak_season',
                        'if' => ['op' => '==', 'value' => 1],
                        'result' => 15,
                        'set_vars' => ['$seasonal_note' => 'Peak Season']
                    ],
                    [
                        'var' => 'is_holiday',
                        'if' => ['op' => '==', 'value' => 1],
                        'result' => 10,
                        'set_vars' => ['$holiday_note' => 'Holiday Premium']
                    ]
                ]
            ],
            [
                'id' => 'final_price_calculation',
                'formula' => '(base_price * demand_multiplier) + priority_fee + seasonal_pricing',
                'inputs' => ['base_price', 'demand_multiplier', 'priority_fee', 'seasonal_pricing'],
                'as' => '$final_price'
            ],
            [
                'id' => 'price_tier_classification',
                'switch' => '$final_price',
                'when' => [
                    [
                        'if' => ['op' => '>=', 'value' => 1000],
                        'result' => 'Premium',
                        'set_vars' => [
                            '$shipping_cost' => 0,
                            '$priority_support' => true
                        ]
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 500],
                        'result' => 'Standard',
                        'set_vars' => [
                            '$shipping_cost' => 50,
                            '$priority_support' => false
                        ]
                    ]
                ],
                'default' => 'Economy',
                'default_vars' => [
                    '$shipping_cost' => 100,
                    '$priority_support' => false
                ]
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    $inputs = [
        'cost' => 400,
        'markup_multiplier' => 1.5,
        'demand_level' => 'high',
        'is_peak_season' => 1,
        'is_holiday' => 0
    ];
    
    $result = $ruleFlow->evaluate($config, $inputs);
    
    echo "Product Pricing Details:\n";
    echo "- Base Cost: ฿{$inputs['cost']}\n";
    echo "- Markup: {$inputs['markup_multiplier']}x\n";
    echo "- Demand Level: {$inputs['demand_level']}\n";
    echo "- Peak Season: " . ($inputs['is_peak_season'] ? 'Yes' : 'No') . "\n";
    echo "- Holiday: " . ($inputs['is_holiday'] ? 'Yes' : 'No') . "\n";
    echo "\nPricing Breakdown:\n";
    echo "- Base Price: ฿{$result['base_price']}\n";
    echo "- Demand Adjustment: {$result['demand_adjustment']} ({$result['demand_multiplier']}x)\n";
    if ($result['priority_fee'] > 0) {
        echo "- Priority Fee: ฿{$result['priority_fee']}\n";
    }
    if ($result['seasonal_pricing'] > 0) {
        echo "- Seasonal Premium: ฿{$result['seasonal_pricing']}\n";
        if (isset($result['seasonal_note'])) echo "  ({$result['seasonal_note']})\n";
        if (isset($result['holiday_note'])) echo "  ({$result['holiday_note']})\n";
    }
    echo "\nFinal Price: ฿{$result['final_price']}\n";
    echo "Price Tier: {$result['price_tier_classification']}\n";
    echo "Shipping Cost: ฿{$result['shipping_cost']}\n";
    echo "Priority Support: " . ($result['priority_support'] ? 'Yes' : 'No') . "\n";

    echo "\n====PHP===\n";
    echo $ruleFlow->generateFunctionAsString($config);
    echo "\n====END===\n\n";
}

// Run all demos
echo "RuleFlow Demo Examples (Updated with \$ Notation)\n";
echo "====================================================\n";

demo1_bmi_calculator();
demo2_credit_scoring();
demo3_blood_pressure();
demo4_discount_system();
demo5_grading_system();
demo6_dynamic_pricing();

echo "\n✅ All demos completed!\n";