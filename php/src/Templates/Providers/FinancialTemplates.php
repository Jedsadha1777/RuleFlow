<?php

require_once __DIR__ . '/../TemplateProviderInterface.php';

class FinancialTemplates implements TemplateProviderInterface
{
    public function getCategory(): string
    {
        return 'financial';
    }
    
    public function getTemplateNames(): array
    {
        return ['loan_application', 'credit_card_approval'];
    }
    
    public function getTemplates(): array
    {
        return [
            'loan_application' => [
                'config' => [
                    'formulas' => [
                        [
                            'id' => 'monthly_income',
                            'formula' => 'annual_income / 12',
                            'inputs' => ['annual_income'],
                            'as' => '$monthly'
                        ],
                        [
                            'id' => 'debt_to_income_ratio',
                            'formula' => 'percentage(monthly_debt, monthly)',
                            'inputs' => ['monthly_debt', 'monthly'],
                            'as' => '$dti_ratio'
                        ],
                        [
                            'id' => 'credit_score_points',
                            'rules' => [
                                [
                                    'var' => 'credit_score',
                                    'ranges' => [
                                        ['if' => ['op' => '>=', 'value' => 750], 'score' => 100],
                                        ['if' => ['op' => '>=', 'value' => 700], 'score' => 80],
                                        ['if' => ['op' => '>=', 'value' => 650], 'score' => 60],
                                        ['if' => ['op' => '>=', 'value' => 600], 'score' => 40],
                                        ['if' => ['op' => '>=', 'value' => 550], 'score' => 20]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 'income_points',
                            'rules' => [
                                [
                                    'var' => 'monthly',
                                    'ranges' => [
                                        ['if' => ['op' => '>=', 'value' => 10000], 'score' => 50],
                                        ['if' => ['op' => '>=', 'value' => 7500], 'score' => 40],
                                        ['if' => ['op' => '>=', 'value' => 5000], 'score' => 30],
                                        ['if' => ['op' => '>=', 'value' => 3000], 'score' => 20],
                                        ['if' => ['op' => '>=', 'value' => 2000], 'score' => 10]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 'employment_points',
                            'rules' => [
                                [
                                    'var' => 'employment_years',
                                    'ranges' => [
                                        ['if' => ['op' => '>=', 'value' => 5], 'score' => 30],
                                        ['if' => ['op' => '>=', 'value' => 3], 'score' => 20],
                                        ['if' => ['op' => '>=', 'value' => 1], 'score' => 10],
                                        ['if' => ['op' => '>=', 'value' => 0.5], 'score' => 5]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 'total_score',
                            'formula' => 'credit_score_points + income_points + employment_points',
                            'inputs' => ['credit_score_points', 'income_points', 'employment_points']
                        ],
                        [
                            'id' => 'loan_decision',
                            'switch' => 'total_score',
                            'when' => [
                                [
                                    'if' => ['op' => '>=', 'value' => 150],
                                    'result' => 'Approved',
                                    'set_vars' => [
                                        '$interest_rate' => 3.5,
                                        '$max_amount' => 1000000,
                                        '$term_years' => 30
                                    ]
                                ],
                                [
                                    'if' => ['op' => '>=', 'value' => 120],
                                    'result' => 'Approved',
                                    'set_vars' => [
                                        '$interest_rate' => 4.5,
                                        '$max_amount' => 500000,
                                        '$term_years' => 25
                                    ]
                                ],
                                [
                                    'if' => ['op' => '>=', 'value' => 80],
                                    'result' => 'Conditional',
                                    'set_vars' => [
                                        '$interest_rate' => 6.5,
                                        '$max_amount' => 200000,
                                        '$term_years' => 15
                                    ]
                                ]
                            ],
                            'default' => 'Rejected',
                            'default_vars' => [
                                'interest_rate' => 0,
                                'max_amount' => 0,
                                'term_years' => 0
                            ]
                        ],
                        [
                            'id' => 'has_loan_approval',
                            'switch' => 'loan_decision',
                            'when' => [
                                ['if' => ['op' => '==', 'value' => 'Approved'], 'result' => 1],
                                ['if' => ['op' => '==', 'value' => 'Conditional'], 'result' => 1]
                            ],
                            'default' => 0
                        ],
                        [
                            'id' => 'monthly_rate',
                            'switch' => 'has_loan_approval',
                            'when' => [
                                ['if' => ['op' => '==', 'value' => 1], 'result' => 1]
                            ],
                            'default' => 0
                        ],
                        [
                            'id' => 'calc_monthly_rate',
                            'formula' => 'interest_rate / 100 / 12 * monthly_rate',
                            'inputs' => ['interest_rate', 'monthly_rate']
                        ],
                        [
                            'id' => 'num_payments',
                            'switch' => 'has_loan_approval',
                            'when' => [
                                ['if' => ['op' => '==', 'value' => 1], 'result' => 1]
                            ],
                            'default' => 0
                        ],
                        [
                            'id' => 'calc_num_payments',
                            'formula' => 'term_years * 12 * num_payments',
                            'inputs' => ['term_years', 'num_payments']
                        ],
                        [
                            'id' => 'payment_factor',
                            'formula' => 'pow(1 + calc_monthly_rate, calc_num_payments)',
                            'inputs' => ['calc_monthly_rate', 'calc_num_payments']
                        ],
                        [
                            'id' => 'monthly_payment',
                            'switch' => 'has_loan_approval',
                            'when' => [
                                ['if' => ['op' => '==', 'value' => 1], 'result' => 'calculated']
                            ],
                            'default' => 0,
                            'formula' => 'max_amount * calc_monthly_rate * payment_factor / max(payment_factor - 1, 0.001)',
                            'inputs' => ['max_amount', 'calc_monthly_rate', 'payment_factor']
                        ]
                    ]
                ],
                'metadata' => [
                    'name' => 'Loan Application Assessment',
                    'category' => 'financial',
                    'description' => 'Comprehensive loan approval system with credit scoring',
                    'author' => 'RuleFlow',
                    'version' => '2.0.0',
                    'inputs' => [
                        'annual_income' => 'Annual gross income (number)',
                        'monthly_debt' => 'Existing monthly debt payments (number)',
                        'credit_score' => 'Credit score 300-850 (number)',
                        'employment_years' => 'Years at current job (number)'
                    ],
                    'outputs' => [
                        'loan_decision' => 'Approved/Conditional/Rejected',
                        'interest_rate' => 'Approved interest rate (%)',
                        'max_amount' => 'Maximum loan amount',
                        'monthly_payment' => 'Estimated monthly payment'
                    ]
                ]
            ],
            
            'credit_card_approval' => [
                'config' => [
                    'formulas' => [
                        [
                            'id' => 'credit_utilization_score',
                            'switch' => 'credit_utilization',
                            'when' => [
                                ['if' => ['op' => '<=', 'value' => 10], 'result' => 50],
                                ['if' => ['op' => '<=', 'value' => 30], 'result' => 30],
                                ['if' => ['op' => '<=', 'value' => 50], 'result' => 10],
                                ['if' => ['op' => '<=', 'value' => 75], 'result' => -10]
                            ],
                            'default' => -20
                        ],
                        [
                            'id' => 'payment_history_score',
                            'switch' => 'late_payments_12m',
                            'when' => [
                                ['if' => ['op' => '==', 'value' => 0], 'result' => 50],
                                ['if' => ['op' => '<=', 'value' => 1], 'result' => 30],
                                ['if' => ['op' => '<=', 'value' => 3], 'result' => 10]
                            ],
                            'default' => -30
                        ],
                        [
                            'id' => 'final_score',
                            'formula' => 'credit_score + credit_utilization_score + payment_history_score + income_score',
                            'inputs' => ['credit_score', 'credit_utilization_score', 'payment_history_score', 'income_score']
                        ],
                        [
                            'id' => 'approval_decision',
                            'switch' => 'final_score',
                            'when' => [
                                [
                                    'if' => ['op' => '>=', 'value' => 750],
                                    'result' => 'Approved',
                                    'set_vars' => ['$credit_limit' => 50000, '$apr' => 12.99]
                                ],
                                [
                                    'if' => ['op' => '>=', 'value' => 650],
                                    'result' => 'Approved',
                                    'set_vars' => ['$credit_limit' => 25000, '$apr' => 18.99]
                                ],
                                [
                                    'if' => ['op' => '>=', 'value' => 600],
                                    'result' => 'Approved',
                                    'set_vars' => ['$credit_limit' => 10000, '$apr' => 24.99]
                                ]
                            ],
                            'default' => 'Rejected'
                        ]
                    ]
                ],
                'metadata' => [
                    'name' => 'Credit Card Approval System',
                    'category' => 'financial',
                    'description' => 'Credit card application assessment with limit determination',
                    'author' => 'RuleFlow',
                    'version' => '1.0.0',
                    'inputs' => [
                        'credit_score' => 'FICO credit score',
                        'credit_utilization' => 'Current credit utilization %',
                        'late_payments_12m' => 'Late payments in last 12 months',
                        'income_score' => 'Income-based score'
                    ],
                    'outputs' => [
                        'approval_decision' => 'Approved/Rejected',
                        'credit_limit' => 'Approved credit limit',
                        'apr' => 'Annual percentage rate'
                    ]
                ]
            ]
        ];
    }
}