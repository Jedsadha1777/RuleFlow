<?php

require_once __DIR__ . '/../src/RuleFlow.php';

echo "RuleFlow Demo: Loan Application Assessment\n";
echo "==========================================\n\n";

$ruleFlow = new RuleFlow();

// Get loan application template
$template = $ruleFlow->getTemplate('loan_application');

// Test Case 1: Excellent applicant
echo "Test Case 1: Excellent Applicant\n";
$inputs1 = [
    'annual_income' => 120000,
    'monthly_debt' => 1500,
    'credit_score' => 780,
    'employment_years' => 8
];

$result1 = $ruleFlow->evaluate($template['config'], $inputs1);

echo "Applicant Profile:\n";
echo "  Annual Income: $" . number_format($inputs1['annual_income']) . "\n";
echo "  Monthly Debt: $" . number_format($inputs1['monthly_debt']) . "\n";
echo "  Credit Score: {$inputs1['credit_score']}\n";
echo "  Employment: {$inputs1['employment_years']} years\n\n";

echo "Assessment Results:\n";
echo "  Monthly Income: $" . number_format($result1['monthly']) . "\n";
echo "  DTI Ratio: " . round($result1['dti_ratio'], 1) . "%\n";
echo "  Credit Score Points: {$result1['credit_score_points']}\n";
echo "  Income Points: {$result1['income_points']}\n";
echo "  Employment Points: {$result1['employment_points']}\n";
echo "  Total Score: {$result1['total_score']}\n";
echo "  Decision: {$result1['loan_decision']}\n";

if ($result1['loan_decision'] !== 'Rejected') {
    echo "  Interest Rate: {$result1['interest_rate']}%\n";
    echo "  Max Amount: $" . number_format($result1['max_amount']) . "\n";
    echo "  Monthly Payment: $" . number_format($result1['monthly_payment']) . "\n";
}
echo "\n";

// Test Case 2: Marginal applicant
echo "Test Case 2: Marginal Applicant\n";
$inputs2 = [
    'annual_income' => 45000,
    'monthly_debt' => 1800,
    'credit_score' => 650,
    'employment_years' => 2
];

$result2 = $ruleFlow->evaluate($template['config'], $inputs2);

echo "Applicant Profile:\n";
echo "  Annual Income: $" . number_format($inputs2['annual_income']) . "\n";
echo "  Monthly Debt: $" . number_format($inputs2['monthly_debt']) . "\n";
echo "  Credit Score: {$inputs2['credit_score']}\n";
echo "  Employment: {$inputs2['employment_years']} years\n\n";

echo "Assessment Results:\n";
echo "  Monthly Income: $" . number_format($result2['monthly']) . "\n";
echo "  DTI Ratio: " . round($result2['dti_ratio'], 1) . "%\n";
echo "  Total Score: {$result2['total_score']}\n";
echo "  Decision: {$result2['loan_decision']}\n";

if ($result2['loan_decision'] !== 'Rejected') {
    echo "  Interest Rate: {$result2['interest_rate']}%\n";
    echo "  Max Amount: $" . number_format($result2['max_amount']) . "\n";
    echo "  Monthly Payment: $" . number_format($result2['monthly_payment']) . "\n";
}
echo "\n";

// Test Case 3: Poor credit applicant
echo "Test Case 3: Poor Credit Applicant\n";
$inputs3 = [
    'annual_income' => 35000,
    'monthly_debt' => 2200,
    'credit_score' => 580,
    'employment_years' => 0.5
];

$result3 = $ruleFlow->evaluate($template['config'], $inputs3);

echo "Applicant Profile:\n";
echo "  Annual Income: $" . number_format($inputs3['annual_income']) . "\n";
echo "  Monthly Debt: $" . number_format($inputs3['monthly_debt']) . "\n";
echo "  Credit Score: {$inputs3['credit_score']}\n";
echo "  Employment: {$inputs3['employment_years']} years\n\n";

echo "Assessment Results:\n";
echo "  Monthly Income: $" . number_format($result3['monthly']) . "\n";
echo "  DTI Ratio: " . round($result3['dti_ratio'], 1) . "%\n";
echo "  Total Score: {$result3['total_score']}\n";
echo "  Decision: {$result3['loan_decision']}\n\n";

// Batch processing
echo "Batch Processing: Multiple Applications\n";
$applications = [
    ['annual_income' => 80000, 'monthly_debt' => 1200, 'credit_score' => 720, 'employment_years' => 5],
    ['annual_income' => 55000, 'monthly_debt' => 1800, 'credit_score' => 680, 'employment_years' => 3],
    ['annual_income' => 95000, 'monthly_debt' => 900, 'credit_score' => 750, 'employment_years' => 7],
];

$batchResults = $ruleFlow->evaluateBatch($template['config'], $applications);

echo "Processed {$batchResults['total_processed']} applications:\n";
echo "  Successful: {$batchResults['successful']}\n";
echo "  Failed: {$batchResults['failed']}\n\n";

foreach ($batchResults['results'] as $index => $result) {
    if ($result['success']) {
        $decision = $result['result']['loan_decision'];
        $score = $result['result']['total_score'];
        echo "  Application " . ($index + 1) . ": $decision (Score: $score)\n";
    }
}

echo "\nDemo completed.\n";