<?php

require_once __DIR__ . '/../src/RuleFlow.php';

echo "RuleFlow Demo: BMI Health Assessment\n";
echo "====================================\n\n";

$ruleFlow = new RuleFlow();

// Get BMI template
$template = $ruleFlow->getTemplate('bmi_health_assessment');

// Test Case 1: Normal weight
echo "Test Case 1: Normal Weight Adult\n";
$inputs1 = [
    'weight' => 70,    // kg
    'height' => 1.75   // meters
];

$result1 = $ruleFlow->evaluate($template['config'], $inputs1);

echo "Patient Profile:\n";
echo "  Weight: {$inputs1['weight']} kg\n";
echo "  Height: {$inputs1['height']} m\n\n";

echo "Assessment Results:\n";
echo "  BMI: " . round($result1['bmi'], 2) . "\n";
echo "  Category: {$result1['bmi_category']}\n";
echo "  Risk Score: {$result1['health_risk_score']}\n";
echo "  Recommendation: {$result1['health_recommendations']}\n\n";

// Test Case 2: Overweight
echo "Test Case 2: Overweight Patient\n";
$inputs2 = [
    'weight' => 85,
    'height' => 1.70
];

$result2 = $ruleFlow->evaluate($template['config'], $inputs2);

echo "Patient Profile:\n";
echo "  Weight: {$inputs2['weight']} kg\n";
echo "  Height: {$inputs2['height']} m\n\n";

echo "Assessment Results:\n";
echo "  BMI: " . round($result2['bmi'], 2) . "\n";
echo "  Category: {$result2['bmi_category']}\n";
echo "  Risk Score: {$result2['health_risk_score']}\n";
echo "  Recommendation: {$result2['health_recommendations']}\n\n";

// Test Case 3: Underweight
echo "Test Case 3: Underweight Patient\n";
$inputs3 = [
    'weight' => 45,
    'height' => 1.65
];

$result3 = $ruleFlow->evaluate($template['config'], $inputs3);

echo "Patient Profile:\n";
echo "  Weight: {$inputs3['weight']} kg\n";
echo "  Height: {$inputs3['height']} m\n\n";

echo "Assessment Results:\n";
echo "  BMI: " . round($result3['bmi'], 2) . "\n";
echo "  Category: {$result3['bmi_category']}\n";
echo "  Risk Score: {$result3['health_risk_score']}\n";
echo "  Recommendation: {$result3['health_recommendations']}\n\n";

// Test Case 4: Obese
echo "Test Case 4: Obese Patient\n";
$inputs4 = [
    'weight' => 95,
    'height' => 1.68
];

$result4 = $ruleFlow->evaluate($template['config'], $inputs4);

echo "Patient Profile:\n";
echo "  Weight: {$inputs4['weight']} kg\n";
echo "  Height: {$inputs4['height']} m\n\n";

echo "Assessment Results:\n";
echo "  BMI: " . round($result4['bmi'], 2) . "\n";
echo "  Category: {$result4['bmi_category']}\n";
echo "  Risk Score: {$result4['health_risk_score']}\n";
echo "  Recommendation: {$result4['health_recommendations']}\n\n";

// BMI ranges reference
echo "BMI Categories Reference:\n";
echo "  Underweight: < 18.5\n";
echo "  Normal: 18.5 - 24.9\n";
echo "  Overweight: 25.0 - 29.9\n";
echo "  Obese: >= 30.0\n\n";

// Form generation
echo "Generated HTML Form:\n";
$htmlForm = $ruleFlow->generateInputSchema($template['config'], 'html');
echo $htmlForm . "\n\n";

// Schema generation
echo "JSON Schema:\n";
$jsonSchema = $ruleFlow->generateInputSchema($template['config'], 'json');
echo "Required fields: " . implode(', ', $jsonSchema['required']) . "\n";
echo "Properties: " . implode(', ', array_keys($jsonSchema['properties'])) . "\n";

echo "\nDemo completed.\n";