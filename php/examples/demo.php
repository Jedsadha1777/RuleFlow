<?php 

require_once 'RuleFlow.php';

// Usage Example
try {
    $ruleEngine = new RuleFlow();

    $config = [
        "inputs" => [
            "weight" => ["label" => "น้ำหนัก", "unit" => "kg"],
            "height" => ["label" => "ส่วนสูง", "unit" => "cm"],
            "age" => ["label" => "อายุ", "unit" => "ปี"]
        ],
        "formulas" => [
            [
                "id" => "bmi",
                "label" => "ค่าดัชนีมวลกาย (BMI)",
                "expression" => "round((weight / ((height / 100) ** 2)), 2)",
                "inputs" => ["weight", "height"],
                "store_as" => "bmi_value",
                "weight_score" => [
                    "condition" => ["operator" => "between", "value" => [18.5, 24.9]],
                    "score" => 10
                ]
            ],
            [
                "id" => "bmi_risk",
                "label" => "ความเสี่ยงจาก BMI",
                "switch_on" => "bmi_value",
                "cases" => [
                    ["condition" => ["operator" => "<", "value" => 18.5], "result" => "ต่ำกว่าเกณฑ์"],
                    ["condition" => ["operator" => "between", "value" => [18.5, 24.9]], "result" => "ปกติ"],
                    ["condition" => ["operator" => "between", "value" => [25, 29.9]], "result" => "น้ำหนักเกิน"],
                    ["condition" => ["operator" => ">=", "value" => 30], "result" => "อ้วน"]
                ],
                "default" => "ไม่สามารถประเมินได้"
            ],
            [
                "id" => "age_risk",
                "label" => "ความเสี่ยงตามอายุ",
                "switch_on" => "age",
                "cases" => [
                    ["condition" => ["operator" => "<", "value" => 30], "result" => "เสี่ยงน้อย"],
                    ["condition" => ["operator" => "between", "value" => [30, 50]], "result" => "เสี่ยงปานกลาง"],
                    ["condition" => ["operator" => ">=", "value" => 50], "result" => "เสี่ยงสูง"]
                ]
            ]
        ]
    ];

    $inputs = [
        "weight" => 70,
        "height" => 175,
        "age" => 35
    ];

    echo "=== Testing Configuration ===\n";
    $testResult = $ruleEngine->testConfig($config, $inputs);
    
    if ($testResult['valid']) {
        echo "✅ Configuration is valid!\n";
        
        if (!empty($testResult['warnings'])) {
            echo "\n⚠️  Warnings:\n";
            foreach ($testResult['warnings'] as $warning) {
                echo "  - $warning\n";
            }
        }
        
        if (!empty($testResult['test_results'])) {
            echo "\n📊 Test Results:\n";
            foreach ($testResult['test_results'] as $key => $value) {
                echo "  $key: $value\n";
            }
        }
    } else {
        echo "❌ Configuration errors:\n";
        foreach ($testResult['errors'] as $error) {
            echo "  - $error\n";
        }
    }

    echo "\n=== Running Evaluation ===\n";
    $result = $ruleEngine->evaluate($config, $inputs);
    
    echo "ผลลัพธ์:\n";
    foreach ($result as $key => $value) {
        echo "$key: $value\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test with invalid configuration
echo "\n=== Testing Invalid Configuration ===\n";
$invalidConfig = [
    "formulas" => [
        [
            // Missing 'id'
            "expression" => "weight + height"
            // Missing 'inputs'
        ],
        [
            "id" => "test",
            "switch_on" => "value",
            "cases" => [
                [
                    "condition" => ["operator" => "invalid_op", "value" => 10],
                    "result" => "test"
                ]
            ]
        ]
    ]
];

$invalidTest = $ruleEngine->testConfig($invalidConfig);
if (!$invalidTest['valid']) {
    echo "❌ Found expected errors:\n";
    foreach ($invalidTest['errors'] as $error) {
        echo "  - $error\n";
    }
}

?>