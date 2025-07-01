<?php 

require_once "../src/RuleEngine.php";


$ruleEngine = new RuleFlow();

$loanConfig = [
    "inputs" => [
        "monthly_debt" => ["label" => "Monthly Debt", "unit" => "USD"],
        "monthly_income" => ["label" => "Monthly Income", "unit" => "USD"],
        "credit_score" => ["label" => "Credit Score", "unit" => "points"]
    ],
    "formulas" => [
        [
            "id" => "debt_to_income",
            "expression" => "round((monthly_debt / monthly_income) * 100, 2)",
            "inputs" => ["monthly_debt", "monthly_income"],
            "store_as" => "dti_ratio"
        ],
        [
            "id" => "credit_score_category",
            "switch_on" => "credit_score",
            "cases" => [
                ["condition" => ["operator" => ">=", "value" => 750], "result" => "Excellent"],
                ["condition" => ["operator" => ">=", "value" => 700], "result" => "Good"],
                ["condition" => ["operator" => ">=", "value" => 650], "result" => "Fair"],
                ["condition" => ["operator" => ">=", "value" => 600], "result" => "Poor"]
            ],
            "default" => "Very Poor",
            "weight_score" => [
                "condition" => ["operator" => ">=", "value" => 700],
                "score" => 40
            ]
        ],
        [
            "id" => "dti_assessment", 
            "switch_on" => "dti_ratio",
            "cases" => [
                ["condition" => ["operator" => "<=", "value" => 20], "result" => "Excellent"],
                ["condition" => ["operator" => "<=", "value" => 36], "result" => "Good"],
                ["condition" => ["operator" => "<=", "value" => 50], "result" => "Acceptable"]
            ],
            "default" => "Too High",
            "weight_score" => [
                "condition" => ["operator" => "<=", "value" => 36],
                "score" => 40
            ]
        ],
        [
            "id" => "combined_score",
            "expression" => "credit_score_category_score + dti_assessment_score",
            "inputs" => ["credit_score_category_score", "dti_assessment_score"]
        ],
        [
            "id" => "loan_approval",
            "switch_on" => "combined_score",
            "cases" => [
                ["condition" => ["operator" => ">=", "value" => 80], "result" => "Approved"],
                ["condition" => ["operator" => ">=", "value" => 60], "result" => "Manual Review"],
                ["condition" => ["operator" => ">=", "value" => 20], "result" => "Conditional"]
            ],
            "default" => "Declined"
        ]
    ]
];

// ข้อมูล input
$loanInputs = [
    "monthly_debt" => 1500,      // หนี้รายเดือน $1,500
    "monthly_income" => 5000,    // รายได้รายเดือน $5,000  
    "credit_score" => 720        // คะแนนเครดิต 720
];

try {
    echo "=== Loan Application Analysis ===\n";
    
    // ทดสอบ config
    $testResult = $ruleEngine->testConfig($loanConfig, $loanInputs);
    
    if ($testResult['valid']) {
        echo "✅ Configuration is valid!\n\n";
        
        // แสดงผลการวิเคราะห์
        echo "📊 Loan Analysis Results:\n";
        $results = $testResult['test_results'];
        
        echo "Input Data:\n";
        echo "  Monthly Income: $" . number_format($loanInputs['monthly_income']) . "\n";
        echo "  Monthly Debt: $" . number_format($loanInputs['monthly_debt']) . "\n";
        echo "  Credit Score: " . $loanInputs['credit_score'] . "\n\n";
        
        echo "Analysis:\n";
        echo "  Debt-to-Income Ratio: " . $results['dti_ratio'] . "%\n";
        echo "  DTI Assessment: " . $results['dti_assessment'] . "\n";
        echo "  Credit Score Category: " . $results['credit_score_category'] . "\n";
        echo "  Combined Score: " . $results['combined_score'] . "/80\n";
        echo "  Final Decision: " . $results['loan_approval'] . "\n\n";
        
        echo "Scoring Breakdown:\n";
        echo "  Credit Score Points: " . $results['credit_score_category_score'] . "/40\n";
        echo "  DTI Points: " . $results['dti_assessment_score'] . "/40\n";
        
    } else {
        echo "❌ Configuration errors:\n";
        foreach ($testResult['errors'] as $error) {
            echo "  - $error\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// ทดสอบกับข้อมูลอื่น
echo "\n" . str_repeat("=", 50) . "\n";
echo "=== Testing Different Scenarios ===\n";

$scenarios = [
    [
        "name" => "High Risk Applicant",
        "data" => ["monthly_debt" => 2500, "monthly_income" => 4000, "credit_score" => 580]
    ],
    [
        "name" => "Excellent Applicant", 
        "data" => ["monthly_debt" => 800, "monthly_income" => 6000, "credit_score" => 780]
    ],
    [
        "name" => "Borderline Case",
        "data" => ["monthly_debt" => 1800, "monthly_income" => 5000, "credit_score" => 680]
    ]
];

foreach ($scenarios as $scenario) {
    echo "\n{$scenario['name']}:\n";
    try {
        $result = $ruleEngine->evaluate($loanConfig, $scenario['data']);
        echo "  DTI: {$result['dti_ratio']}% | Credit: {$result['credit_score_category']} | Decision: {$result['loan_approval']}\n";
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}
?>