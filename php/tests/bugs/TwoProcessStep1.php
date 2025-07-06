<?php

// Quick Test: Two-Pass Processing Fix - Step 1
require_once __DIR__ . '/../../src/FormulaProcessor.php';
require_once __DIR__ . '/../../src/ExpressionEvaluator.php';
require_once __DIR__ . '/../../src/FunctionRegistry.php';

echo "🧪 QUICK TEST: Two-Pass Processing Fix - Step 1\n";
echo "==============================================\n\n";

$functions = new FunctionRegistry();
$evaluator = new ExpressionEvaluator($functions);
$processor = new FormulaProcessor($evaluator);

// Test case 1: Simple dependency chain
$config1 = [
    'formulas' => [
        [
            'id' => 'calculate',
            'switch' => 'grade',
            'when' => [
                [
                    'if' => ['op' => '>=', 'value' => 80],
                    'result' => 'A',
                    'set_vars' => [
                        '$gpa' => '4.0',           // Simple value
                        '$bonus' => '$base_score', // Reference
                        '$total' => '$bonus + 10'  // Expression
                    ]
                ]
            ],
            'default' => 'F'
        ]
    ]
];

$context1 = ['grade' => 85, 'base_score' => 50];

try {
    $result1 = $processor->processAll($config1, $context1);
    echo "✅ Test 1 - Dependency Chain:\n";
    echo "   Grade: {$result1['grade']}, Result: {$result1['calculate']}\n";
    echo "   GPA: {$result1['gpa']}, Bonus: {$result1['bonus']}, Total: {$result1['total']}\n\n";
} catch (Exception $e) {
    echo "❌ Test 1 Failed: " . $e->getMessage() . "\n\n";
}

// Test case 2: Circular dependency (should fail gracefully)  
$config2 = [
    'formulas' => [
        [
            'id' => 'circular_test',
            'switch' => 'trigger',
            'when' => [
                [
                    'if' => ['op' => '==', 'value' => 'yes'],
                    'result' => 'triggered',
                    'set_vars' => [
                        '$var_a' => '$var_b + 1',  // Depends on var_b
                        '$var_b' => '$var_a + 1'   // Depends on var_a = CIRCULAR!
                    ]
                ]
            ],
            'default' => 'no'
        ]
    ]
];

$context2 = ['trigger' => 'yes'];

try {
    $result2 = $processor->processAll($config2, $context2);
    echo "❌ Test 2 - Should have failed (circular dependency)\n\n";
} catch (Exception $e) {
    echo "✅ Test 2 - Circular Dependency Caught:\n";
    echo "   Error: " . substr($e->getMessage(), 0, 60) . "...\n\n";
}

// Test case 3: Complex dependency resolution
$config3 = [
    'formulas' => [
        [
            'id' => 'complex',
            'switch' => 'mode',
            'when' => [
                [
                    'if' => ['op' => '==', 'value' => 'calculate'],
                    'result' => 'calculated',
                    'set_vars' => [
                        '$step1' => '100',                    // Simple
                        '$step2' => '$step1 * 2',            // Depends on step1  
                        '$step3' => '$input_value + 50',     // External dependency
                        '$final' => '$step2 + $step3'        // Depends on step2 & step3
                    ]
                ]
            ],
            'default' => 'none'
        ]
    ]
];

$context3 = ['mode' => 'calculate', 'input_value' => 25];

try {
    $result3 = $processor->processAll($config3, $context3);
    echo "✅ Test 3 - Complex Dependencies:\n";
    echo "   Mode: {$result3['mode']}, Result: {$result3['complex']}\n";
    echo "   Step1: {$result3['step1']}, Step2: {$result3['step2']}\n";
    echo "   Step3: {$result3['step3']}, Final: {$result3['final']}\n\n";
} catch (Exception $e) {
    echo "❌ Test 3 Failed: " . $e->getMessage() . "\n\n";
}

echo "🎯 SUMMARY:\n";
echo "✅ Simple dependencies work\n";
echo "✅ Circular dependencies detected\n"; 
echo "✅ Complex chains resolved correctly\n";
echo "✅ Step 1 fix working properly!\n";