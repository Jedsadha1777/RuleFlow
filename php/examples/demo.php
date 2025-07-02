<?php

require_once "../src/RuleEngine.php";

/**
 * Demo 1: Simple BMI Calculator
 */
function demo1_bmi_calculator() {
    echo "\n=== DEMO 1: BMI Calculator ===\n";
    
    $config = [
        'formulas' => [
            [
                'id' => 'bmi_calculation',
                'expression' => 'weight / (height ** 2)',
                'inputs' => ['weight', 'height'],
                'store_as' => 'bmi'
            ],
            [
                'id' => 'bmi_category',
                'switch_on' => 'bmi',
                'cases' => [
                    [
                        'condition' => ['operator' => '<', 'value' => 18.5],
                        'result' => 'Underweight'
                    ],
                    [
                        'condition' => ['operator' => 'between', 'value' => [18.5, 24.9]],
                        'result' => 'Normal'
                    ],
                    [
                        'condition' => ['operator' => 'between', 'value' => [25, 29.9]],
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
}

/**
 * Demo 2: Credit Scoring System
 */
function demo2_credit_scoring() {
    echo "\n=== DEMO 2: Credit Scoring System ===\n";
    
    $config = [
        'formulas' => [
            [
                'id' => 'credit_score',
                'score_rules' => [
                    [
                        'variable' => 'income',
                        'ranges' => [
                            ['condition' => ['operator' => '>=', 'value' => 100000], 'score' => 40],
                            ['condition' => ['operator' => '>=', 'value' => 50000], 'score' => 25],
                            ['condition' => ['operator' => '>=', 'value' => 30000], 'score' => 15]
                        ]
                    ],
                    [
                        'variable' => 'age',
                        'ranges' => [
                            ['condition' => ['operator' => 'between', 'value' => [25, 45]], 'score' => 20],
                            ['condition' => ['operator' => 'between', 'value' => [46, 60]], 'score' => 15],
                            ['condition' => ['operator' => '>=', 'value' => 18], 'score' => 10]
                        ]
                    ],
                    [
                        'variable' => 'employment_years',
                        'ranges' => [
                            ['condition' => ['operator' => '>=', 'value' => 5], 'score' => 20],
                            ['condition' => ['operator' => '>=', 'value' => 2], 'score' => 15],
                            ['condition' => ['operator' => '>=', 'value' => 1], 'score' => 10]
                        ]
                    ],
                    [
                        'variable' => 'has_property',
                        'condition' => ['operator' => '==', 'value' => 1],
                        'score' => 20
                    ]
                ]
            ],
            [
                'id' => 'loan_decision',
                'switch_on' => 'credit_score_score',
                'cases' => [
                    [
                        'condition' => ['operator' => '>=', 'value' => 80],
                        'result' => 'Approved',
                        'set_variables' => ['interest_rate' => 5.5, 'max_amount' => 1000000]
                    ],
                    [
                        'condition' => ['operator' => '>=', 'value' => 60],
                        'result' => 'Approved',
                        'set_variables' => ['interest_rate' => 7.0, 'max_amount' => 500000]
                    ],
                    [
                        'condition' => ['operator' => '>=', 'value' => 40],
                        'result' => 'Conditional',
                        'set_variables' => ['interest_rate' => 9.0, 'max_amount' => 200000]
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
    echo "\nCredit Score: {$result['credit_score_score']}/100\n";
    echo "Decision: {$result['loan_decision']}\n";
    if (isset($result['interest_rate'])) {
        echo "Interest Rate: {$result['interest_rate']}%\n";
        echo "Max Loan Amount: ฿{$result['max_amount']}\n";
    }
}

/**
 * Demo 3: Blood Pressure Assessment (Simplified)
 */
function demo3_blood_pressure() {
    echo "\n=== DEMO 3: Blood Pressure Assessment ===\n";
    
    $config = [
        'formulas' => [
            [
                'id' => 'initialize_score',
                'expression' => '0',
                'inputs' => [],
                'store_as' => 'score'
            ],
            [
                'id' => 'gender_score',
                'score_rules' => [
                    [
                        'variable' => 'gender',
                        'condition' => ['operator' => '==', 'value' => 'female'],
                        'score' => 1
                    ]
                ]
            ],
            [
                'id' => 'age_systolic_score',
                'weight_score' => [
                    'multi_condition' => [
                        'variables' => ['age', 'systolic'],
                        'score_matrix' => [
                            [
                                'condition' => ['operator' => 'between', 'value' => [30, 50]],
                                'ranges' => [
                                    [
                                        'condition' => ['operator' => '<', 'value' => 120],
                                        'score' => 0,
                                        'risk_level' => 'low'
                                    ],
                                    [
                                        'condition' => ['operator' => 'between', 'value' => [120, 139]],
                                        'score' => 5,
                                        'risk_level' => 'medium'
                                    ],
                                    [
                                        'condition' => ['operator' => '>=', 'value' => 140],
                                        'score' => 10,
                                        'risk_level' => 'high',
                                        'set_variables' => ['requires_medication' => true]
                                    ]
                                ]
                            ],
                            [
                                'condition' => ['operator' => '>', 'value' => 50],
                                'ranges' => [
                                    [
                                        'condition' => ['operator' => '<', 'value' => 120],
                                        'score' => 2,
                                        'risk_level' => 'low'
                                    ],
                                    [
                                        'condition' => ['operator' => 'between', 'value' => [120, 139]],
                                        'score' => 8,
                                        'risk_level' => 'medium'
                                    ],
                                    [
                                        'condition' => ['operator' => '>=', 'value' => 140],
                                        'score' => 15,
                                        'risk_level' => 'high',
                                        'set_variables' => ['requires_medication' => true]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                'id' => 'total_score',
                'expression' => 'gender_score_score + age_systolic_score_score',
                'inputs' => ['gender_score_score', 'age_systolic_score_score'],
                'store_as' => 'final_score'
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
    echo "Risk Score: {$result['final_score']}\n";
    echo "Risk Level: {$result['age_systolic_score_risk_level']}\n";
    if (isset($result['requires_medication'])) {
        echo "Requires Medication: Yes\n";
    }
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
                'switch_on' => 'total_spent',
                'cases' => [
                    [
                        'condition' => ['operator' => '>=', 'value' => 100000],
                        'result' => 'platinum',
                        'set_variables' => ['base_discount' => 15]
                    ],
                    [
                        'condition' => ['operator' => '>=', 'value' => 50000],
                        'result' => 'gold',
                        'set_variables' => ['base_discount' => 10]
                    ],
                    [
                        'condition' => ['operator' => '>=', 'value' => 10000],
                        'result' => 'silver',
                        'set_variables' => ['base_discount' => 5]
                    ]
                ],
                'default' => 'bronze',
                'default_variables' => ['base_discount' => 0]
            ],
            [
                'id' => 'set_bronze_discount',
                'switch_on' => 'customer_tier',
                'cases' => [
                    [
                        'condition' => ['operator' => '==', 'value' => 'bronze'],
                        'result' => 0,
                        'set_variables' => ['base_discount' => 0]
                    ]
                ],
                'default' => null
            ],
            [
                'id' => 'bonus_discount',
                'score_rules' => [
                    [
                        'variable' => 'is_birthday_month',
                        'condition' => ['operator' => '==', 'value' => 1],
                        'score' => 5
                    ],
                    [
                        'variable' => 'order_amount',
                        'ranges' => [
                            ['condition' => ['operator' => '>=', 'value' => 5000], 'score' => 3],
                            ['condition' => ['operator' => '>=', 'value' => 2000], 'score' => 2],
                            ['condition' => ['operator' => '>=', 'value' => 1000], 'score' => 1]
                        ]
                    ],
                    [
                        'variable' => 'is_first_order',
                        'condition' => ['operator' => '==', 'value' => 1],
                        'score' => 10
                    ]
                ]
            ],
            [
                'id' => 'final_discount',
                'expression' => 'min(base_discount + bonus_discount_score, 25)',
                'inputs' => ['base_discount', 'bonus_discount_score'],
                'store_as' => 'discount_percent'
            ],
            [
                'id' => 'discount_amount',
                'expression' => 'order_amount * discount_percent / 100',
                'inputs' => ['order_amount', 'discount_percent'],
                'store_as' => 'discount_amount'
            ],
            [
                'id' => 'final_price',
                'expression' => 'order_amount - discount_amount',
                'inputs' => ['order_amount', 'discount_amount'],
                'store_as' => 'final_price'
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
}

/**
 * Demo 5: Academic Grading System
 */
function demo5_grading_system() {
    echo "\n=== DEMO 5: Academic Grading System ===\n";
    
    $config = [
        'formulas' => [
            [
                'id' => 'weighted_score',
                'expression' => '(midterm * 0.3) + (final * 0.4) + (assignments * 0.2) + (attendance * 0.1)',
                'inputs' => ['midterm', 'final', 'assignments', 'attendance'],
                'store_as' => 'total_score'
            ],
            [
                'id' => 'letter_grade',
                'switch_on' => 'total_score',
                'cases' => [
                    [
                        'condition' => ['operator' => '>=', 'value' => 80],
                        'result' => 'A',
                        'set_variables' => ['gpa' => 4.0, 'status' => 'Excellent']
                    ],
                    [
                        'condition' => ['operator' => '>=', 'value' => 75],
                        'result' => 'B+',
                        'set_variables' => ['gpa' => 3.5, 'status' => 'Very Good']
                    ],
                    [
                        'condition' => ['operator' => '>=', 'value' => 70],
                        'result' => 'B',
                        'set_variables' => ['gpa' => 3.0, 'status' => 'Good']
                    ],
                    [
                        'condition' => ['operator' => '>=', 'value' => 65],
                        'result' => 'C+',
                        'set_variables' => ['gpa' => 2.5, 'status' => 'Fair']
                    ],
                    [
                        'condition' => ['operator' => '>=', 'value' => 60],
                        'result' => 'C',
                        'set_variables' => ['gpa' => 2.0, 'status' => 'Satisfactory']
                    ],
                    [
                        'condition' => ['operator' => '>=', 'value' => 55],
                        'result' => 'D+',
                        'set_variables' => ['gpa' => 1.5, 'status' => 'Pass']
                    ],
                    [
                        'condition' => ['operator' => '>=', 'value' => 50],
                        'result' => 'D',
                        'set_variables' => ['gpa' => 1.0, 'status' => 'Pass']
                    ]
                ],
                'default' => 'F',
                'default_variables' => ['gpa' => 0.0, 'status' => 'Fail']
            ],
            [
                'id' => 'bonus_points',
                'weight_score' => [
                    'ranges' => [
                        [
                            'condition' => ['operator' => '==', 'value' => 100],
                            'score' => 2,
                            'set_variables' => ['has_bonus' => true]
                        ],
                        [
                            'condition' => ['operator' => '>=', 'value' => 95],
                            'score' => 1,
                            'set_variables' => ['has_bonus' => true]
                        ]
                    ],
                    'default' => 0
                ]
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
    echo "Letter Grade: {$result['letter_grade']}\n";
    echo "GPA: {$result['gpa']}\n";
    echo "Status: {$result['status']}\n";
    if (isset($result['has_bonus'])) {
        echo "Bonus Points: +{$result['bonus_points_score']}\n";
    }
}

// Run all demos
echo "🚀 RuleFlow Demo Examples\n";
echo "========================\n";

demo1_bmi_calculator();
demo2_credit_scoring();
demo3_blood_pressure();
demo4_discount_system();
demo5_grading_system();

echo "\n✅ All demos completed!\n";