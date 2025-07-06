<?php

require_once __DIR__ . '/../src/RuleFlow.php';

echo "RuleFlow Demo: Employee Performance Review\n";
echo "==========================================\n\n";

$ruleFlow = new RuleFlow();

// Get performance review template
$template = $ruleFlow->getTemplate('performance_review');

// Test Case 1: Exceptional performer
echo "Test Case 1: Exceptional Performer\n";
$inputs1 = [
    'quality_score' => 95,
    'productivity_score' => 88,
    'teamwork_score' => 92,
    'communication_score' => 90,
    'goals_achieved' => 95,
    'tenure_bonus' => 10
];

$result1 = $ruleFlow->evaluate($template['config'], $inputs1);

echo "Employee Profile:\n";
echo "  Quality Score: {$inputs1['quality_score']}\n";
echo "  Productivity Score: {$inputs1['productivity_score']}\n";
echo "  Teamwork Score: {$inputs1['teamwork_score']}\n";
echo "  Communication Score: {$inputs1['communication_score']}\n";
echo "  Goals Achieved: {$inputs1['goals_achieved']}%\n";
echo "  Tenure Bonus: {$inputs1['tenure_bonus']}\n\n";

echo "Review Results:\n";
echo "  Performance Score: " . round($result1['performance_score'], 1) . "\n";
echo "  Goal Achievement Bonus: {$result1['goal_achievement_bonus']}\n";
echo "  Total Score: " . round($result1['total_score'], 1) . "\n";
echo "  Rating: {$result1['rating']}\n";
echo "  Salary Increase: {$result1['salary_increase_percent']}%\n\n";

// Test Case 2: Average performer
echo "Test Case 2: Average Performer\n";
$inputs2 = [
    'quality_score' => 75,
    'productivity_score' => 70,
    'teamwork_score' => 78,
    'communication_score' => 72,
    'goals_achieved' => 65,
    'tenure_bonus' => 5
];

$result2 = $ruleFlow->evaluate($template['config'], $inputs2);

echo "Employee Profile:\n";
echo "  Quality Score: {$inputs2['quality_score']}\n";
echo "  Productivity Score: {$inputs2['productivity_score']}\n";
echo "  Teamwork Score: {$inputs2['teamwork_score']}\n";
echo "  Communication Score: {$inputs2['communication_score']}\n";
echo "  Goals Achieved: {$inputs2['goals_achieved']}%\n";
echo "  Tenure Bonus: {$inputs2['tenure_bonus']}\n\n";

echo "Review Results:\n";
echo "  Performance Score: " . round($result2['performance_score'], 1) . "\n";
echo "  Goal Achievement Bonus: {$result2['goal_achievement_bonus']}\n";
echo "  Total Score: " . round($result2['total_score'], 1) . "\n";
echo "  Rating: {$result2['rating']}\n";
echo "  Salary Increase: {$result2['salary_increase_percent']}%\n\n";

// Test Case 3: Below expectations
echo "Test Case 3: Below Expectations\n";
$inputs3 = [
    'quality_score' => 60,
    'productivity_score' => 55,
    'teamwork_score' => 65,
    'communication_score' => 58,
    'goals_achieved' => 45,
    'tenure_bonus' => 2
];

$result3 = $ruleFlow->evaluate($template['config'], $inputs3);

echo "Employee Profile:\n";
echo "  Quality Score: {$inputs3['quality_score']}\n";
echo "  Productivity Score: {$inputs3['productivity_score']}\n";
echo "  Teamwork Score: {$inputs3['teamwork_score']}\n";
echo "  Communication Score: {$inputs3['communication_score']}\n";
echo "  Goals Achieved: {$inputs3['goals_achieved']}%\n";
echo "  Tenure Bonus: {$inputs3['tenure_bonus']}\n\n";

echo "Review Results:\n";
echo "  Performance Score: " . round($result3['performance_score'], 1) . "\n";
echo "  Goal Achievement Bonus: {$result3['goal_achievement_bonus']}\n";
echo "  Total Score: " . round($result3['total_score'], 1) . "\n";
echo "  Rating: {$result3['rating']}\n";
echo "  Salary Increase: {$result3['salary_increase_percent']}%\n\n";

// Candidate scoring template
echo "Candidate Evaluation System\n";
$candidateTemplate = $ruleFlow->getTemplate('candidate_scoring');

$candidateInputs = [
    'years_experience' => 7,
    'education_level' => 'Masters',
    'technical_skills' => 85,
    'soft_skills' => 78,
    'domain_knowledge' => 82
];

$candidateResult = $ruleFlow->evaluate($candidateTemplate['config'], $candidateInputs);

echo "Candidate Profile:\n";
echo "  Experience: {$candidateInputs['years_experience']} years\n";
echo "  Education: {$candidateInputs['education_level']}\n";
echo "  Technical Skills: {$candidateInputs['technical_skills']}\n";
echo "  Soft Skills: {$candidateInputs['soft_skills']}\n";
echo "  Domain Knowledge: {$candidateInputs['domain_knowledge']}\n\n";

echo "Evaluation Results:\n";
echo "  Experience Score: {$candidateResult['experience_score']}\n";
echo "  Education Score: {$candidateResult['education_score']}\n";
echo "  Skills Score: " . round($candidateResult['skills_score'], 1) . "\n";
echo "  Total Score: " . round($candidateResult['total_candidate_score'], 1) . "\n";
echo "  Recommendation: {$candidateResult['recommendation']}\n\n";

// Performance rating scale
echo "Performance Rating Scale:\n";
echo "  90+: Exceptional\n";
echo "  80-89: Exceeds Expectations\n";
echo "  70-79: Meets Expectations\n";
echo "  60-69: Below Expectations\n";
echo "  <60: Unsatisfactory\n";

echo "\nDemo completed.\n";