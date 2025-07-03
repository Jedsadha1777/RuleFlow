
<?php 

require_once "../src/RuleFlow.php";

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
                'as' => 'bmi'
            ],
            [
                'id' => 'bmi_category',
                'switch' => 'bmi',
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
    echo $ruleFlow->generateFunctionAsString($config,$inputs);
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
                'switch' => 'credit_score',
                'when' => [
                    [
                        'if' => ['op' => '>=', 'value' => 80],
                        'result' => 'Approved',
                        'set_vars' => ['interest_rate' => 5.5, 'max_amount' => 1000000]
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 60],
                        'result' => 'Approved',
                        'set_vars' => ['interest_rate' => 7.0, 'max_amount' => 500000]
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 40],
                        'result' => 'Conditional',
                        'set_vars' => ['interest_rate' => 9.0, 'max_amount' => 200000]
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
    echo "\nCredit Score: {$result['credit_score']}/100\n";
    echo "Decision: {$result['loan_decision']}\n";
    if (isset($result['interest_rate'])) {
        echo "Interest Rate: {$result['interest_rate']}%\n";
        echo "Max Loan Amount: ฿{$result['max_amount']}\n";
    }

    echo "\n====PHP===\n";
    echo $ruleFlow->generateFunctionAsString($config,$inputs);
    echo "\n====END===\n\n";
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
                'formula' => '0',
                'inputs' => [],
                'as' => 'score'
            ],
            [
                'id' => 'gender',
                'rules' => [
                    [
                        'var' => 'gender',
                        'if' => ['op' => '==', 'value' => 'female'],
                        'score' => 1
                    ]
                ]
            ],
            [
                'id' => 'age_systolic',
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
                                        'set_vars' => ['requires_medication' => true]
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
                                        'set_vars' => ['requires_medication' => true]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                'id' => 'total_score',
                'formula' => 'gender_score + age_systolic_score',
                'inputs' => ['gender_score', 'age_systolic_score'],
                'as' => 'final_score'
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
    echo "Risk Level: {$result['age_systolic_risk_level']}\n";
    if (isset($result['requires_medication'])) {
        echo "Requires Medication: Yes\n";
    }

    echo "\n====PHP===\n";
    echo $ruleFlow->generateFunctionAsString($config,$inputs);
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
                        'set_vars' => ['base_discount' => 15]
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 50000],
                        'result' => 'gold',
                        'set_vars' => ['base_discount' => 10]
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 10000],
                        'result' => 'silver',
                        'set_vars' => ['base_discount' => 5]
                    ]
                ],
                'default' => 'bronze',
                'default_vars' => ['base_discount' => 0]
            ],
            [
                'id' => 'set_bronze_discount',
                'switch' => 'customer_tier',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'bronze'],
                        'result' => 0,
                        'set_vars' => ['base_discount' => 0]
                    ]
                ],
                'default' => null
            ],
            [
                'id' => 'bonus_discount',
                'rules' => [
                    [
                        'var' => 'is_birthday_month',
                        'if' => ['op' => '==', 'value' => 1],
                        'score' => 5
                    ],
                    [
                        'var' => 'order_amount',
                        'ranges' => [
                            ['if' => ['op' => '>=', 'value' => 5000], 'score' => 3],
                            ['if' => ['op' => '>=', 'value' => 2000], 'score' => 2],
                            ['if' => ['op' => '>=', 'value' => 1000], 'score' => 1]
                        ]
                    ],
                    [
                        'var' => 'is_first_order',
                        'if' => ['op' => '==', 'value' => 1],
                        'score' => 10
                    ]
                ]
            ],
            [
                'id' => 'final_discount',
                'formula' => 'min(base_discount + bonus_discount_score, 25)',
                'inputs' => ['base_discount', 'bonus_discount_score'],
                'as' => 'discount_percent'
            ],
            [
                'id' => 'discount_amount',
                'formula' => 'order_amount * discount_percent / 100',
                'inputs' => ['order_amount', 'discount_percent'],
                'as' => 'discount_amount'
            ],
            [
                'id' => 'final_price',
                'formula' => 'order_amount - discount_amount',
                'inputs' => ['order_amount', 'discount_amount'],
                'as' => 'final_price'
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
    echo $ruleFlow->generateFunctionAsString($config,$inputs);
    echo "\n====END===\n\n";
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
                'formula' => '(midterm * 0.3) + (final * 0.4) + (assignments * 0.2) + (attendance * 0.1)',
                'inputs' => ['midterm', 'final', 'assignments', 'attendance'],
                'as' => 'total_score'
            ],
            [
                'id' => 'letter_grade',
                'switch' => 'total_score',
                'when' => [
                    [
                        'if' => ['op' => '>=', 'value' => 80],
                        'result' => 'A',
                        'set_vars' => ['gpa' => 4.0, 'status' => 'Excellent']
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 75],
                        'result' => 'B+',
                        'set_vars' => ['gpa' => 3.5, 'status' => 'Very Good']
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 70],
                        'result' => 'B',
                        'set_vars' => ['gpa' => 3.0, 'status' => 'Good']
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 65],
                        'result' => 'C+',
                        'set_vars' => ['gpa' => 2.5, 'status' => 'Fair']
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 60],
                        'result' => 'C',
                        'set_vars' => ['gpa' => 2.0, 'status' => 'Satisfactory']
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 55],
                        'result' => 'D+',
                        'set_vars' => ['gpa' => 1.5, 'status' => 'Pass']
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 50],
                        'result' => 'D',
                        'set_vars' => ['gpa' => 1.0, 'status' => 'Pass']
                    ]
                ],
                'default' => 'F',
                'default_vars' => ['gpa' => 0.0, 'status' => 'Fail']
            ],
            [
                'id' => 'bonus_points',
                'scoring' => [
                    'ranges' => [
                        [
                            'if' => ['op' => '==', 'value' => 100],
                            'score' => 2,
                            'set_vars' => ['has_bonus' => true]
                        ],
                        [
                            'if' => ['op' => '>=', 'value' => 95],
                            'score' => 1,
                            'set_vars' => ['has_bonus' => true]
                        ]
                    ],
                    'default' => 0
                ],
                'as' => 'attendance'  // ระบุตัวแปรที่จะเอามาให้คะแนน
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


    echo "\n====PHP===\n";
    echo $ruleFlow->generateFunctionAsString($config,$inputs);
    echo "\n====END===\n\n";
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