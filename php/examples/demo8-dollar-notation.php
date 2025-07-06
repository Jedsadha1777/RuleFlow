<?php

require_once __DIR__ . '/../src/RuleFlow.php';

echo "RuleFlow Demo: Dollar Notation Features\n";
echo "=======================================\n\n";

$ruleFlow = new RuleFlow();

// Basic $ notation
echo "1. Basic \$ Variable Usage\n";
$config1 = [
    'formulas' => [
        [
            'id' => 'base_price',
            'formula' => 'unit_price * quantity',
            'inputs' => ['unit_price', 'quantity'],
            'as' => '$subtotal'
        ],
        [
            'id' => 'tax_amount',
            'formula' => 'subtotal * tax_rate',
            'inputs' => ['subtotal', 'tax_rate'],
            'as' => '$tax'
        ],
        [
            'id' => 'final_total',
            'formula' => 'subtotal + tax',
            'inputs' => ['subtotal', 'tax']
        ]
    ]
];

$inputs1 = [
    'unit_price' => 25.99,
    'quantity' => 3,
    'tax_rate' => 0.08
];

$result1 = $ruleFlow->evaluate($config1, $inputs1);

echo "Order Details:\n";
echo "  Unit Price: $" . number_format($inputs1['unit_price'], 2) . "\n";
echo "  Quantity: {$inputs1['quantity']}\n";
echo "  Tax Rate: " . ($inputs1['tax_rate'] * 100) . "%\n\n";

echo "Calculations:\n";
echo "  Subtotal (\$subtotal): $" . number_format($result1['subtotal'], 2) . "\n";
echo "  Tax (\$tax): $" . number_format($result1['tax'], 2) . "\n";
echo "  Final Total: $" . number_format($result1['final_total'], 2) . "\n\n";

// $ notation in switch conditions
echo "2. \$ Notation in Switch Logic\n";
$config2 = [
    'formulas' => [
        [
            'id' => 'order_value',
            'formula' => 'price * items',
            'inputs' => ['price', 'items'],
            'as' => '$order_total'
        ],
        [
            'id' => 'shipping_cost',
            'switch' => '$order_total',
            'when' => [
                ['if' => ['op' => '>=', 'value' => 100], 'result' => 0],
                ['if' => ['op' => '>=', 'value' => 50], 'result' => 5.99],
                ['if' => ['op' => '>=', 'value' => 25], 'result' => 9.99]
            ],
            'default' => 15.99
        ],
        [
            'id' => 'discount_rate',
            'switch' => '$order_total',
            'when' => [
                ['if' => ['op' => '>=', 'value' => 200], 'result' => 0.15],
                ['if' => ['op' => '>=', 'value' => 100], 'result' => 0.10],
                ['if' => ['op' => '>=', 'value' => 50], 'result' => 0.05]
            ],
            'default' => 0
        ],
        [
            'id' => 'final_amount',
            'formula' => '(order_total * (1 - discount_rate)) + shipping_cost',
            'inputs' => ['order_total', 'discount_rate', 'shipping_cost']
        ]
    ]
];

$inputs2 = [
    'price' => 35.50,
    'items' => 4
];

$result2 = $ruleFlow->evaluate($config2, $inputs2);

echo "Order Summary:\n";
echo "  Item Price: $" . number_format($inputs2['price'], 2) . "\n";
echo "  Quantity: {$inputs2['items']}\n";
echo "  Order Total (\$order_total): $" . number_format($result2['order_total'], 2) . "\n";
echo "  Discount Rate: " . ($result2['discount_rate'] * 100) . "%\n";
echo "  Shipping Cost: $" . number_format($result2['shipping_cost'], 2) . "\n";
echo "  Final Amount: $" . number_format($result2['final_amount'], 2) . "\n\n";

// $ notation with set_vars
echo "3. \$ Notation with Set Variables\n";
$config3 = [
    'formulas' => [
        [
            'id' => 'customer_score',
            'formula' => 'purchase_history + loyalty_points',
            'inputs' => ['purchase_history', 'loyalty_points'],
            'as' => '$score'
        ],
        [
            'id' => 'tier_assignment',
            'switch' => '$score',
            'when' => [
                [
                    'if' => ['op' => '>=', 'value' => 1000],
                    'result' => 'Platinum',
                    'set_vars' => [
                        '$discount_pct' => 20,
                        '$free_shipping' => 1,
                        '$priority_support' => 1
                    ]
                ],
                [
                    'if' => ['op' => '>=', 'value' => 500],
                    'result' => 'Gold',
                    'set_vars' => [
                        '$discount_pct' => 15,
                        '$free_shipping' => 1,
                        '$priority_support' => 0
                    ]
                ],
                [
                    'if' => ['op' => '>=', 'value' => 200],
                    'result' => 'Silver',
                    'set_vars' => [
                        '$discount_pct' => 10,
                        '$free_shipping' => 0,
                        '$priority_support' => 0
                    ]
                ]
            ],
            'default' => 'Bronze',
            'default_vars' => [
                '$discount_pct' => 5,
                '$free_shipping' => 0,
                '$priority_support' => 0
            ]
        ],
        [
            'id' => 'benefits_summary',
            'formula' => 'discount_pct + free_shipping + priority_support',
            'inputs' => ['discount_pct', 'free_shipping', 'priority_support'],
            'as' => '$total_benefits'
        ]
    ]
];

$customers = [
    ['name' => 'John', 'purchase_history' => 800, 'loyalty_points' => 350],
    ['name' => 'Sarah', 'purchase_history' => 300, 'loyalty_points' => 150],
    ['name' => 'Mike', 'purchase_history' => 150, 'loyalty_points' => 80]
];

foreach ($customers as $customer) {
    $inputs3 = [
        'purchase_history' => $customer['purchase_history'],
        'loyalty_points' => $customer['loyalty_points']
    ];
    
    $result3 = $ruleFlow->evaluate($config3, $inputs3);
    
    echo "Customer: {$customer['name']}\n";
    echo "  Score (\$score): {$result3['score']}\n";
    echo "  Tier: {$result3['tier_assignment']}\n";
    echo "  Discount: {$result3['discount_pct']}%\n";
    echo "  Free Shipping: " . ($result3['free_shipping'] ? 'Yes' : 'No') . "\n";
    echo "  Priority Support: " . ($result3['priority_support'] ? 'Yes' : 'No') . "\n";
    echo "  Total Benefits (\$total_benefits): {$result3['total_benefits']}\n\n";
}

// $ notation in complex expressions
echo "4. \$ Notation in Complex Expressions\n";
$config4 = [
    'formulas' => [
        [
            'id' => 'base_salary',
            'formula' => 'hourly_rate * hours_per_week * 52',
            'inputs' => ['hourly_rate', 'hours_per_week'],
            'as' => '$annual_base'
        ],
        [
            'id' => 'performance_bonus',
            'formula' => 'annual_base * performance_rating / 100',
            'inputs' => ['annual_base', 'performance_rating'],
            'as' => '$bonus'
        ],
        [
            'id' => 'total_compensation',
            'formula' => 'annual_base + bonus + benefits_value',
            'inputs' => ['annual_base', 'bonus', 'benefits_value'],
            'as' => '$total_comp'
        ],
        [
            'id' => 'tax_bracket',
            'switch' => '$total_comp',
            'when' => [
                ['if' => ['op' => '>=', 'value' => 100000], 'result' => 'High'],
                ['if' => ['op' => '>=', 'value' => 60000], 'result' => 'Medium'],
                ['if' => ['op' => '>=', 'value' => 30000], 'result' => 'Low']
            ],
            'default' => 'Minimum'
        ],
        [
            'id' => 'net_income',
            'switch' => 'tax_bracket',
            'when' => [
                ['if' => ['op' => '==', 'value' => 'High'], 'result' => 'calculated', 'set_vars' => ['$tax_rate' => 0.28]],
                ['if' => ['op' => '==', 'value' => 'Medium'], 'result' => 'calculated', 'set_vars' => ['$tax_rate' => 0.22]],
                ['if' => ['op' => '==', 'value' => 'Low'], 'result' => 'calculated', 'set_vars' => ['$tax_rate' => 0.15]]
            ],
            'default' => 'calculated',
            'default_vars' => ['$tax_rate' => 0.10]
        ],
        [
            'id' => 'calculated_net_income',
            'formula' => 'total_comp * (1 - tax_rate)',
            'inputs' => ['total_comp', 'tax_rate']
        ]
    ]
];

$employee = [
    'hourly_rate' => 35,
    'hours_per_week' => 40,
    'performance_rating' => 12,
    'benefits_value' => 8000
];

$result4 = $ruleFlow->evaluate($config4, $employee);

echo "Employee Compensation Analysis:\n";
echo "  Hourly Rate: $" . number_format($employee['hourly_rate'], 2) . "\n";
echo "  Hours/Week: {$employee['hours_per_week']}\n";
echo "  Performance Rating: {$employee['performance_rating']}%\n";
echo "  Benefits Value: $" . number_format($employee['benefits_value']) . "\n\n";

echo "Calculated Results:\n";
echo "  Annual Base (\$annual_base): $" . number_format($result4['annual_base']) . "\n";
echo "  Performance Bonus (\$bonus): $" . number_format($result4['bonus']) . "\n";
echo "  Total Compensation (\$total_comp): $" . number_format($result4['total_comp']) . "\n";
echo "  Tax Bracket: {$result4['tax_bracket']}\n";
echo "  Tax Rate: " . ($result4['tax_rate'] * 100) . "%\n";
echo "  Net Income: $" . number_format($result4['calculated_net_income']) . "\n";

echo "\nDemo completed.\n";