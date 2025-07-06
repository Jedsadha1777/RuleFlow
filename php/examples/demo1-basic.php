<?php

require_once __DIR__ . '/../src/RuleFlow.php';

echo "RuleFlow Demo: Basic Usage\n";
echo "==========================\n\n";

$ruleFlow = new RuleFlow();

// Simple calculation
echo "1. Basic Math Expression\n";
$config1 = [
    'formulas' => [
        [
            'id' => 'total',
            'formula' => 'price * quantity + shipping',
            'inputs' => ['price', 'quantity', 'shipping']
        ]
    ]
];

$inputs1 = ['price' => 25.99, 'quantity' => 3, 'shipping' => 9.99];
$result1 = $ruleFlow->evaluate($config1, $inputs1);

echo "Input: price=25.99, quantity=3, shipping=9.99\n";
echo "Formula: price * quantity + shipping\n";
echo "Result: {$result1['total']}\n\n";

// Switch logic
echo "2. Conditional Logic\n";
$config2 = [
    'formulas' => [
        [
            'id' => 'subtotal',
            'formula' => 'price * quantity',
            'inputs' => ['price', 'quantity']
        ],
        [
            'id' => 'discount',
            'switch' => 'subtotal',
            'when' => [
                ['if' => ['op' => '>=', 'value' => 100], 'result' => 10],
                ['if' => ['op' => '>=', 'value' => 50], 'result' => 5]
            ],
            'default' => 0
        ],
        [
            'id' => 'final_total',
            'formula' => 'subtotal - discount',
            'inputs' => ['subtotal', 'discount']
        ]
    ]
];

$inputs2 = ['price' => 30, 'quantity' => 4];
$result2 = $ruleFlow->evaluate($config2, $inputs2);

echo "Input: price=30, quantity=4\n";
echo "Subtotal: {$result2['subtotal']}\n";
echo "Discount: {$result2['discount']}\n";
echo "Final total: {$result2['final_total']}\n\n";

// Using functions
echo "3. Built-in Functions\n";
$config3 = [
    'formulas' => [
        [
            'id' => 'average_score',
            'formula' => 'avg(math, english, science)',
            'inputs' => ['math', 'english', 'science']
        ],
        [
            'id' => 'grade',
            'switch' => 'average_score',
            'when' => [
                ['if' => ['op' => '>=', 'value' => 80], 'result' => 'A'],
                ['if' => ['op' => '>=', 'value' => 70], 'result' => 'B'],
                ['if' => ['op' => '>=', 'value' => 60], 'result' => 'C']
            ],
            'default' => 'F'
        ]
    ]
];

$inputs3 = ['math' => 85, 'english' => 78, 'science' => 92];
$result3 = $ruleFlow->evaluate($config3, $inputs3);

echo "Input: math=85, english=78, science=92\n";
echo "Average: {$result3['average_score']}\n";
echo "Grade: {$result3['grade']}\n\n";

// Error handling
echo "4. Error Handling\n";
try {
    $badConfig = [
        'formulas' => [
            [
                'id' => 'calculation',
                'formula' => 'a + b',
                'inputs' => ['a', 'b']
            ]
        ]
    ];
    
    $badInputs = ['a' => 10]; // Missing 'b'
    $ruleFlow->evaluate($badConfig, $badInputs);
} catch (Exception $e) {
    echo "Caught error: " . $e->getMessage() . "\n";
}

echo "\nDemo completed.\n";