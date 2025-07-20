<?php
require_once __DIR__ . '/../src/RuleFlow.php';

// Demo: Generate optimized PHP code from RuleFlow configuration
echo "<h1>RuleFlow Code Generation Demo</h1>\n";

try {
    $ruleFlow = new RuleFlow();
    
    // Example 1: BMI Calculator
    echo "<h2>Example 1: BMI Calculator</h2>\n";
    
    $bmiConfig = [
        'formulas' => [
            [
                'id' => 'bmi_calculation',
                'formula' => 'weight / ((height / 100) ** 2)',
                'inputs' => ['weight', 'height'],
                'as' => '$bmi'
            ],
            [
                'id' => 'bmi_category',
                'switch' => '$bmi',
                'when' => [
                    ['if' => ['op' => '<', 'value' => 18.5], 'result' => 'Underweight'],
                    ['if' => ['op' => 'between', 'value' => [18.5, 24.9]], 'result' => 'Normal'],
                    ['if' => ['op' => 'between', 'value' => [25, 29.9]], 'result' => 'Overweight'],
                    ['if' => ['op' => '>=', 'value' => 30], 'result' => 'Obese']
                ],
                'default' => 'Unknown'
            ],
            [
                'id' => 'health_score',
                'switch' => 'bmi_category',
                'when' => [
                    ['if' => ['op' => '==', 'value' => 'Normal'], 'result' => 100],
                    ['if' => ['op' => '==', 'value' => 'Overweight'], 'result' => 70],
                    ['if' => ['op' => '==', 'value' => 'Obese'], 'result' => 40],
                    ['if' => ['op' => '==', 'value' => 'Underweight'], 'result' => 60]
                ],
                'default' => 0
            ]
        ]
    ];
    
    // Generate optimized PHP function
    $generatedCode = $ruleFlow->generateFunctionAsString($bmiConfig);
    
    echo "<h3>Generated PHP Function:</h3>\n";
    echo "<pre><code>" . htmlspecialchars($generatedCode) . "</code></pre>\n";
    
    // Test the regular evaluation
    $testInputs = ['weight' => 70, 'height' => 175];
    $result = $ruleFlow->evaluate($bmiConfig, $testInputs);
    
    echo "<h3>Test Result (weight: 70kg, height: 175cm):</h3>\n";
    echo "<ul>\n";
    echo "<li>BMI: " . round($result['bmi'], 2) . "</li>\n";
    echo "<li>Category: " . $result['bmi_category'] . "</li>\n";
    echo "<li>Health Score: " . $result['health_score'] . "</li>\n";
    echo "</ul>\n";
    
    // Example 2: Loan Approval System
    echo "<h2>Example 2: Loan Approval System</h2>\n";
    
    $loanConfig = [
        'formulas' => [
            [
                'id' => 'monthly_income',
                'formula' => 'annual_income / 12',
                'inputs' => ['annual_income'],
                'as' => '$monthly'
            ],
            [
                'id' => 'debt_ratio',
                'formula' => 'percentage(monthly_debt, monthly)',
                'inputs' => ['monthly_debt', 'monthly'],
                'as' => '$debt_percentage'
            ],
            [
                'id' => 'credit_score_points',
                'rules' => [
                    [
                        'var' => 'credit_score',
                        'ranges' => [
                            ['if' => ['op' => '>=', 'value' => 750], 'result' => 100],
                            ['if' => ['op' => '>=', 'value' => 700], 'result' => 80],
                            ['if' => ['op' => '>=', 'value' => 650], 'result' => 60],
                            ['if' => ['op' => '>=', 'value' => 600], 'result' => 40]
                        ]
                    ]
                ]
            ],
            [
                'id' => 'loan_decision',
                'switch' => 'credit_score_points',
                'when' => [
                    [
                        'if' => ['op' => '>=', 'value' => 80],
                        'result' => 'Approved',
                        'set_vars' => [
                            '$interest_rate' => 3.5,
                            '$max_amount' => 1000000
                        ]
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 60],
                        'result' => 'Approved',
                        'set_vars' => [
                            '$interest_rate' => 5.5,
                            '$max_amount' => 500000
                        ]
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 40],
                        'result' => 'Conditional',
                        'set_vars' => [
                            '$interest_rate' => 8.0,
                            '$max_amount' => 200000
                        ]
                    ]
                ],
                'default' => 'Rejected'
            ]
        ]
    ];
    
    // Generate loan approval function
    $loanCode = $ruleFlow->generateFunctionAsString($loanConfig);
    
    echo "<h3>Generated Loan Approval Function:</h3>\n";
    echo "<pre><code>" . htmlspecialchars($loanCode) . "</code></pre>\n";
    
    // Test loan approval
    $loanInputs = [
        'annual_income' => 600000,
        'monthly_debt' => 15000,
        'credit_score' => 720
    ];
    
    $loanResult = $ruleFlow->evaluate($loanConfig, $loanInputs);
    
    echo "<h3>Test Result (Income: 600k, Debt: 15k/month, Score: 720):</h3>\n";
    echo "<ul>\n";
    echo "<li>Monthly Income: " . number_format($loanResult['monthly']) . "</li>\n";
    echo "<li>Debt Ratio: " . round($loanResult['debt_percentage'], 2) . "%</li>\n";
    echo "<li>Credit Points: " . $loanResult['credit_score_points'] . "</li>\n";
    echo "<li>Decision: " . $loanResult['loan_decision'] . "</li>\n";
    if (isset($loanResult['interest_rate'])) {
        echo "<li>Interest Rate: " . $loanResult['interest_rate'] . "%</li>\n";
        echo "<li>Max Amount: " . number_format($loanResult['max_amount']) . "</li>\n";
    }
    echo "</ul>\n";
    
    // Performance Comparison
    echo "<h2>Performance Comparison</h2>\n";
    
    // Create cached evaluator for performance
    $cachedEvaluator = $ruleFlow->createCachedEvaluator($bmiConfig);
    
    // Test regular evaluation
    $iterations = 1000;
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $ruleFlow->evaluate($bmiConfig, $testInputs);
    }
    $regularTime = microtime(true) - $start;
    
    // Test cached evaluation
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $cachedEvaluator($testInputs);
    }
    $cachedTime = microtime(true) - $start;
    
    echo "<h3>Performance Results ({$iterations} iterations):</h3>\n";
    echo "<ul>\n";
    echo "<li>Regular Evaluation: " . round($regularTime * 1000, 2) . "ms</li>\n";
    echo "<li>Cached Evaluation: " . round($cachedTime * 1000, 2) . "ms</li>\n";
    echo "<li>Speed Improvement: " . round($regularTime / $cachedTime, 2) . "x faster</li>\n";
    echo "</ul>\n";
    
    // Memory Usage
    $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;
    echo "<h3>Memory Usage:</h3>\n";
    echo "<p>Peak Memory: " . round($memoryUsage, 2) . " MB</p>\n";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>