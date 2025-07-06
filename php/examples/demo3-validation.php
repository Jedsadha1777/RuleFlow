<?php

require_once __DIR__ . '/../src/RuleFlow.php';

echo "RuleFlow Demo: Validation Features\n";
echo "==================================\n\n";

$ruleFlow = new RuleFlow();

// Configuration validation
echo "1. Configuration Validation\n";
$validConfig = [
    'formulas' => [
        [
            'id' => 'test_calc',
            'formula' => 'a + b',
            'inputs' => ['a', 'b']
        ]
    ]
];

$validationResult = $ruleFlow->validateConfig($validConfig);
echo "Valid config check:\n";
echo "Valid: " . ($validationResult['valid'] ? 'Yes' : 'No') . "\n";
echo "Errors: " . count($validationResult['errors']) . "\n";
echo "Warnings: " . count($validationResult['warnings']) . "\n\n";

// Invalid configuration
echo "2. Invalid Configuration\n";
$invalidConfig = [
    'formulas' => [
        [
            // Missing 'id' field
            'formula' => 'x + y',
            'inputs' => ['x', 'y']
        ]
    ]
];

$invalidResult = $ruleFlow->validateConfig($invalidConfig);
echo "Invalid config check:\n";
echo "Valid: " . ($invalidResult['valid'] ? 'Yes' : 'No') . "\n";
echo "Errors: " . count($invalidResult['errors']) . "\n";
if (!empty($invalidResult['errors'])) {
    echo "First error: " . $invalidResult['errors'][0] . "\n";
}
echo "\n";

// Field validation
echo "3. Field Validation\n";
$config = [
    'formulas' => [
        [
            'id' => 'bmi',
            'formula' => 'weight / ((height / 100) ** 2)',
            'inputs' => ['weight', 'height']
        ]
    ]
];

// Valid field
$validField = $ruleFlow->validateField('weight', 70, $config);
echo "Field 'weight' = 70:\n";
echo "Valid: " . ($validField['valid'] ? 'Yes' : 'No') . "\n";
echo "Converted: {$validField['converted_value']}\n\n";

// Invalid field
$invalidField = $ruleFlow->validateField('weight', 'not_a_number', $config);
echo "Field 'weight' = 'not_a_number':\n";
echo "Valid: " . ($invalidField['valid'] ? 'Yes' : 'No') . "\n";
echo "Errors: " . count($invalidField['errors']) . "\n";
if (!empty($invalidField['errors'])) {
    echo "Error: " . $invalidField['errors'][0]['message'] . "\n";
}
echo "\n";

// Partial validation
echo "4. Partial Form Validation\n";
$partialInputs = [
    'weight' => 70,
    // height is missing
];

$partialResult = $ruleFlow->validatePartial($partialInputs, $config);
echo "Partial inputs (weight only):\n";
echo "Valid: " . ($partialResult['valid'] ? 'Yes' : 'No') . "\n";
echo "Progress: " . round($partialResult['overall_progress']) . "%\n";
echo "Missing required: " . count($partialResult['missing_required']) . "\n";
if (!empty($partialResult['missing_required'])) {
    echo "Missing: " . $partialResult['missing_required'][0]['field'] . "\n";
}
echo "\n";

// Validation status
echo "5. Complete Validation Status\n";
$completeInputs = [
    'weight' => 70,
    'height' => 175
];

$status = $ruleFlow->getValidationStatus($completeInputs, $config);
echo "Complete inputs:\n";
echo "Ready to submit: " . ($status['ready_to_submit'] ? 'Yes' : 'No') . "\n";
echo "Validation score: " . round($status['validation_score']) . "%\n";
echo "Progress: " . round($status['field_validation']['overall_progress']) . "%\n\n";

// Live preview
echo "6. Live Preview\n";
$preview = $ruleFlow->generateLivePreview($partialInputs, $config);
if ($preview['success']) {
    echo "Preview with estimated values:\n";
    echo "BMI: " . round($preview['preview_results']['bmi'], 2) . "\n";
    echo "Confidence: " . round($preview['confidence']) . "%\n";
    echo "Estimated fields: " . implode(', ', array_keys($preview['estimated_fields'])) . "\n";
} else {
    echo "Preview failed: " . $preview['error'] . "\n";
}

echo "\nDemo completed.\n";