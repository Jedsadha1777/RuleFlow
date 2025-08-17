<?php
declare(strict_types=1);

require_once __DIR__ . '/../../src/RuleFlow.php';

/**
 * เทสสูตรด้วย RuleFlow (เวอร์ชันที่ใช้งานได้)
 */
function testFormulaWithRuleFlow() {
    $ruleFlow = new RuleFlow();
    
    // แบบที่ 1: สูตรเดียวยาวๆ (เหมือนของเดิม)
    $config1 = [
        'formulas' => [
            [
                'id' => 'complex_formula',
                'formula' => 'max(0, min(100, (i+3)*((((m_2*1)+(m_1*2)+(m_0*3))-(((m_2+m_1+m_0)/3)*(6)))/2) + (((m_2+m_1+m_0)/3)-((((m_2*1)+(m_1*2)+(m_0*3))-(((m_2+m_1+m_0)/3)*(6)))/2)*2)))',
                'inputs' => ['i', 'm_2', 'm_1', 'm_0']
            ]
        ]
    ];
    
    // แบบที่ 2: แยกเป็นขั้นตอน (ใช้ผลลัพธ์จาก formula ก่อนหน้า)
    $config2 = [
        'formulas' => [
            // ขั้นตอน 1: ค่าเฉลี่ย
            [
                'id' => 'avg',
                'formula' => '(m_2 + m_1 + m_0) / 3',
                'inputs' => ['m_2', 'm_1', 'm_0']
            ],
            
            // ขั้นตอน 2: weighted sum
            [
                'id' => 'w_sum',
                'formula' => '(m_2 * 1) + (m_1 * 2) + (m_0 * 3)',
                'inputs' => ['m_2', 'm_1', 'm_0']
            ],
            
            // ขั้นตอน 3: part1
            [
                'id' => 'part1',
                'formula' => '(w_sum - (avg * 6)) / 2',
                'inputs' => ['w_sum', 'avg']
            ],
            
            // ขั้นตอน 4: part2
            [
                'id' => 'part2',
                'formula' => 'avg - (part1 * 2)',
                'inputs' => ['avg', 'part1']
            ],
            
            // ขั้นตอน 5: ผลลัพธ์สุดท้าย
            [
                'id' => 'final_result',
                'formula' => 'max(0, min(100, (i + 3) * part1 + part2))',
                'inputs' => ['i', 'part1', 'part2']
            ]
        ]
    ];
    
    // เทสเคสต่างๆ
    $testCases = [
        [
            'name' => 'เทส 1: ค่าปกติ',
            'inputs' => ['i' => 5, 'm_2' => 10, 'm_1' => 20, 'm_0' => 30]
        ],
        [
            'name' => 'เทส 2: ค่าเท่ากัน',
            'inputs' => ['i' => 1, 'm_2' => 5, 'm_1' => 5, 'm_0' => 5]
        ],
        [
            'name' => 'เทส 3: ค่าสูง',
            'inputs' => ['i' => 10, 'm_2' => 50, 'm_1' => 60, 'm_0' => 70]
        ],
        [
            'name' => 'เทส 4: ค่าติดลบ',
            'inputs' => ['i' => -10, 'm_2' => 1, 'm_1' => 1, 'm_0' => 1]
        ],
        [
            'name' => 'เทส 5: ค่าเป็นศูนย์',
            'inputs' => ['i' => 0, 'm_2' => 0, 'm_1' => 0, 'm_0' => 0]
        ]
    ];
    
    echo "=== เทสสูตรด้วย RuleFlow ===\n\n";
    
    foreach ($testCases as $test) {
        echo "🧪 " . $test['name'] . "\n";
        echo "Input: ";
        foreach ($test['inputs'] as $key => $value) {
            echo "$key=$value ";
        }
        echo "\n";
        
        try {
            // เทสแบบสูตรเดียว
            $result1 = $ruleFlow->evaluate($config1, $test['inputs']);
            echo "  - สูตรเดียว: " . number_format($result1['complex_formula'], 2) . "\n";
            
            // เทสแบบแยกขั้นตอน
            $result2 = $ruleFlow->evaluate($config2, $test['inputs']);
            echo "  - แยกขั้นตอน:\n";
            echo "    * ค่าเฉลี่ย: " . number_format($result2['avg'], 2) . "\n";
            echo "    * Weighted Sum: " . number_format($result2['w_sum'], 2) . "\n";
            echo "    * Part1: " . number_format($result2['part1'], 2) . "\n";
            echo "    * Part2: " . number_format($result2['part2'], 2) . "\n";
            echo "    * ✅ ผลลัพธ์: " . number_format($result2['final_result'], 2) . "\n";
            
            // เช็คว่าผลลัพธ์ตรงกันไหม
            $diff = abs($result1['complex_formula'] - $result2['final_result']);
            if ($diff < 0.0001) {
                echo "  - ✅ ผลลัพธ์ตรงกัน!\n";
            } else {
                echo "  - ❌ ผลลัพธ์ไม่ตรงกัน (ต่าง: " . $diff . ")\n";
            }
            
        } catch (Exception $e) {
            echo "  - ❌ Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
}

/**
 * เทสประสิทธิภาพแบบง่าย
 */
function simplePerformanceTest() {
    echo "=== เทสประสิทธิภาพ ===\n\n";
    
    $ruleFlow = new RuleFlow();
    
    $config = [
        'formulas' => [
            [
                'id' => 'result',
                'formula' => 'max(0, min(100, (i+3)*((((m_2*1)+(m_1*2)+(m_0*3))-(((m_2+m_1+m_0)/3)*(6)))/2) + (((m_2+m_1+m_0)/3)-((((m_2*1)+(m_1*2)+(m_0*3))-(((m_2+m_1+m_0)/3)*(6)))/2)*2)))',
                'inputs' => ['i', 'm_2', 'm_1', 'm_0']
            ]
        ]
    ];
    
    $testData = ['i' => 5, 'm_2' => 10, 'm_1' => 20, 'm_0' => 30];
    $iterations = 1000;
    
    $start = microtime(true);
    for ($x = 0; $x < $iterations; $x++) {
        $ruleFlow->evaluate($config, $testData);
    }
    $end = microtime(true);
    $time = ($end - $start) * 1000;
    
    echo "การทำงาน $iterations ครั้ง: " . number_format($time, 2) . " ms\n";
    echo "เฉลี่ยต่อครั้ง: " . number_format($time / $iterations, 4) . " ms\n\n";
}

/**
 * เทส validation และ error handling
 */
function testValidationAndErrors() {
    echo "=== เทส Validation และ Error Handling ===\n\n";
    
    $ruleFlow = new RuleFlow();
    
    // เทส 1: Configuration ที่ถูกต้อง
    $validConfig = [
        'formulas' => [
            [
                'id' => 'test',
                'formula' => 'a + b',
                'inputs' => ['a', 'b']
            ]
        ]
    ];
    
    echo "🔍 เทส Configuration ที่ถูกต้อง:\n";
    try {
        $result = $ruleFlow->evaluate($validConfig, ['a' => 10, 'b' => 20]);
        echo "  ✅ ผลลัพธ์: " . $result['test'] . "\n";
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
    
    // เทส 2: Configuration ผิด
    $invalidConfig = [
        'formulas' => [
            [
                'id' => 'invalid',
                'formula' => 'a +* b', // syntax ผิด
                'inputs' => ['a', 'b']
            ]
        ]
    ];
    
    echo "\n🔍 เทส Configuration ที่ผิด:\n";
    try {
        $result = $ruleFlow->evaluate($invalidConfig, ['a' => 10, 'b' => 20]);
        echo "  ❌ ไม่ควรทำงานได้\n";
    } catch (Exception $e) {
        echo "  ✅ จับ error ได้: " . $e->getMessage() . "\n";
    }
    
    // เทส 3: Input ขาดหาย
    echo "\n🔍 เทส Input ขาดหาย:\n";
    try {
        $result = $ruleFlow->evaluate($validConfig, ['a' => 10]); // ขาด 'b'
        echo "  ⚠️ ทำงานได้ แต่อาจได้ผลผิด: " . ($result['test'] ?? 'null') . "\n";
    } catch (Exception $e) {
        echo "  ✅ จับ error ได้: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

/**
 * ฟังก์ชันช่วยสำหรับคำนวณแบบเดิม (เปรียบเทียบ)
 */
function originalCalculation($i, $m_2, $m_1, $m_0) {
    return max(0, min(100,
        (($i+3)*((((($m_2*1)+($m_1*2)+($m_0*3)))-((($m_2+$m_1+$m_0)/3)*(6)))/2))
        + ((($m_2+$m_1+$m_0)/3)-((((($m_2*1)+($m_1*2)+($m_0*3))-((($m_2+$m_1+$m_0)/3)*(6)))/2)*2))
    ));
}

/**
 * เปรียบเทียบผลลัพธ์กับสูตรเดิม
 */
function compareResults() {
    echo "=== เปรียบเทียบกับสูตรเดิม ===\n\n";
    
    $ruleFlow = new RuleFlow();
    
    $config = [
        'formulas' => [
            [
                'id' => 'formula_result',
                'formula' => 'max(0, min(100, (i+3)*((((m_2*1)+(m_1*2)+(m_0*3))-(((m_2+m_1+m_0)/3)*(6)))/2) + (((m_2+m_1+m_0)/3)-((((m_2*1)+(m_1*2)+(m_0*3))-(((m_2+m_1+m_0)/3)*(6)))/2)*2)))',
                'inputs' => ['i', 'm_2', 'm_1', 'm_0']
            ]
        ]
    ];
    
    $testCases = [
        [5, 10, 20, 30],
        [1, 5, 5, 5],
        [10, 50, 60, 70],
        [-10, 1, 1, 1],
        [0, 0, 0, 0],
        [2, 15, 25, 35]
    ];
    
    foreach ($testCases as $test) {
        [$i, $m_2, $m_1, $m_0] = $test;
        
        $inputs = ['i' => $i, 'm_2' => $m_2, 'm_1' => $m_1, 'm_0' => $m_0];
        
        // คำนวณด้วย RuleFlow
        $ruleFlowResult = $ruleFlow->evaluate($config, $inputs);
        $ruleFlowValue = $ruleFlowResult['formula_result'];
        
        // คำนวณแบบเดิม
        $originalValue = originalCalculation($i, $m_2, $m_1, $m_0);
        
        // เปรียบเทียบ
        $diff = abs($ruleFlowValue - $originalValue);
        $match = $diff < 0.0001;
        $status = $match ? "✅ ตรงกัน" : "❌ ไม่ตรงกัน (ต่าง: $diff)";
        
        echo "Input: i=$i, m_2=$m_2, m_1=$m_1, m_0=$m_0\n";
        echo "  RuleFlow: " . number_format($ruleFlowValue, 4) . "\n";
        echo "  Original: " . number_format($originalValue, 4) . "\n";
        echo "  $status\n\n";
    }
}

/**
 * เทสแบบ unit testing
 */
function unitTests() {
    echo "=== Unit Tests ===\n\n";
    
    $ruleFlow = new RuleFlow();
    $config = [
        'formulas' => [
            [
                'id' => 'test_result',
                'formula' => 'max(0, min(100, (i+3)*((((m_2*1)+(m_1*2)+(m_0*3))-(((m_2+m_1+m_0)/3)*(6)))/2) + (((m_2+m_1+m_0)/3)-((((m_2*1)+(m_1*2)+(m_0*3))-(((m_2+m_1+m_0)/3)*(6)))/2)*2)))',
                'inputs' => ['i', 'm_2', 'm_1', 'm_0']
            ]
        ]
    ];
    
    $tests = [
        [
            'name' => 'edge_case_zeros',
            'input' => ['i' => 0, 'm_2' => 0, 'm_1' => 0, 'm_0' => 0],
            'expected' => 0
        ],
        [
            'name' => 'positive_normal',
            'input' => ['i' => 5, 'm_2' => 10, 'm_1' => 20, 'm_0' => 30],
            'expected' => 80
        ],
        [
            'name' => 'negative_i',
            'input' => ['i' => -5, 'm_2' => 10, 'm_1' => 20, 'm_0' => 30],
            'expected' => 0 // ควรถูกจำกัดที่ 0
        ],
        [
            'name' => 'high_values',
            'input' => ['i' => 100, 'm_2' => 100, 'm_1' => 100, 'm_0' => 100],
            'expected' => 100 // ควรถูกจำกัดที่ 100
        ]
    ];
    
    $passedTests = 0;
    $totalTests = count($tests);
    
    foreach ($tests as $test) {
        echo "🧪 Test: " . $test['name'] . "\n";
        
        try {
            $result = $ruleFlow->evaluate($config, $test['input']);
            $actual = $result['test_result'];
            $expected = $test['expected'];
            
            if (abs($actual - $expected) < 0.1) {
                echo "  ✅ PASS - Expected: $expected, Got: " . number_format($actual, 2) . "\n";
                $passedTests++;
            } else {
                echo "  ❌ FAIL - Expected: $expected, Got: " . number_format($actual, 2) . "\n";
            }
            
        } catch (Exception $e) {
            echo "  ❌ ERROR: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "📊 ผลสรุป: $passedTests/$totalTests tests passed (" . 
         number_format(($passedTests/$totalTests)*100, 1) . "%)\n\n";
}

/**
 * เทส configuration validation
 */
function testConfigValidation() {
    echo "=== เทส Configuration Validation ===\n\n";
    
    $ruleFlow = new RuleFlow();
    
    // เช็คว่ามี validateConfig method ไหม
    if (method_exists($ruleFlow, 'validateConfig')) {
        $config = [
            'formulas' => [
                [
                    'id' => 'test',
                    'formula' => 'max(0, min(100, (i+3)*((((m_2*1)+(m_1*2)+(m_0*3))-(((m_2+m_1+m_0)/3)*(6)))/2) + (((m_2+m_1+m_0)/3)-((((m_2*1)+(m_1*2)+(m_0*3))-(((m_2+m_1+m_0)/3)*(6)))/2)*2)))',
                    'inputs' => ['i', 'm_2', 'm_1', 'm_0']
                ]
            ]
        ];
        
        $validation = $ruleFlow->validateConfig($config);
        
        if ($validation['valid']) {
            echo "✅ Configuration ถูกต้อง\n";
            if (!empty($validation['warnings'])) {
                echo "⚠️ คำเตือน:\n";
                foreach ($validation['warnings'] as $warning) {
                    echo "  - $warning\n";
                }
            }
        } else {
            echo "❌ Configuration ผิด:\n";
            foreach ($validation['errors'] as $error) {
                echo "  - $error\n";
            }
        }
    } else {
        echo "⚠️ validateConfig method ไม่มีใน version นี้\n";
    }
    
    echo "\n";
}

// รันเทสทั้งหมด
testFormulaWithRuleFlow();
compareResults();
unitTests();
testConfigValidation();

echo "✅ เทสทั้งหมดเสร็จสิ้น!\n";

?>