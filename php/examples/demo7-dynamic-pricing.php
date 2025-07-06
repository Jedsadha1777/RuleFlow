<?php

require_once __DIR__ . '/../src/RuleFlow.php';

echo "RuleFlow Demo: Dynamic Pricing Engine\n";
echo "=====================================\n\n";

$ruleFlow = new RuleFlow();

// Get dynamic pricing template
$template = $ruleFlow->getTemplate('dynamic_pricing');

// Test Case 1: High demand, low inventory
echo "Test Case 1: High Demand, Low Inventory\n";
$inputs1 = [
    'base_price' => 100,
    'demand_level' => 'High',
    'inventory_level' => 8,
    'price_vs_competitor' => 1.05,
    'customer_tier' => 'VIP'
];

$result1 = $ruleFlow->evaluate($template['config'], $inputs1);

echo "Market Conditions:\n";
echo "  Base Price: $" . number_format($inputs1['base_price']) . "\n";
echo "  Demand Level: {$inputs1['demand_level']}\n";
echo "  Inventory: {$inputs1['inventory_level']} units\n";
echo "  vs Competitor: " . ($inputs1['price_vs_competitor'] * 100) . "%\n";
echo "  Customer Tier: {$inputs1['customer_tier']}\n\n";

echo "Pricing Results:\n";
echo "  Demand Multiplier: {$result1['demand_multiplier']}\n";
echo "  Inventory Multiplier: {$result1['inventory_multiplier']}\n";
echo "  Competitor Adjustment: {$result1['competitor_adjustment']}\n";
echo "  Dynamic Price: $" . number_format($result1['dynamic_price'], 2) . "\n";
echo "  Customer Discount: " . ($result1['discount_eligible'] * 100) . "%\n";
echo "  Final Price: $" . number_format($result1['final_price'], 2) . "\n\n";

// Test Case 2: Low demand, high inventory
echo "Test Case 2: Low Demand, High Inventory\n";
$inputs2 = [
    'base_price' => 100,
    'demand_level' => 'Low',
    'inventory_level' => 150,
    'price_vs_competitor' => 0.85,
    'customer_tier' => 'Silver'
];

$result2 = $ruleFlow->evaluate($template['config'], $inputs2);

echo "Market Conditions:\n";
echo "  Base Price: $" . number_format($inputs2['base_price']) . "\n";
echo "  Demand Level: {$inputs2['demand_level']}\n";
echo "  Inventory: {$inputs2['inventory_level']} units\n";
echo "  vs Competitor: " . ($inputs2['price_vs_competitor'] * 100) . "%\n";
echo "  Customer Tier: {$inputs2['customer_tier']}\n\n";

echo "Pricing Results:\n";
echo "  Demand Multiplier: {$result2['demand_multiplier']}\n";
echo "  Inventory Multiplier: {$result2['inventory_multiplier']}\n";
echo "  Competitor Adjustment: {$result2['competitor_adjustment']}\n";
echo "  Dynamic Price: $" . number_format($result2['dynamic_price'], 2) . "\n";
echo "  Customer Discount: " . ($result2['discount_eligible'] * 100) . "%\n";
echo "  Final Price: $" . number_format($result2['final_price'], 2) . "\n\n";

// Customer LTV template
echo "Customer Lifetime Value Analysis\n";
$ltvTemplate = $ruleFlow->getTemplate('customer_ltv');

$ltvInputs = [
    'avg_order_value' => 85,
    'orders_per_month' => 2.5,
    'customer_segment' => 'Premium'
];

$ltvResult = $ruleFlow->evaluate($ltvTemplate['config'], $ltvInputs);

echo "Customer Profile:\n";
echo "  Avg Order Value: $" . number_format($ltvInputs['avg_order_value']) . "\n";
echo "  Orders per Month: {$ltvInputs['orders_per_month']}\n";
echo "  Customer Segment: {$ltvInputs['customer_segment']}\n\n";

echo "LTV Analysis:\n";
echo "  Monthly Value: $" . number_format($ltvResult['monthly_value']) . "\n";
echo "  Annual Value: $" . number_format($ltvResult['annual_value']) . "\n";
echo "  Retention Factor: {$ltvResult['retention_factor']}\n";
echo "  Lifetime Months: " . round($ltvResult['lifetime_months'], 1) . "\n";
echo "  Customer LTV: $" . number_format($ltvResult['customer_ltv']) . "\n";
echo "  LTV Category: {$ltvResult['ltv_category']}\n\n";

// Pricing scenarios
echo "Pricing Scenarios Analysis:\n";
$scenarios = [
    ['demand' => 'High', 'inventory' => 5, 'tier' => 'VIP'],
    ['demand' => 'Medium', 'inventory' => 50, 'tier' => 'Gold'],
    ['demand' => 'Low', 'inventory' => 100, 'tier' => 'Silver'],
];

foreach ($scenarios as $i => $scenario) {
    $inputs = [
        'base_price' => 100,
        'demand_level' => $scenario['demand'],
        'inventory_level' => $scenario['inventory'],
        'price_vs_competitor' => 1.0,
        'customer_tier' => $scenario['tier']
    ];
    
    $result = $ruleFlow->evaluate($template['config'], $inputs);
    $scenario_num = $i + 1;
    
    echo "Scenario $scenario_num ({$scenario['demand']}, {$scenario['inventory']} units, {$scenario['tier']}):\n";
    echo "  Final Price: $" . number_format($result['final_price'], 2) . "\n";
}

echo "\nDemo completed.\n";