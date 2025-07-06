<?php

require_once __DIR__ . '/../src/RuleFlow.php';

echo "RuleFlow Demo: Custom Functions\n";
echo "===============================\n\n";

$ruleFlow = new RuleFlow();

// Register custom business functions
echo "1. Registering Custom Functions\n";

// Shipping cost calculator
$ruleFlow->registerFunction('shipping_cost', function($weight, $distance, $zone_code) {
    $base_rate = match((int)$zone_code) {
        1 => 0.5,  // domestic
        2 => 1.2,  // international
        3 => 2.0,  // express
        default => 0.8
    };
    
    $distance_factor = min($distance / 100, 3.0); // Cap at 3x
    return ($weight * $base_rate) + ($distance_factor * 5);
});

// Customer lifetime value
$ruleFlow->registerFunction('ltv_estimate', function($monthly_spend, $months_active, $churn_rate) {
    if ($churn_rate <= 0) return $monthly_spend * $months_active * 10; // Assume 10 years
    
    $avg_lifetime_months = 1 / $churn_rate;
    return $monthly_spend * min($avg_lifetime_months, 120); // Cap at 10 years
});

// Credit risk score
$ruleFlow->registerFunction('risk_score', function($credit_score, $income, $debt) {
    $credit_factor = ($credit_score - 300) / 550; // Normalize 300-850 to 0-1
    $debt_ratio = $debt / max($income, 1);
    $income_factor = min($income / 50000, 2.0); // Cap at 2x
    
    return round(($credit_factor * 40) + ($income_factor * 30) - ($debt_ratio * 70), 1);
});

// Inventory turnover
$ruleFlow->registerFunction('turnover_rate', function($sales_volume, $avg_inventory, $days) {
    if ($avg_inventory <= 0) return 0;
    return ($sales_volume / $avg_inventory) * (365 / $days);
});

echo "Custom functions registered:\n";
echo "  - shipping_cost(weight, distance, zone_code)\n";
echo "  - ltv_estimate(monthly_spend, months_active, churn_rate)\n";
echo "  - risk_score(credit_score, income, debt)\n";
echo "  - turnover_rate(sales_volume, avg_inventory, days)\n\n";

// Using custom functions in formulas
echo "2. E-commerce Order Processing\n";
$orderConfig = [
    'formulas' => [
        [
            'id' => 'product_total',
            'formula' => 'price * quantity',
            'inputs' => ['price', 'quantity']
        ],
        [
            'id' => 'zone_code',
            'switch' => 'shipping_zone',
            'when' => [
                ['if' => ['op' => '==', 'value' => 'domestic'], 'result' => 1],
                ['if' => ['op' => '==', 'value' => 'international'], 'result' => 2],
                ['if' => ['op' => '==', 'value' => 'express'], 'result' => 3]
            ],
            'default' => 0
        ],
        [
            'id' => 'shipping_fee',
            'formula' => 'shipping_cost(total_weight, delivery_distance, zone_code)',
            'inputs' => ['total_weight', 'delivery_distance', 'zone_code']
        ],
        [
            'id' => 'grand_total',
            'formula' => 'product_total + shipping_fee',
            'inputs' => ['product_total', 'shipping_fee']
        ],
        [
            'id' => 'customer_ltv',
            'formula' => 'ltv_estimate(avg_monthly_spend, months_since_signup, monthly_churn_rate)',
            'inputs' => ['avg_monthly_spend', 'months_since_signup', 'monthly_churn_rate']
        ],
        [
            'id' => 'order_priority',
            'switch' => 'customer_ltv',
            'when' => [
                ['if' => ['op' => '>=', 'value' => 5000], 'result' => 'VIP'],
                ['if' => ['op' => '>=', 'value' => 2000], 'result' => 'Premium'],
                ['if' => ['op' => '>=', 'value' => 500], 'result' => 'Standard']
            ],
            'default' => 'Basic'
        ]
    ]
];

$orderData = [
    'price' => 89.99,
    'quantity' => 2,
    'total_weight' => 3.5,
    'delivery_distance' => 150,
    'shipping_zone' => 'domestic',
    'avg_monthly_spend' => 250,
    'months_since_signup' => 18,
    'monthly_churn_rate' => 0.05
];

$orderResult = $ruleFlow->evaluate($orderConfig, $orderData);

echo "Order Details:\n";
echo "  Product Price: $" . number_format($orderData['price'], 2) . "\n";
echo "  Quantity: {$orderData['quantity']}\n";
echo "  Weight: {$orderData['total_weight']} kg\n";
echo "  Distance: {$orderData['delivery_distance']} km\n";
echo "  Zone: {$orderData['shipping_zone']}\n\n";

echo "Calculated Results:\n";
echo "  Product Total: $" . number_format($orderResult['product_total'], 2) . "\n";
echo "  Shipping Fee: $" . number_format($orderResult['shipping_fee'], 2) . "\n";
echo "  Grand Total: $" . number_format($orderResult['grand_total'], 2) . "\n";
echo "  Customer LTV: $" . number_format($orderResult['customer_ltv']) . "\n";
echo "  Order Priority: {$orderResult['order_priority']}\n\n";

// Credit assessment with custom risk scoring
echo "3. Credit Risk Assessment\n";
$creditConfig = [
    'formulas' => [
        [
            'id' => 'base_risk_score',
            'formula' => 'risk_score(credit_score, annual_income, total_debt)',
            'inputs' => ['credit_score', 'annual_income', 'total_debt']
        ],
        [
            'id' => 'employment_adjustment',
            'switch' => 'employment_status',
            'when' => [
                ['if' => ['op' => '==', 'value' => 'permanent'], 'result' => 5],
                ['if' => ['op' => '==', 'value' => 'contract'], 'result' => 0],
                ['if' => ['op' => '==', 'value' => 'self_employed'], 'result' => -3]
            ],
            'default' => -5
        ],
        [
            'id' => 'final_risk_score',
            'formula' => 'base_risk_score + employment_adjustment',
            'inputs' => ['base_risk_score', 'employment_adjustment']
        ],
        [
            'id' => 'credit_decision',
            'switch' => 'final_risk_score',
            'when' => [
                ['if' => ['op' => '>=', 'value' => 70], 'result' => 'Approved'],
                ['if' => ['op' => '>=', 'value' => 50], 'result' => 'Review'],
                ['if' => ['op' => '>=', 'value' => 30], 'result' => 'Conditional']
            ],
            'default' => 'Declined'
        ]
    ]
];

$applicants = [
    ['name' => 'Alice', 'credit_score' => 750, 'annual_income' => 85000, 'total_debt' => 15000, 'employment_status' => 'permanent'],
    ['name' => 'Bob', 'credit_score' => 680, 'annual_income' => 55000, 'total_debt' => 25000, 'employment_status' => 'contract'],
    ['name' => 'Carol', 'credit_score' => 620, 'annual_income' => 45000, 'total_debt' => 35000, 'employment_status' => 'self_employed']
];

foreach ($applicants as $applicant) {
    $inputs = [
        'credit_score' => $applicant['credit_score'],
        'annual_income' => $applicant['annual_income'],
        'total_debt' => $applicant['total_debt'],
        'employment_status' => $applicant['employment_status']
    ];
    
    $result = $ruleFlow->evaluate($creditConfig, $inputs);
    
    echo "Applicant: {$applicant['name']}\n";
    echo "  Credit Score: {$applicant['credit_score']}\n";
    echo "  Annual Income: $" . number_format($applicant['annual_income']) . "\n";
    echo "  Total Debt: $" . number_format($applicant['total_debt']) . "\n";
    echo "  Employment: {$applicant['employment_status']}\n";
    echo "  Base Risk Score: {$result['base_risk_score']}\n";
    echo "  Employment Adj: {$result['employment_adjustment']}\n";
    echo "  Final Risk Score: {$result['final_risk_score']}\n";
    echo "  Decision: {$result['credit_decision']}\n\n";
}

// Inventory management with custom functions
echo "4. Inventory Management\n";
$inventoryConfig = [
    'formulas' => [
        [
            'id' => 'current_turnover',
            'formula' => 'turnover_rate(units_sold, avg_stock_level, period_days)',
            'inputs' => ['units_sold', 'avg_stock_level', 'period_days']
        ],
        [
            'id' => 'reorder_point',
            'formula' => '(units_sold / period_days) * lead_time_days + safety_stock',
            'inputs' => ['units_sold', 'period_days', 'lead_time_days', 'safety_stock']
        ],
        [
            'id' => 'stock_status',
            'switch' => 'current_stock',
            'when' => [
                ['if' => ['op' => '<=', 'value' => '$reorder_point'], 'result' => 'Reorder Now'],
                ['if' => ['op' => '<=', 'value' => '$reorder_point * 1.5'], 'result' => 'Low Stock'],
                ['if' => ['op' => '>=', 'value' => '$avg_stock_level * 2'], 'result' => 'Overstock']
            ],
            'default' => 'Normal'
        ]
    ]
];

$products = [
    ['name' => 'Widget A', 'units_sold' => 120, 'avg_stock_level' => 50, 'current_stock' => 15, 'lead_time_days' => 7, 'safety_stock' => 10],
    ['name' => 'Widget B', 'units_sold' => 80, 'avg_stock_level' => 40, 'current_stock' => 45, 'lead_time_days' => 5, 'safety_stock' => 8],
    ['name' => 'Widget C', 'units_sold' => 200, 'avg_stock_level' => 100, 'current_stock' => 180, 'lead_time_days' => 10, 'safety_stock' => 20]
];

foreach ($products as $product) {
    $inputs = array_merge($product, ['period_days' => 30]);
    unset($inputs['name']);
    
    $result = $ruleFlow->evaluate($inventoryConfig, $inputs);
    
    echo "Product: {$product['name']}\n";
    echo "  Units Sold (30 days): {$product['units_sold']}\n";
    echo "  Current Stock: {$product['current_stock']}\n";
    echo "  Turnover Rate: " . round($result['current_turnover'], 2) . "x/year\n";
    echo "  Reorder Point: " . round($result['reorder_point']) . " units\n";
    echo "  Status: {$result['stock_status']}\n\n";
}

// Available functions summary
echo "5. Available Functions Summary\n";
$functions = $ruleFlow->getAvailableFunctions();
echo "Built-in functions: " . count($functions['functions']) . "\n";
echo "Categories: " . implode(', ', array_keys($functions['categories'])) . "\n";

echo "\nDemo completed.\n";