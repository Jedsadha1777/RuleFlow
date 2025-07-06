<?php

require_once __DIR__ . '/../src/RuleFlow.php';

echo "RuleFlow Demo: Template Usage\n";
echo "=============================\n\n";

$ruleFlow = new RuleFlow();

// List available templates
echo "1. Available Templates\n";
$templates = $ruleFlow->getTemplates();
echo "Found " . count($templates['available_templates']) . " templates:\n";
foreach ($templates['available_templates'] as $template) {
    echo "  - $template\n";
}
echo "\n";

// BMI Calculator Template
echo "2. BMI Health Assessment Template\n";
$bmiTemplate = $ruleFlow->getTemplate('bmi_health_assessment');
$bmiInputs = [
    'weight' => 70,  // kg
    'height' => 1.75 // meters
];

$bmiResult = $ruleFlow->evaluate($bmiTemplate['config'], $bmiInputs);
echo "Input: weight=70kg, height=1.75m\n";
echo "BMI: " . round($bmiResult['bmi'], 2) . "\n";
echo "Category: {$bmiResult['bmi_category']}\n";
echo "Risk Score: {$bmiResult['health_risk_score']}\n";
echo "Recommendation: {$bmiResult['health_recommendations']}\n\n";

// Loan Application Template
echo "3. Loan Application Template\n";
$loanTemplate = $ruleFlow->getTemplate('loan_application');
$loanInputs = [
    'annual_income' => 80000,
    'monthly_debt' => 2000,
    'credit_score' => 720,
    'employment_years' => 5
];

$loanResult = $ruleFlow->evaluate($loanTemplate['config'], $loanInputs);
echo "Input: income=80000, debt=2000, credit=720, employment=5 years\n";
echo "Monthly Income: " . round($loanResult['monthly'], 2) . "\n";
echo "DTI Ratio: " . round($loanResult['dti_ratio'], 1) . "%\n";
echo "Total Score: {$loanResult['total_score']}\n";
echo "Decision: {$loanResult['loan_decision']}\n";
if (isset($loanResult['interest_rate'])) {
    echo "Interest Rate: {$loanResult['interest_rate']}%\n";
    echo "Max Amount: $" . number_format($loanResult['max_amount']) . "\n";
}
echo "\n";

// Employee Performance Template
echo "4. Employee Performance Review Template\n";
$performanceTemplate = $ruleFlow->getTemplate('performance_review');
$performanceInputs = [
    'quality_score' => 85,
    'productivity_score' => 78,
    'teamwork_score' => 92,
    'communication_score' => 88,
    'goals_achieved' => 85,
    'tenure_bonus' => 5
];

$performanceResult = $ruleFlow->evaluate($performanceTemplate['config'], $performanceInputs);
echo "Input: quality=85, productivity=78, teamwork=92, communication=88\n";
echo "Performance Score: " . round($performanceResult['performance_score'], 1) . "\n";
echo "Goal Bonus: {$performanceResult['goal_achievement_bonus']}\n";
echo "Total Score: " . round($performanceResult['total_score'], 1) . "\n";
echo "Rating: {$performanceResult['rating']}\n";
echo "Salary Increase: {$performanceResult['salary_increase_percent']}%\n\n";

// Template by category
echo "5. Templates by Category\n";
$financialTemplates = $ruleFlow->getTemplates('financial');
echo "Financial templates: " . count($financialTemplates) . "\n";
foreach ($financialTemplates as $name => $template) {
    echo "  - $name: {$template['metadata']['name']}\n";
}

echo "\nDemo completed.\n";