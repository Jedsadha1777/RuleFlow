<?php

require_once __DIR__ . '/../src/RuleFlow.php';


echo "=== Loan Payment Calculator ===\n\n";

$ruleFlow = new RuleFlow();

// กำหนด Configuration
$loanConfig = [
    "formulas" => [
        [
            "id" => "monthly_rate",
            "formula" => "annual_rate / 12 / 100",
            "inputs" => ["annual_rate"],
            "as" => '$rate'
        ],
        [
            "id" => "monthly_payment",
            "formula" => 'principal * ($rate * ((1 + $rate) ** months)) / (((1 + $rate) ** months) - 1)',
            "inputs" => ["principal", '$rate', "months"]  
        ]
    ]
];

// Test Case 1: เงินกู้ซื้อบ้าน
echo "🏠 Test 1: Home Loan\n";
echo "-------------------\n";
$inputs1 = [
    'principal' => 3000000,    // เงินต้น 3 ล้าน
    'annual_rate' => 5.5,      // ดอกเบี้ย 5.5% ต่อปี
    'months' => 360            // 30 ปี (360 เดือน)
];

try {
    $result1 = $ruleFlow->evaluate($loanConfig, $inputs1);
    
    echo "Principal: " . number_format($inputs1['principal']) . " บาท\n";
    echo "Annual Rate: {$inputs1['annual_rate']}%\n";
    echo "Loan Term: " . ($inputs1['months']/12) . " ปี\n";
    // echo "Monthly Rate: " . round($result1['rate'] * 100, 4) . "%\n";
    echo "Monthly Payment: " . number_format($result1['monthly_payment'], 2) . " บาท\n";
    echo "Total Payment: " . number_format($result1['monthly_payment'] * $inputs1['months'], 2) . " บาท\n";
    echo "Total Interest: " . number_format(($result1['monthly_payment'] * $inputs1['months']) - $inputs1['principal'], 2) . " บาท\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Test Case 2: เงินกู้รถยนต์
echo "🚗 Test 2: Car Loan\n";
echo "-------------------\n";
$inputs2 = [
    'principal' => 800000,     // เงินต้น 800,000 บาท
    'annual_rate' => 7.2,      // ดอกเบี้ย 7.2% ต่อปี
    'months' => 60             // 5 ปี (60 เดือน)
];

try {
    $result2 = $ruleFlow->evaluate($loanConfig, $inputs2);
    
    echo "Principal: " . number_format($inputs2['principal']) . " บาท\n";
    echo "Annual Rate: {$inputs2['annual_rate']}%\n";
    echo "Loan Term: " . ($inputs2['months']/12) . " ปี\n";
    echo "Monthly Rate: " . round($result2['rate'] * 100, 4) . "%\n";
    echo "Monthly Payment: " . number_format($result2['monthly_payment'], 2) . " บาท\n";
    echo "Total Payment: " . number_format($result2['monthly_payment'] * $inputs2['months'], 2) . " บาท\n";
    echo "Total Interest: " . number_format(($result2['monthly_payment'] * $inputs2['months']) - $inputs2['principal'], 2) . " บาท\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Test Case 3: เงินกู้ส่วนบุคคล
echo "💰 Test 3: Personal Loan\n";
echo "------------------------\n";
$inputs3 = [
    'principal' => 500000,     // เงินต้น 500,000 บาท
    'annual_rate' => 12.0,     // ดอกเบี้ย 12% ต่อปี
    'months' => 36             // 3 ปี (36 เดือน)
];

try {
    $result3 = $ruleFlow->evaluate($loanConfig, $inputs3);
    
    echo "Principal: " . number_format($inputs3['principal']) . " บาท\n";
    echo "Annual Rate: {$inputs3['annual_rate']}%\n";
    echo "Loan Term: " . ($inputs3['months']/12) . " ปี\n";
    echo "Monthly Rate: " . round($result3['rate'] * 100, 4) . "%\n";
    echo "Monthly Payment: " . number_format($result3['monthly_payment'], 2) . " บาท\n";
    echo "Total Payment: " . number_format($result3['monthly_payment'] * $inputs3['months'], 2) . " บาท\n";
    echo "Total Interest: " . number_format(($result3['monthly_payment'] * $inputs3['months']) - $inputs3['principal'], 2) . " บาท\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// สูตรการคำนวณ
echo "📐 Formula Explanation:\n";
echo "======================\n";
echo "Monthly Rate = Annual Rate ÷ 12 ÷ 100\n";
echo "Monthly Payment = P × [r × (1 + r)^n] ÷ [(1 + r)^n - 1]\n";
echo "Where:\n";
echo "  P = Principal (เงินต้น)\n";
echo "  r = Monthly interest rate (อัตราดอกเบี้ยรายเดือน)\n";
echo "  n = Number of months (จำนวนเดือน)\n\n";

echo "✅ All loan calculations completed!\n";

?>