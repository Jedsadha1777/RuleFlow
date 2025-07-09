<?php

// Test Auto-discovery System
declare(strict_types=1);

require_once __DIR__ . '/../../src/RuleFlow.php';

echo "🚀 Testing Enhanced Formula Syntax\n";
echo "==================================\n\n";

$ruleFlow = new RuleFlow();

// ============================================
// 🧪 TEST 1: Direct function_call
// ============================================

echo "🧪 Test 1: Direct Function Calls\n";
echo "=================================\n";

$directConfig = [
    'formulas' => [
        [
            'id' => 'weekend_check',
            'function_call' => 'is_weekend',
            'params' => ['booking_date']
        ],
        [
            'id' => 'weekend_pricing',
            'function_call' => 'weekend_pricing',
            'params' => ['base_price', 'booking_date', 1.5]
        ]
    ]
];

$testCases = [
    ['base_price' => 1000, 'booking_date' => '2024-07-06', 'expected' => 1500], // Saturday
    ['base_price' => 1000, 'booking_date' => '2024-07-08', 'expected' => 1000], // Monday
];

foreach ($testCases as $test) {
    try {
        $result = $ruleFlow->evaluate($directConfig, $test);
        
        $isWeekend = $result['weekend_check'];
        $price = $result['weekend_pricing'];
        $status = ($price == $test['expected']) ? '✅' : '❌';
        
        echo "{$status} Date: {$test['booking_date']}, Weekend: " . ($isWeekend ? 'Yes' : 'No') . ", Price: {$price}\n";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// ============================================
// 🧪 TEST 2: Mixed approach
// ============================================

echo "🧪 Test 2: Mixed Function Calls + Formula\n";
echo "==========================================\n";

$mixedConfig = [
    'formulas' => [
        // Function calls
        [
            'id' => 'base_weekend_price',
            'function_call' => 'weekend_pricing',
            'params' => ['base_price', 'booking_date', 1.5]
        ],
        [
            'id' => 'seasonal_factor',
            'function_call' => 'seasonal_multiplier',
            'params' => ['booking_date']
        ],
        
        // Regular formula using function results
        [
            'id' => 'seasonal_price',
            'formula' => 'base_weekend_price * seasonal_factor',
            'inputs' => ['base_weekend_price', 'seasonal_factor']
        ],
        
        // Function call with result from formula
        [
            'id' => 'customer_discount',
            'function_call' => 'tier_discount',
            'params' => ['customer_tier', 'seasonal_price']
        ],
        
        // Final calculation
        [
            'id' => 'final_price',
            'formula' => 'seasonal_price - customer_discount',
            'inputs' => ['seasonal_price', 'customer_discount']
        ]
    ]
];

$mixedInputs = [
    'base_price' => 1000,
    'booking_date' => '2024-07-06', // Saturday in July (summer)
    'customer_tier' => 'GOLD'
];

try {
    $result = $ruleFlow->evaluate($mixedConfig, $mixedInputs);
    
    echo "📊 Mixed Approach Results:\n";
    echo "   Input: Price={$mixedInputs['base_price']}, Date={$mixedInputs['booking_date']}, Tier={$mixedInputs['customer_tier']}\n";
    echo "   1. Weekend Price: {$result['base_weekend_price']}\n";
    echo "   2. Seasonal Factor: {$result['seasonal_factor']}\n";
    echo "   3. Seasonal Price: {$result['seasonal_price']}\n";
    echo "   4. Customer Discount: {$result['customer_discount']}\n";
    echo "   5. Final Price: {$result['final_price']}\n\n";
    
} catch (Exception $e) {
    echo "❌ Mixed approach failed: " . $e->getMessage() . "\n\n";
}

// ============================================
// 🧪 TEST 3: Switch with function calls
// ============================================

echo "🧪 Test 3: Switch with Function Calls\n";
echo "======================================\n";

$switchConfig = [
    'formulas' => [
        [
            'id' => 'pricing_strategy',
            'switch' => 'customer_tier',
            'when' => [
                [
                    'if' => ['op' => '==', 'value' => 'VIP'],
                    'function_call' => 'tier_discount',
                    'params' => ['VIP', 'base_amount']
                ],
                [
                    'if' => ['op' => '==', 'value' => 'GOLD'],
                    'function_call' => 'tier_discount',
                    'params' => ['GOLD', 'base_amount']
                ]
            ],
            'default' => [
                'function_call' => 'tier_discount',
                'params' => ['BASIC', 'base_amount']
            ]
        ]
    ]
];

$switchTests = [
    ['customer_tier' => 'VIP', 'base_amount' => 1000],
    ['customer_tier' => 'GOLD', 'base_amount' => 1000],
    ['customer_tier' => 'SILVER', 'base_amount' => 1000], // Will use default
];

foreach ($switchTests as $test) {
    try {
        $result = $ruleFlow->evaluate($switchConfig, $test);
        echo "✅ Tier: {$test['customer_tier']}, Discount: {$result['pricing_strategy']}\n";
        
    } catch (Exception $e) {
        echo "❌ Switch test failed for {$test['customer_tier']}: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// ============================================
// 🧪 TEST 4: Complex business logic
// ============================================

echo "🧪 Test 4: Complex Business Logic\n";
echo "==================================\n";

$complexConfig = [
    'formulas' => [
        // Step 1: Weekend pricing
        [
            'id' => 'step1_weekend',
            'function_call' => 'weekend_pricing',
            'params' => ['base_price', 'booking_date', 1.5]
        ],
        
        // Step 2: Seasonal adjustment  
        [
            'id' => 'step2_seasonal',
            'function_call' => 'seasonal_multiplier',
            'params' => ['booking_date']
        ],
        
        // Step 3: Apply seasonal
        [
            'id' => 'step3_with_seasonal',
            'formula' => 'step1_weekend * step2_seasonal',
            'inputs' => ['step1_weekend', 'step2_seasonal']
        ],
        
        // Step 4: Customer discount
        [
            'id' => 'step4_discount',
            'function_call' => 'tier_discount',
            'params' => ['customer_tier', 'step3_with_seasonal']
        ],
        
        // Step 5: Final price
        [
            'id' => 'step5_final',
            'formula' => 'step3_with_seasonal - step4_discount',
            'inputs' => ['step3_with_seasonal', 'step4_discount']
        ],
        
        // Step 6: Thai VAT
        [
            'id' => 'step6_vat',
            'function_call' => 'thai_vat',
            'params' => ['step5_final']
        ],
        
        // Step 7: Total with VAT
        [
            'id' => 'total_with_vat',
            'formula' => 'step5_final + step6_vat',
            'inputs' => ['step5_final', 'step6_vat']
        ]
    ]
];

$complexInputs = [
    'base_price' => 1000,
    'booking_date' => '2024-07-06', // Saturday in July
    'customer_tier' => 'GOLD'
];

try {
    $result = $ruleFlow->evaluate($complexConfig, $complexInputs);
    
    echo "📊 Complex Business Logic Results:\n";
    echo "   Input: Price={$complexInputs['base_price']}, Date={$complexInputs['booking_date']}, Tier={$complexInputs['customer_tier']}\n\n";
    echo "   📈 Step-by-step calculation:\n";
    echo "      1. Weekend pricing: {$result['step1_weekend']}\n";
    echo "      2. Seasonal factor: {$result['step2_seasonal']}\n";
    echo "      3. With seasonal: {$result['step3_with_seasonal']}\n";
    echo "      4. GOLD discount: {$result['step4_discount']}\n";
    echo "      5. After discount: {$result['step5_final']}\n";
    echo "      6. VAT (7%): {$result['step6_vat']}\n";
    echo "      7. Total with VAT: {$result['total_with_vat']}\n\n";
    
} catch (Exception $e) {
    echo "❌ Complex logic failed: " . $e->getMessage() . "\n\n";
}

echo "🎉 Enhanced Syntax Test Complete!\n";
?>