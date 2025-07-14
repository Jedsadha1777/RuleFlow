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

try {
    $validationResult = $ruleFlow->validateConfig($validConfig);
    echo "Valid config check:\n";
    echo "Valid: " . ($validationResult['valid'] ? 'Yes' : 'No') . "\n";
    echo "Errors: " . count($validationResult['errors']) . "\n";
    echo "Warnings: " . count($validationResult['warnings']) . "\n\n";
} catch (Exception $e) {
    echo "Config validation failed: " . $e->getMessage() . "\n\n";
}

// Field validation with error handling
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

try {
    $validField = $ruleFlow->validateField('weight', 70, $config);
    echo "Field 'weight' = 70:\n";
    echo "Valid: " . ($validField['valid'] ? 'Yes' : 'No') . "\n";
    echo "Converted: {$validField['converted_value']}\n\n";
} catch (Exception $e) {
    echo "Field validation failed: " . $e->getMessage() . "\n\n";
}

// Partial validation with null checks
echo "4. Partial Form Validation\n";
$partialInputs = [
    'weight' => 70,
    // height is missing
];

try {
    $partialResult = $ruleFlow->validatePartial($partialInputs, $config);
    echo "Partial inputs (weight only):\n";
    echo "Valid: " . ($partialResult['valid'] ? 'Yes' : 'No') . "\n";
    echo "Progress: " . round($partialResult['overall_progress'] ?? 0) . "%\n";
    echo "Missing required: " . count($partialResult['missing_required'] ?? []) . "\n";
    if (!empty($partialResult['missing_required'])) {
        echo "Missing: " . ($partialResult['missing_required'][0]['field'] ?? 'unknown') . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "Partial validation failed: " . $e->getMessage() . "\n\n";
}

// Complete validation with null checks
echo "5. Complete Validation Status\n";
$completeInputs = [
    'weight' => 70,
    'height' => 175
];

try {
    $status = $ruleFlow->getValidationStatus($completeInputs, $config);
    echo "Complete inputs:\n";
    echo "Ready to submit: " . ($status['ready_to_submit'] ? 'Yes' : 'No') . "\n";
    echo "Validation score: " . round($status['validation_score'] ?? 0) . "%\n";
    echo "Progress: " . round($status['field_validation']['overall_progress'] ?? 0) . "%\n\n";
} catch (Exception $e) {
    echo "Status validation failed: " . $e->getMessage() . "\n\n";
}

// Live preview with better error handling
echo "6. Live Preview\n";
try {
    $preview = $ruleFlow->generateLivePreview($partialInputs, $config);
    if ($preview['success']) {
        echo "Preview with estimated values:\n";
        echo "BMI: " . round($preview['preview_results']['bmi'] ?? 0, 2) . "\n";
        echo "Confidence: " . round($preview['confidence'] ?? 0) . "%\n";
        echo "Estimated fields: " . implode(', ', array_keys($preview['estimated_fields'] ?? [])) . "\n";
    } else {
        echo "Preview failed: " . ($preview['error'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "Preview failed: " . $e->getMessage() . "\n";
}

echo "\nDemo completed.\n";