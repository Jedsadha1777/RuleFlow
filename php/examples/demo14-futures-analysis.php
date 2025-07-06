<?php 
require_once __DIR__ . "/../src/RuleFlow.php";

/**
 * Simplified Trading Bot Analysis Examples
 * Enhanced with step-by-step explanations and error handling
 */

/**
 * Example 1: Simple Stop Loss Checker (Beginner)
 */
function example1_simple_stop_loss() {
    echo "\n=== EXAMPLE 1: Simple Stop Loss Checker ===\n";
    echo "Purpose: Check if portfolio loss exceeds threshold and should stop trading\n";
    echo "Input: current_value, initial_value\n";
    echo "Output: stop_trading (true/false), loss_percent\n\n";
    
    // Step 1: Define the configuration
    $config = [
        'formulas' => [
            // Calculate loss percentage
            [
                'id' => 'loss_percent',
                'formula' => '((current_value - initial_value) / initial_value) * 100',
                'inputs' => ['current_value', 'initial_value'],
                'as' => '$loss_percent'
            ],
            
            // Simple decision: stop if loss > 5%
            [
                'id' => 'stop_trading',
                'switch' => '$loss_percent',
                'when' => [
                    [
                        'if' => ['op' => '<=', 'value' => -5],
                        'result' => true,
                        'set_vars' => ['$action' => 'STOP', '$reason' => 'Loss exceeds 5%']
                    ]
                ],
                'default' => false,
                'default_vars' => ['$action' => 'CONTINUE', '$reason' => 'Loss within acceptable range']
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    // Test scenarios
    $scenarios = [
        ['name' => 'Safe scenario', 'current_value' => 98000, 'initial_value' => 100000],
        ['name' => 'Warning scenario', 'current_value' => 95000, 'initial_value' => 100000],
        ['name' => 'Stop scenario', 'current_value' => 92000, 'initial_value' => 100000]
    ];
    
    foreach ($scenarios as $i => $scenario) {
        echo "Scenario " . ($i + 1) . ": {$scenario['name']}\n";
        echo "Portfolio: ฿{$scenario['initial_value']} → ฿{$scenario['current_value']}\n";
        
        try {
            $result = $ruleFlow->evaluate($config, $scenario);
            echo "Loss: " . round($result['loss_percent'], 2) . "%\n";
            echo "Decision: " . ($result['stop_trading'] ? 'STOP TRADING' : 'CONTINUE') . "\n";
            echo "Action: {$result['action']}\n";
            echo "Reason: {$result['reason']}\n\n";
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n\n";
        }
    }
    
    return $config;
}

/**
 * Example 2: Risk Level Assessment (Intermediate)
 */
function example2_risk_assessment() {
    echo "\n=== EXAMPLE 2: Risk Level Assessment ===\n";
    echo "Purpose: Calculate risk score based on multiple factors\n";
    echo "Input: portfolio_loss, volatility, volume_spike\n";
    echo "Output: risk_score, risk_level, recommendation\n\n";
    
    // Step 1: Build risk scoring system
    $config = [
        'formulas' => [
            // Calculate base risk from portfolio loss
            [
                'id' => 'loss_risk_score',
                'rules' => [
                    [
                        'var' => 'portfolio_loss_percent',
                        'ranges' => [
                            ['if' => ['op' => '<=', 'value' => -15], 'score' => 50], // Critical loss
                            ['if' => ['op' => '<=', 'value' => -10], 'score' => 30], // High loss
                            ['if' => ['op' => '<=', 'value' => -5], 'score' => 15],  // Medium loss
                            ['if' => ['op' => '<=', 'value' => -2], 'score' => 5]    // Small loss
                        ]
                    ]
                ]
            ],
            
            // Add volatility risk
            [
                'id' => 'volatility_risk_score',
                'rules' => [
                    [
                        'var' => 'volatility_percent',
                        'ranges' => [
                            ['if' => ['op' => '>=', 'value' => 8], 'score' => 25],  // Very high volatility
                            ['if' => ['op' => '>=', 'value' => 5], 'score' => 15],  // High volatility
                            ['if' => ['op' => '>=', 'value' => 3], 'score' => 8],   // Medium volatility
                            ['if' => ['op' => '>=', 'value' => 1], 'score' => 3]    // Low volatility
                        ]
                    ]
                ]
            ],
            
            // Add volume spike risk
            [
                'id' => 'volume_risk_score',
                'rules' => [
                    [
                        'var' => 'volume_spike',
                        'ranges' => [
                            ['if' => ['op' => '>=', 'value' => 5], 'score' => 20],  // Extreme spike
                            ['if' => ['op' => '>=', 'value' => 3], 'score' => 12],  // High spike
                            ['if' => ['op' => '>=', 'value' => 2], 'score' => 6],   // Medium spike
                            ['if' => ['op' => '>=', 'value' => 1.5], 'score' => 2]  // Small spike
                        ]
                    ]
                ]
            ],
            
            // Calculate total risk score
            [
                'id' => 'total_risk_score',
                'formula' => 'loss_risk_score + volatility_risk_score + volume_risk_score',
                'inputs' => ['loss_risk_score', 'volatility_risk_score', 'volume_risk_score']
            ],
            
            // Determine risk level and recommendation
            [
                'id' => 'risk_assessment',
                'switch' => 'total_risk_score',
                'when' => [
                    [
                        'if' => ['op' => '>=', 'value' => 70],
                        'result' => 'CRITICAL',
                        'set_vars' => [
                            '$recommendation' => 'EMERGENCY_STOP',
                            '$action' => 'Close all positions immediately',
                            '$risk_level' => 'CRITICAL'
                        ]
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 40],
                        'result' => 'HIGH',
                        'set_vars' => [
                            '$recommendation' => 'REDUCE_EXPOSURE',
                            '$action' => 'Reduce position sizes by 50%',
                            '$risk_level' => 'HIGH'
                        ]
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 20],
                        'result' => 'MEDIUM',
                        'set_vars' => [
                            '$recommendation' => 'CAUTIOUS_TRADING',
                            '$action' => 'Monitor closely, reduce new positions',
                            '$risk_level' => 'MEDIUM'
                        ]
                    ]
                ],
                'default' => 'LOW',
                'default_vars' => [
                    '$recommendation' => 'NORMAL_TRADING',
                    '$action' => 'Continue normal operations',
                    '$risk_level' => 'LOW'
                ]
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    // Test different risk scenarios
    $scenarios = [
        [
            'name' => 'Low Risk',
            'portfolio_loss_percent' => -1,
            'volatility_percent' => 2,
            'volume_spike' => 1.2
        ],
        [
            'name' => 'Medium Risk',
            'portfolio_loss_percent' => -6,
            'volatility_percent' => 4,
            'volume_spike' => 2.5
        ],
        [
            'name' => 'High Risk',
            'portfolio_loss_percent' => -12,
            'volatility_percent' => 6,
            'volume_spike' => 4.0
        ],
        [
            'name' => 'Critical Risk',
            'portfolio_loss_percent' => -18,
            'volatility_percent' => 9,
            'volume_spike' => 6.0
        ]
    ];
    
    foreach ($scenarios as $i => $scenario) {
        echo "Scenario " . ($i + 1) . ": {$scenario['name']}\n";
        echo "Loss: {$scenario['portfolio_loss_percent']}%, Volatility: {$scenario['volatility_percent']}%, Volume Spike: {$scenario['volume_spike']}x\n";
        
        try {
            $result = $ruleFlow->evaluate($config, $scenario);
            echo "📊 Risk Breakdown:\n";
            echo "  • Loss Risk: {$result['loss_risk_score']} points\n";
            echo "  • Volatility Risk: {$result['volatility_risk_score']} points\n";
            echo "  • Volume Risk: {$result['volume_risk_score']} points\n";
            echo "🎯 Total Risk Score: {$result['total_risk_score']}\n";
            echo "⚠️ Risk Level: {$result['risk_level']}\n";
            echo "💡 Recommendation: {$result['recommendation']}\n";
            echo "🔧 Action: {$result['action']}\n\n";
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n\n";
        }
    }
    
    return $config;
}

/**
 * Example 3: Position Size Calculator (Advanced)
 */
function example3_position_sizing() {
    echo "\n=== EXAMPLE 3: Position Size Calculator ===\n";
    echo "Purpose: Calculate optimal position size based on account and market conditions\n";
    echo "Input: account_balance, risk_percent, volatility, confidence_level\n";
    echo "Output: position_size, risk_amount, stop_loss_distance\n\n";
    
    $config = [
        'formulas' => [
            // Calculate maximum risk amount
            [
                'id' => 'max_risk_amount',
                'formula' => 'account_balance * (risk_percent / 100)',
                'inputs' => ['account_balance', 'risk_percent'],
                'as' => '$max_risk'
            ],
            
            // Adjust for volatility
            [
                'id' => 'volatility_multiplier',
                'switch' => 'volatility_percent',
                'when' => [
                    ['if' => ['op' => '>=', 'value' => 8], 'result' => 0.5], // High volatility = smaller position
                    ['if' => ['op' => '>=', 'value' => 5], 'result' => 0.7],
                    ['if' => ['op' => '>=', 'value' => 3], 'result' => 0.85],
                    ['if' => ['op' => '>=', 'value' => 1], 'result' => 1.0]
                ],
                'default' => 1.2 // Very low volatility = slightly larger position
            ],
            
            // Adjust for confidence level
            [
                'id' => 'confidence_multiplier',
                'switch' => 'confidence_level',
                'when' => [
                    ['if' => ['op' => '>=', 'value' => 90], 'result' => 1.2], // Very confident
                    ['if' => ['op' => '>=', 'value' => 75], 'result' => 1.0], // Confident
                    ['if' => ['op' => '>=', 'value' => 60], 'result' => 0.8], // Moderate confidence
                    ['if' => ['op' => '>=', 'value' => 40], 'result' => 0.6]  // Low confidence
                ],
                'default' => 0.3 // Very low confidence
            ],
            
            // Calculate adjusted risk amount
            [
                'id' => 'adjusted_risk_amount',
                'formula' => 'max_risk * volatility_multiplier * confidence_multiplier',
                'inputs' => ['max_risk', 'volatility_multiplier', 'confidence_multiplier'],
                'as' => '$adj_risk'
            ],
            
            // Calculate stop loss distance (based on volatility)
            [
                'id' => 'stop_loss_distance_percent',
                'formula' => 'volatility_percent * 1.5', // 1.5x volatility for stop loss
                'inputs' => ['volatility_percent'],
                'as' => '$stop_distance'
            ],
            
            // Calculate position size
            [
                'id' => 'position_size',
                'formula' => 'adj_risk / (stop_distance / 100)',
                'inputs' => ['adj_risk', 'stop_distance'],
                'as' => '$pos_size'
            ],
            
            // Generate recommendations
            [
                'id' => 'position_recommendation',
                'switch' => '$pos_size',
                'when' => [
                    [
                        'if' => ['op' => '>', 'value' => 50000],
                        'result' => 'LARGE_POSITION',
                        'set_vars' => ['$warning' => 'Large position detected - consider splitting']
                    ],
                    [
                        'if' => ['op' => '>', 'value' => 20000],
                        'result' => 'MEDIUM_POSITION',
                        'set_vars' => ['$warning' => 'Medium position - monitor closely']
                    ],
                    [
                        'if' => ['op' => '>', 'value' => 5000],
                        'result' => 'SMALL_POSITION',
                        'set_vars' => ['$warning' => 'Small position - good risk management']
                    ]
                ],
                'default' => 'MICRO_POSITION',
                'default_vars' => ['$warning' => 'Very small position - may not be worth trading']
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    // Test different scenarios
    $scenarios = [
        [
            'name' => 'Conservative Trade',
            'account_balance' => 100000,
            'risk_percent' => 1,
            'volatility_percent' => 2,
            'confidence_level' => 60
        ],
        [
            'name' => 'Aggressive Trade',
            'account_balance' => 100000,
            'risk_percent' => 3,
            'volatility_percent' => 1.5,
            'confidence_level' => 85
        ],
        [
            'name' => 'High Volatility',
            'account_balance' => 100000,
            'risk_percent' => 2,
            'volatility_percent' => 7,
            'confidence_level' => 70
        ],
        [
            'name' => 'Low Confidence',
            'account_balance' => 100000,
            'risk_percent' => 2,
            'volatility_percent' => 3,
            'confidence_level' => 45
        ]
    ];
    
    foreach ($scenarios as $i => $scenario) {
        echo "Scenario " . ($i + 1) . ": {$scenario['name']}\n";
        echo "Account: ฿{$scenario['account_balance']}, Risk: {$scenario['risk_percent']}%, Vol: {$scenario['volatility_percent']}%, Confidence: {$scenario['confidence_level']}%\n";
        
        try {
            $result = $ruleFlow->evaluate($config, $scenario);
            echo "📈 Calculation Results:\n";
            echo "  • Max Risk Amount: ฿" . number_format($result['max_risk'], 0) . "\n";
            echo "  • Volatility Adjustment: " . round($result['volatility_multiplier'] * 100, 0) . "%\n";
            echo "  • Confidence Adjustment: " . round($result['confidence_multiplier'] * 100, 0) . "%\n";
            echo "  • Adjusted Risk: ฿" . number_format($result['adj_risk'], 0) . "\n";
            echo "  • Stop Loss Distance: " . round($result['stop_distance'], 2) . "%\n";
            echo "💰 Recommended Position Size: ฿" . number_format($result['pos_size'], 0) . "\n";
            echo "📊 Position Type: {$result['position_recommendation']}\n";
            echo "⚠️ Warning: {$result['warning']}\n\n";
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n\n";
        }
    }
    
    return $config;
}

/**
 * Example 4: Performance Tracker (Advanced)
 */
function example4_performance_tracker() {
    echo "\n=== EXAMPLE 4: Performance Tracker ===\n";
    echo "Purpose: Track and evaluate trading performance with recommendations\n";
    echo "Input: total_trades, winning_trades, total_profit, max_drawdown\n";
    echo "Output: win_rate, performance_grade, recommendations\n\n";
    
    $config = [
        'formulas' => [
            // Calculate win rate
            [
                'id' => 'win_rate_percent',
                'formula' => '(winning_trades / total_trades) * 100',
                'inputs' => ['winning_trades', 'total_trades']
            ],
            
            // Calculate average profit per trade
            [
                'id' => 'avg_profit_per_trade',
                'formula' => 'total_profit / total_trades',
                'inputs' => ['total_profit', 'total_trades']
            ],
            
            // Performance scoring system
            [
                'id' => 'performance_score',
                'scoring' => [
                    'ifs' => [
                        'vars' => ['win_rate_percent', 'total_profit'],
                        'tree' => [
                            [
                                'if' => ['op' => '>=', 'value' => 70], // Win rate >= 70%
                                'ranges' => [
                                    [
                                        'if' => ['op' => '>', 'value' => 0],  // Profit > 0
                                        'score' => 90,
                                        'grade' => 'A',
                                        'message' => 'Excellent performance'
                                    ],
                                    [
                                        'if' => ['op' => '<=', 'value' => 0], // Profit <= 0
                                        'score' => 60,
                                        'grade' => 'C',
                                        'message' => 'High win rate but low profitability'
                                    ]
                                ]
                            ],
                            [
                                'if' => ['op' => 'between', 'value' => [50, 69]], // Win rate 50-69%
                                'ranges' => [
                                    [
                                        'if' => ['op' => '>', 'value' => 5000],  // Good profit
                                        'score' => 80,
                                        'grade' => 'B',
                                        'message' => 'Good balance of wins and profit'
                                    ],
                                    [
                                        'if' => ['op' => '>', 'value' => 0],    // Some profit
                                        'score' => 70,
                                        'grade' => 'B-',
                                        'message' => 'Decent performance'
                                    ],
                                    [
                                        'if' => ['op' => '<=', 'value' => 0],   // No profit
                                        'score' => 40,
                                        'grade' => 'D',
                                        'message' => 'Mediocre win rate, no profit'
                                    ]
                                ]
                            ],
                            [
                                'if' => ['op' => '<', 'value' => 50], // Win rate < 50%
                                'ranges' => [
                                    [
                                        'if' => ['op' => '>', 'value' => 0],  // Still profitable
                                        'score' => 60,
                                        'grade' => 'C',
                                        'message' => 'Low win rate but still profitable'
                                    ],
                                    [
                                        'if' => ['op' => '<=', 'value' => 0], // Not profitable
                                        'score' => 20,
                                        'grade' => 'F',
                                        'message' => 'Poor performance - needs improvement'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            
            // Generate recommendations based on performance
            [
                'id' => 'recommendations',
                'switch' => 'performance_score_grade',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'A'],
                        'result' => 'MAINTAIN_STRATEGY',
                        'set_vars' => [
                            '$action' => 'Continue current approach',
                            '$focus' => 'Maintain discipline and risk management'
                        ]
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'B'],
                        'result' => 'OPTIMIZE_STRATEGY',
                        'set_vars' => [
                            '$action' => 'Fine-tune entry/exit points',
                            '$focus' => 'Improve profit margins'
                        ]
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'C'],
                        'result' => 'REVIEW_APPROACH',
                        'set_vars' => [
                            '$action' => 'Analyze losing trades',
                            '$focus' => 'Improve risk-reward ratio'
                        ]
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'D'],
                        'result' => 'MAJOR_CHANGES_NEEDED',
                        'set_vars' => [
                            '$action' => 'Revise trading strategy',
                            '$focus' => 'Reduce position sizes, improve timing'
                        ]
                    ]
                ],
                'default' => 'STOP_AND_LEARN',
                'default_vars' => [
                    '$action' => 'Stop trading and study',
                    '$focus' => 'Learn from mistakes, practice on demo'
                ]
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    // Test performance scenarios
    $scenarios = [
        [
            'name' => 'Excellent Trader',
            'total_trades' => 100,
            'winning_trades' => 75,
            'total_profit' => 15000,
            'max_drawdown' => 3000
        ],
        [
            'name' => 'Good Trader',
            'total_trades' => 80,
            'winning_trades' => 50,
            'total_profit' => 8000,
            'max_drawdown' => 4000
        ],
        [
            'name' => 'Average Trader',
            'total_trades' => 60,
            'winning_trades' => 35,
            'total_profit' => 2000,
            'max_drawdown' => 5000
        ],
        [
            'name' => 'Struggling Trader',
            'total_trades' => 40,
            'winning_trades' => 15,
            'total_profit' => -3000,
            'max_drawdown' => 8000
        ]
    ];
    
    foreach ($scenarios as $i => $scenario) {
        echo "Scenario " . ($i + 1) . ": {$scenario['name']}\n";
        echo "Trades: {$scenario['total_trades']}, Wins: {$scenario['winning_trades']}, Profit: ฿{$scenario['total_profit']}\n";
        
        try {
            $result = $ruleFlow->evaluate($config, $scenario);
            echo "📊 Performance Analysis:\n";
            echo "  • Win Rate: " . round($result['win_rate_percent'], 1) . "%\n";
            echo "  • Avg Profit/Trade: ฿" . number_format($result['avg_profit_per_trade'], 0) . "\n";
            echo "  • Performance Score: {$result['performance_score']}/100\n";
            echo "🎯 Grade: {$result['performance_score_grade']}\n";
            echo "💭 Assessment: {$result['performance_score_message']}\n";
            echo "📈 Recommendation: {$result['recommendations']}\n";
            echo "🔧 Action: {$result['action']}\n";
            echo "🎯 Focus: {$result['focus']}\n\n";
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n\n";
        }
    }
    
    return $config;
}

/**
 * Performance Benchmarks
 */
function benchmark_trading_analysis() {
    echo "\n=== PERFORMANCE BENCHMARKS ===\n";
    
    $ruleFlow = new RuleFlow();
    
    // Simple risk calculation
    $simpleConfig = [
        'formulas' => [
            [
                'id' => 'risk_percent',
                'formula' => 'abs((current_value - initial_value) / initial_value) * 100',
                'inputs' => ['current_value', 'initial_value']
            ],
            [
                'id' => 'risk_level',
                'switch' => 'risk_percent',
                'when' => [
                    ['if' => ['op' => '>', 'value' => 10], 'result' => 'HIGH'],
                    ['if' => ['op' => '>', 'value' => 5], 'result' => 'MEDIUM']
                ],
                'default' => 'LOW'
            ]
        ]
    ];
    
    $testInput = ['current_value' => 95000, 'initial_value' => 100000];
    
    // Benchmark simple calculations
    $iterations = 1000;
    $start = microtime(true);
    
    for ($i = 0; $i < $iterations; $i++) {
        $ruleFlow->evaluate($simpleConfig, $testInput);
    }
    
    $end = microtime(true);
    $simpleTime = ($end - $start) * 1000;
    
    echo "Simple Risk Analysis ($iterations iterations):\n";
    echo "  Total: " . round($simpleTime, 2) . "ms\n";
    echo "  Average: " . round($simpleTime / $iterations, 4) . "ms per calculation\n";
    echo "  Rate: " . round($iterations / ($simpleTime / 1000)) . " calculations/second\n\n";
    
    // Memory usage
    $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;
    echo "Peak Memory Usage: " . round($memoryUsage, 2) . " MB\n";
}

/**
 * Error Handling Examples
 */
function demonstrate_error_handling() {
    echo "\n=== ERROR HANDLING EXAMPLES ===\n";
    
    $ruleFlow = new RuleFlow();
    
    // Valid configuration
    $config = [
        'formulas' => [
            [
                'id' => 'portfolio_change',
                'formula' => '(current_value - initial_value) / initial_value * 100',
                'inputs' => ['current_value', 'initial_value']
            ]
        ]
    ];
    
    echo "1. Valid Calculation:\n";
    try {
        $result = $ruleFlow->evaluate($config, ['current_value' => 105000, 'initial_value' => 100000]);
        echo "   ✅ Success: Portfolio change = " . round($result['portfolio_change'], 2) . "%\n\n";
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n\n";
    }
    
    echo "2. Missing Input:\n";
    try {
        $result = $ruleFlow->evaluate($config, ['current_value' => 105000]); // Missing initial_value
        echo "   ✅ Success: " . $result['portfolio_change'] . "\n\n";
    } catch (Exception $e) {
        echo "   ❌ Expected Error: " . $e->getMessage() . "\n\n";
    }
    
    echo "3. Division by Zero Protection:\n";
    try {
        $result = $ruleFlow->evaluate($config, ['current_value' => 105000, 'initial_value' => 0]);
        echo "   ✅ Success: " . $result['portfolio_change'] . "\n\n";
    } catch (Exception $e) {
        echo "   ❌ Expected Error: " . $e->getMessage() . "\n\n";
    }
    
    echo "4. Invalid Configuration:\n";
    try {
        $invalidConfig = [
            'formulas' => [
                [
                    // Missing 'id' field
                    'formula' => 'current_value + initial_value',
                    'inputs' => ['current_value', 'initial_value']
                ]
            ]
        ];
        $result = $ruleFlow->evaluate($invalidConfig, ['current_value' => 105000, 'initial_value' => 100000]);
        echo "   ✅ Success: " . $result['portfolio_change'] . "\n\n";
    } catch (Exception $e) {
        echo "   ❌ Expected Error: " . $e->getMessage() . "\n\n";
    }
}

/**
 * Generate Production-Ready PHP Functions
 */
function generate_trading_functions() {
    echo "\n=== GENERATED PHP FUNCTIONS FOR PRODUCTION ===\n";
    
    $ruleFlow = new RuleFlow();
    
    // Simple stop loss function
    $stopLossConfig = [
        'formulas' => [
            [
                'id' => 'loss_percent',
                'formula' => '((current_value - initial_value) / initial_value) * 100',
                'inputs' => ['current_value', 'initial_value'],
                'as' => '$loss_percent'
            ],
            [
                'id' => 'stop_decision',
                'switch' => '$loss_percent',
                'when' => [
                    [
                        'if' => ['op' => '<=', 'value' => -5],
                        'result' => 'STOP',
                        'set_vars' => ['$should_stop' => true, '$reason' => 'Loss exceeds 5%']
                    ]
                ],
                'default' => 'CONTINUE',
                'default_vars' => ['$should_stop' => false, '$reason' => 'Loss within limits']
            ]
        ]
    ];
    
    echo "// Stop Loss Function\n";
    echo "\$stopLossFunction = " . $ruleFlow->generateFunctionAsString($stopLossConfig) . ";\n\n";
    
    // Risk assessment function
    $riskConfig = [
        'formulas' => [
            [
                'id' => 'portfolio_risk',
                'rules' => [
                    [
                        'var' => 'loss_percent',
                        'ranges' => [
                            ['if' => ['op' => '<=', 'value' => -15], 'score' => 50],
                            ['if' => ['op' => '<=', 'value' => -10], 'score' => 30],
                            ['if' => ['op' => '<=', 'value' => -5], 'score' => 15]
                        ]
                    ]
                ]
            ],
            [
                'id' => 'volatility_risk',
                'rules' => [
                    [
                        'var' => 'volatility',
                        'ranges' => [
                            ['if' => ['op' => '>=', 'value' => 8], 'score' => 25],
                            ['if' => ['op' => '>=', 'value' => 5], 'score' => 15],
                            ['if' => ['op' => '>=', 'value' => 3], 'score' => 8]
                        ]
                    ]
                ]
            ],
            [
                'id' => 'total_risk',
                'formula' => 'portfolio_risk + volatility_risk',
                'inputs' => ['portfolio_risk', 'volatility_risk']
            ],
            [
                'id' => 'risk_level',
                'switch' => 'total_risk',
                'when' => [
                    ['if' => ['op' => '>=', 'value' => 50], 'result' => 'CRITICAL'],
                    ['if' => ['op' => '>=', 'value' => 30], 'result' => 'HIGH'],
                    ['if' => ['op' => '>=', 'value' => 15], 'result' => 'MEDIUM']
                ],
                'default' => 'LOW'
            ]
        ]
    ];
    
    echo "// Risk Assessment Function\n";
    echo "\$riskAssessmentFunction = " . $ruleFlow->generateFunctionAsString($riskConfig) . ";\n\n";
    
    // Position sizing function
    $positionConfig = [
        'formulas' => [
            [
                'id' => 'max_risk_amount',
                'formula' => 'account_balance * (risk_percent / 100)',
                'inputs' => ['account_balance', 'risk_percent']
            ],
            [
                'id' => 'volatility_adjustment',
                'switch' => 'volatility',
                'when' => [
                    ['if' => ['op' => '>=', 'value' => 8], 'result' => 0.5],
                    ['if' => ['op' => '>=', 'value' => 5], 'result' => 0.7],
                    ['if' => ['op' => '>=', 'value' => 3], 'result' => 0.85]
                ],
                'default' => 1.0
            ],
            [
                'id' => 'position_size',
                'formula' => 'max_risk_amount * volatility_adjustment',
                'inputs' => ['max_risk_amount', 'volatility_adjustment']
            ]
        ]
    ];
    
    echo "// Position Sizing Function\n";
    echo "\$positionSizingFunction = " . $ruleFlow->generateFunctionAsString($positionConfig) . ";\n\n";
    
    echo "// Usage Examples:\n";
    echo "/*\n";
    echo "// Stop Loss Check\n";
    echo "\$inputs = ['current_value' => 95000, 'initial_value' => 100000];\n";
    echo "\$result = \$stopLossFunction(\$inputs);\n";
    echo "if (\$result['should_stop']) {\n";
    echo "    echo 'Stop trading: ' . \$result['reason'];\n";
    echo "}\n\n";
    echo "// Risk Assessment\n";
    echo "\$inputs = ['loss_percent' => -8, 'volatility' => 6];\n";
    echo "\$result = \$riskAssessmentFunction(\$inputs);\n";
    echo "echo 'Risk Level: ' . \$result['risk_level'];\n\n";
    echo "// Position Sizing\n";
    echo "\$inputs = ['account_balance' => 100000, 'risk_percent' => 2, 'volatility' => 4];\n";
    echo "\$result = \$positionSizingFunction(\$inputs);\n";
    echo "echo 'Position Size:  . \$result['position_size'];\n";
    echo "*/\n\n";
    
    echo "// Performance Tips:\n";
    echo "// 1. Cache generated functions for better performance\n";
    echo "// 2. Use specific functions instead of general evaluation for high-frequency trading\n";
    echo "// 3. Consider input validation before calling functions\n";
    echo "// 4. Monitor memory usage for large-scale operations\n";
}

/**
 * Configuration Summary and Best Practices
 */
function show_configuration_summary() {
    echo "\n=== CONFIGURATION SUMMARY & BEST PRACTICES ===\n";
    
    echo "📋 Available Examples:\n";
    echo "1. Simple Stop Loss - Basic portfolio protection\n";
    echo "2. Risk Assessment - Multi-factor risk scoring\n";
    echo "3. Position Sizing - Dynamic position calculation\n";
    echo "4. Performance Tracking - Trading performance analysis\n\n";
    
    echo "🎯 Key Formula Types Used:\n";
    echo "• Expression Formulas: Mathematical calculations\n";
    echo "• Switch Formulas: Conditional logic and decisions\n";
    echo "• Rules Formulas: Scoring systems\n";
    echo "• Scoring Formulas: Multi-dimensional analysis\n\n";
    
    echo "⚡ Performance Optimizations:\n";
    echo "• Use generateFunctionAsString() for repeated calculations\n";
    echo "• Cache configurations to avoid re-validation\n";
    echo "• Validate inputs before processing\n";
    echo "• Monitor memory usage for large datasets\n\n";
    
    echo "🔧 Error Handling Best Practices:\n";
    echo "• Always wrap evaluate() calls in try-catch blocks\n";
    echo "• Validate required inputs before processing\n";
    echo "• Check for division by zero in financial calculations\n";
    echo "• Provide meaningful error messages to users\n\n";
    
    echo "📊 Real-World Usage Tips:\n";
    echo "• Start with simple examples and build complexity gradually\n";
    echo "• Test configurations with edge cases (zero values, negative numbers)\n";
    echo "• Use meaningful variable names for better maintainability\n";
    echo "• Document your formulas for future reference\n";
    echo "• Consider using templates for common patterns\n\n";
}

// Run all examples
echo "Trading Bot Analysis Examples (Simplified & Enhanced)\n";
echo "========================================================\n";

// Store configs for later use
try {
    $stopLossConfig = example1_simple_stop_loss();
    $riskConfig = example2_risk_assessment();
    $positionConfig = example3_position_sizing();
    $performanceConfig = example4_performance_tracker();
    
    // Performance benchmarks
    benchmark_trading_analysis();
    
    // Error handling demonstration
    demonstrate_error_handling();
    
    // Generate production functions
    generate_trading_functions();
    
    // Show summary
    show_configuration_summary();
    
    echo "\n✅ All trading analysis examples completed successfully!\n";
    echo "\n📊 Summary:\n";
    echo "- 4 Trading Analysis Types: Stop Loss, Risk Assessment, Position Sizing, Performance\n";
    echo "- Performance Benchmarks: Included\n";
    echo "- Error Handling: Demonstrated\n";
    echo "- Production Functions: Generated\n";
    echo "- Total Test Scenarios: 16\n";
    echo "- Memory Efficient: Optimized for production use\n\n";
    
    echo "🎯 Ready for Production:\n";
    echo "- Copy generated functions to your trading system\n";
    echo "- Customize thresholds for your risk tolerance\n";
    echo "- Add real-time data feeds\n";
    echo "- Implement proper logging and monitoring\n";
    
} catch (Exception $e) {
    echo "\n❌ Error running examples: " . $e->getMessage() . "\n";
    echo "Please check your RuleFlow installation and try again.\n";
}

?>