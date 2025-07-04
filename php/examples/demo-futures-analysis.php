<?php 
require_once "../src/RuleFlow.php";

/**
 * Example 1: Portfolio Stop Loss Check
 */
function example1_portfolio_stop_loss() {
    echo "\n=== EXAMPLE 1: Portfolio Stop Loss Check ===\n";
    
    $config = [
        'formulas' => [
            [
                'id' => 'portfolio_loss_percent',
                'formula' => '((current_portfolio_value - initial_portfolio_value) / initial_portfolio_value) * 100',
                'inputs' => ['current_portfolio_value', 'initial_portfolio_value'],
                'as' => '$loss_percent'
            ],
            [
                'id' => 'asset_type_stop_loss',
                'switch' => 'asset_type',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'stock'],
                        'result' => -3,
                        'set_vars' => ['$stop_loss_threshold' => -3]
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'crypto'],
                        'result' => -15,
                        'set_vars' => ['$stop_loss_threshold' => -15]
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'forex'],
                        'result' => -2,
                        'set_vars' => ['$stop_loss_threshold' => -2]
                    ]
                ],
                'default' => -5,
                'default_vars' => ['$stop_loss_threshold' => -5]
            ],
            [
                'id' => 'stop_loss_decision',
                'switch' => '$loss_percent',
                'when' => [
                    [
                        'if' => ['op' => '<=', 'value' => -15],  // crypto threshold
                        'result' => 'EMERGENCY_EXIT',
                        'set_vars' => ['$action' => 'stop_system', '$priority' => 'critical']
                    ],
                    [
                        'if' => ['op' => '<=', 'value' => -3],   // stock threshold
                        'result' => 'EMERGENCY_EXIT',
                        'set_vars' => ['$action' => 'stop_system', '$priority' => 'critical']
                    ],
                    [
                        'if' => ['op' => '<=', 'value' => -2],   // forex threshold
                        'result' => 'EMERGENCY_EXIT',
                        'set_vars' => ['$action' => 'stop_system', '$priority' => 'critical']
                    ]
                ],
                'default' => 'CONTINUE',
                'default_vars' => ['$action' => 'continue', '$priority' => 'normal']
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    // Test Case 1: Crypto with acceptable loss
    $inputs1 = [
        'current_portfolio_value' => 90000,
        'initial_portfolio_value' => 100000,
        'asset_type' => 'crypto'
    ];
    
    // Test Case 2: Stock with critical loss
    $inputs2 = [
        'current_portfolio_value' => 96000,
        'initial_portfolio_value' => 100000,
        'asset_type' => 'stock'
    ];
    
    echo "Test Case 1: Crypto Portfolio\n";
    echo "Input: Portfolio ฿100,000 → ฿90,000 (Crypto)\n";
    $result1 = $ruleFlow->evaluate($config, $inputs1);
    echo "Loss: " . round($result1['loss_percent'], 2) . "%\n";
    echo "Threshold: {$result1['stop_loss_threshold']}%\n";
    echo "Decision: {$result1['stop_loss_decision']}\n";
    echo "Action: {$result1['action']}\n\n";
    
    echo "Test Case 2: Stock Portfolio\n";
    echo "Input: Portfolio ฿100,000 → ฿96,000 (Stock)\n";
    $result2 = $ruleFlow->evaluate($config, $inputs2);
    echo "Loss: " . round($result2['loss_percent'], 2) . "%\n";
    echo "Threshold: {$result2['stop_loss_threshold']}%\n";
    echo "Decision: {$result2['stop_loss_decision']}\n";
    echo "Action: {$result2['action']}\n";
}

/**
 * Example 2: Market Trend Analysis
 */
function example2_market_trend_analysis() {
    echo "\n=== EXAMPLE 2: Market Trend Analysis ===\n";
    
    $config = [
        'formulas' => [
            [
                'id' => 'trend_strength',
                'formula' => 'abs(ema_fast - ema_slow) / atr',
                'inputs' => ['ema_fast', 'ema_slow', 'atr'],
                'as' => '$trend_strength_ratio'
            ],
            [
                'id' => 'volatility_check',
                'formula' => 'atr / price * 100',
                'inputs' => ['atr', 'price'],
                'as' => '$volatility_percent'
            ],
            [
                'id' => 'market_condition',
                'scoring' => [
                    'ifs' => [
                        'vars' => ['$trend_strength_ratio', '$volatility_percent'],
                        'tree' => [
                            [
                                'if' => ['op' => '>=', 'value' => 2.0],
                                'ranges' => [
                                    [
                                        'if' => ['op' => '<=', 'value' => 3.0],
                                        'score' => 1,
                                        'condition' => 'STRONG_TREND',
                                        'grid_type' => 'trend_following',
                                        'set_vars' => ['$reduce_grid_density' => false]
                                    ],
                                    [
                                        'if' => ['op' => '>', 'value' => 3.0],
                                        'score' => 2,
                                        'condition' => 'HIGH_VOLATILITY',
                                        'grid_type' => 'wide_range',
                                        'set_vars' => ['$reduce_position_size' => true]
                                    ]
                                ]
                            ],
                            [
                                'if' => ['op' => '<', 'value' => 2.0],
                                'ranges' => [
                                    [
                                        'if' => ['op' => '>=', 'value' => 0],
                                        'score' => 0,
                                        'condition' => 'SIDEWAYS',
                                        'grid_type' => 'standard',
                                        'set_vars' => ['$use_standard_grid' => true]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    // Test Case 1: Strong Trend Market
    $inputs1 = [
        'ema_fast' => 1.1850,
        'ema_slow' => 1.1800,
        'atr' => 0.0025,
        'price' => 1.1825
    ];
    
    // Test Case 2: High Volatility Market
    $inputs2 = [
        'ema_fast' => 1.1900,
        'ema_slow' => 1.1800,
        'atr' => 0.0020,
        'price' => 1.1850
    ];
    
    // Test Case 3: Sideways Market
    $inputs3 = [
        'ema_fast' => 1.1820,
        'ema_slow' => 1.1815,
        'atr' => 0.0015,
        'price' => 1.1818
    ];
    
    echo "Test Case 1: Strong Trend\n";
    echo "Input: EMA Fast=1.1850, EMA Slow=1.1800, ATR=0.0025, Price=1.1825\n";
    $result1 = $ruleFlow->evaluate($config, $inputs1);
    echo "Trend Strength: " . round($result1['trend_strength_ratio'], 2) . "\n";
    echo "Volatility: " . round($result1['volatility_percent'], 2) . "%\n";
    echo "Condition: {$result1['market_condition_condition']}\n";
    echo "Grid Type: {$result1['market_condition_grid_type']}\n\n";
    
    echo "Test Case 2: High Volatility\n";
    echo "Input: EMA Fast=1.1900, EMA Slow=1.1800, ATR=0.0020, Price=1.1850\n";
    $result2 = $ruleFlow->evaluate($config, $inputs2);
    echo "Trend Strength: " . round($result2['trend_strength_ratio'], 2) . "\n";
    echo "Volatility: " . round($result2['volatility_percent'], 2) . "%\n";
    echo "Condition: {$result2['market_condition_condition']}\n";
    echo "Grid Type: {$result2['market_condition_grid_type']}\n";
    if (isset($result2['reduce_position_size'])) {
        echo "Reduce Position Size: Yes\n";
    }
    echo "\n";
    
    echo "Test Case 3: Sideways\n";
    echo "Input: EMA Fast=1.1820, EMA Slow=1.1815, ATR=0.0015, Price=1.1818\n";
    $result3 = $ruleFlow->evaluate($config, $inputs3);
    echo "Trend Strength: " . round($result3['trend_strength_ratio'], 2) . "\n";
    echo "Volatility: " . round($result3['volatility_percent'], 2) . "%\n";
    echo "Condition: {$result3['market_condition_condition']}\n";
    echo "Grid Type: {$result3['market_condition_grid_type']}\n";
}

/**
 * Example 3: Grid Position Management
 */
function example3_grid_position_management() {
    echo "\n=== EXAMPLE 3: Grid Position Management ===\n";
    
    $config = [
        'formulas' => [
            [
                'id' => 'max_grid_by_asset',
                'switch' => 'asset_type',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'stock'],
                        'result' => 10,
                        'set_vars' => ['$max_grids' => 10]
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'crypto'],
                        'result' => 8,
                        'set_vars' => ['$max_grids' => 8]
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'forex'],
                        'result' => 12,
                        'set_vars' => ['$max_grids' => 12]
                    ]
                ],
                'default' => 10,
                'default_vars' => ['$max_grids' => 10]
            ],
            [
                'id' => 'volatility_multiplier',
                'formula' => '1 / (volatility_percent / 100)',
                'inputs' => ['volatility_percent'],
                'as' => '$vol_multiplier'
            ],
            [
                'id' => 'position_size_calculation',
                'formula' => 'min(base_position_size * vol_multiplier * account_size_multiplier, max_position_size)',
                'inputs' => ['base_position_size', 'vol_multiplier', 'account_size_multiplier', 'max_position_size'],
                'as' => '$calculated_position_size'
            ],
            [
                'id' => 'grid_action',
                'switch' => 'active_grid_count',
                'when' => [
                    [
                        'if' => ['op' => '<', 'value' => 8],  // less than max for crypto
                        'result' => 'OPEN_NEW_POSITION',
                        'set_vars' => ['$can_open' => true, '$recommendation' => 'add_grid']
                    ],
                    [
                        'if' => ['op' => '>=', 'value' => 8], // at or above max
                        'result' => 'STOP_NEW_POSITIONS',
                        'set_vars' => ['$can_open' => false, '$monitor_only' => true]
                    ]
                ],
                'default' => 'WAIT',
                'default_vars' => ['$can_open' => false, '$action' => 'wait']
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    // Test Case 1: Can open new positions
    $inputs1 = [
        'asset_type' => 'crypto',
        'active_grid_count' => 5,
        'volatility_percent' => 2.5,
        'base_position_size' => 1000,
        'account_size_multiplier' => 1.0,
        'max_position_size' => 5000
    ];
    
    // Test Case 2: At maximum grids
    $inputs2 = [
        'asset_type' => 'crypto',
        'active_grid_count' => 8,
        'volatility_percent' => 4.0,
        'base_position_size' => 1000,
        'account_size_multiplier' => 1.0,
        'max_position_size' => 5000
    ];
    
    echo "Test Case 1: Can Open New Positions\n";
    echo "Input: Crypto, Active Grids=5/8, Volatility=2.5%\n";
    $result1 = $ruleFlow->evaluate($config, $inputs1);
    echo "Max Grids: {$result1['max_grids']}\n";
    echo "Calculated Position Size: ฿" . number_format($result1['calculated_position_size']) . "\n";
    echo "Action: {$result1['grid_action']}\n";
    echo "Can Open: " . ($result1['can_open'] ? 'Yes' : 'No') . "\n\n";
    
    echo "Test Case 2: At Maximum Grids\n";
    echo "Input: Crypto, Active Grids=8/8, Volatility=4.0%\n";
    $result2 = $ruleFlow->evaluate($config, $inputs2);
    echo "Max Grids: {$result2['max_grids']}\n";
    echo "Calculated Position Size: ฿" . number_format($result2['calculated_position_size']) . "\n";
    echo "Action: {$result2['grid_action']}\n";
    echo "Can Open: " . ($result2['can_open'] ? 'Yes' : 'No') . "\n";
    echo "Monitor Only: " . (isset($result2['monitor_only']) && $result2['monitor_only'] ? 'Yes' : 'No') . "\n";
}

/**
 * Example 4: Performance Evaluation
 */
function example4_performance_evaluation() {
    echo "\n=== EXAMPLE 4: Performance Evaluation ===\n";
    
    $config = [
        'formulas' => [
            [
                'id' => 'win_rate_calculation',
                'formula' => '(winning_trades / total_trades) * 100',
                'inputs' => ['winning_trades', 'total_trades'],
                'as' => '$win_rate_percent'
            ],
            [
                'id' => 'profit_calculation',
                'formula' => '((current_portfolio_value - initial_portfolio_value) / initial_portfolio_value) * 100',
                'inputs' => ['current_portfolio_value', 'initial_portfolio_value'],
                'as' => '$profit_percent'
            ],
            [
                'id' => 'win_rate_threshold_by_asset',
                'switch' => 'asset_type',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'stock'],
                        'result' => 70,
                        'set_vars' => ['$min_win_rate' => 70]
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'crypto'],
                        'result' => 50,
                        'set_vars' => ['$min_win_rate' => 50]
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'forex'],
                        'result' => 60,
                        'set_vars' => ['$min_win_rate' => 60]
                    ]
                ],
                'default' => 60,
                'default_vars' => ['$min_win_rate' => 60]
            ],
            [
                'id' => 'performance_decision',
                'scoring' => [
                    'ifs' => [
                        'vars' => ['$profit_percent', '$win_rate_percent'],
                        'tree' => [
                            [
                                'if' => ['op' => '>=', 'value' => 10],
                                'ranges' => [
                                    [
                                        'if' => ['op' => '>=', 'value' => 50],
                                        'score' => 0,
                                        'action' => 'TAKE_PROFIT',
                                        'reason' => 'target_reached',
                                        'set_vars' => ['$withdraw_profit' => true]
                                    ]
                                ]
                            ],
                            [
                                'if' => ['op' => 'between', 'value' => [0, 10]],
                                'ranges' => [
                                    [
                                        'if' => ['op' => '<', 'value' => 50],  // win rate < 50%
                                        'score' => 0,
                                        'action' => 'ADJUST_STRATEGY',
                                        'reason' => 'poor_win_rate',
                                        'set_vars' => ['$need_optimization' => true]
                                    ],
                                    [
                                        'if' => ['op' => '>=', 'value' => 50],
                                        'score' => 0,
                                        'action' => 'CONTINUE',
                                        'reason' => 'normal_performance',
                                        'set_vars' => ['$status' => 'normal']
                                    ]
                                ]
                            ],
                            [
                                'if' => ['op' => '<', 'value' => 0],
                                'ranges' => [
                                    [
                                        'if' => ['op' => '>=', 'value' => 0],
                                        'score' => 0,
                                        'action' => 'REVIEW_RISK',
                                        'reason' => 'losing_money',
                                        'set_vars' => ['$urgent_review' => true]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    // Test Case 1: Good performance - take profit
    $inputs1 = [
        'winning_trades' => 45,
        'total_trades' => 60,
        'current_portfolio_value' => 115000,
        'initial_portfolio_value' => 100000,
        'asset_type' => 'stock'
    ];
    
    // Test Case 2: Poor win rate - need adjustment
    $inputs2 = [
        'winning_trades' => 25,
        'total_trades' => 60,
        'current_portfolio_value' => 103000,
        'initial_portfolio_value' => 100000,
        'asset_type' => 'crypto'
    ];
    
    // Test Case 3: Losing money
    $inputs3 = [
        'winning_trades' => 30,
        'total_trades' => 70,
        'current_portfolio_value' => 97000,
        'initial_portfolio_value' => 100000,
        'asset_type' => 'forex'
    ];
    
    echo "Test Case 1: Good Performance\n";
    echo "Input: 45/60 wins, Portfolio ฿100k → ฿115k (Stock)\n";
    $result1 = $ruleFlow->evaluate($config, $inputs1);
    echo "Win Rate: " . round($result1['win_rate_percent'], 1) . "% (Min: {$result1['min_win_rate']}%)\n";
    echo "Profit: " . round($result1['profit_percent'], 1) . "%\n";
    echo "Action: {$result1['performance_decision_action']}\n";
    echo "Reason: {$result1['performance_decision_reason']}\n";
    if (isset($result1['withdraw_profit'])) {
        echo "Withdraw Profit: Yes\n";
    }
    echo "\n";
    
    echo "Test Case 2: Poor Win Rate\n";
    echo "Input: 25/60 wins, Portfolio ฿100k → ฿103k (Crypto)\n";
    $result2 = $ruleFlow->evaluate($config, $inputs2);
    echo "Win Rate: " . round($result2['win_rate_percent'], 1) . "% (Min: {$result2['min_win_rate']}%)\n";
    echo "Profit: " . round($result2['profit_percent'], 1) . "%\n";
    echo "Action: {$result2['performance_decision_action']}\n";
    echo "Reason: {$result2['performance_decision_reason']}\n";
    if (isset($result2['need_optimization'])) {
        echo "Need Optimization: Yes\n";
    }
    echo "\n";
    
    echo "Test Case 3: Losing Money\n";
    echo "Input: 30/70 wins, Portfolio ฿100k → ฿97k (Forex)\n";
    $result3 = $ruleFlow->evaluate($config, $inputs3);
    echo "Win Rate: " . round($result3['win_rate_percent'], 1) . "% (Min: {$result3['min_win_rate']}%)\n";
    echo "Profit: " . round($result3['profit_percent'], 1) . "%\n";
    echo "Action: {$result3['performance_decision_action']}\n";
    echo "Reason: {$result3['performance_decision_reason']}\n";
    if (isset($result3['urgent_review'])) {
        echo "Urgent Review: Yes\n";
    }
}

/**
 * Example 5: Complete Grid Bot Decision Flow with $ Notation
 */
function example5_complete_flow() {
    echo "\n=== EXAMPLE 5: Complete Grid Bot Decision Flow ===\n";
    
    $config = [
        'formulas' => [
            // 1. Portfolio Health Check
            [
                'id' => 'portfolio_loss',
                'formula' => '((current_value - initial_value) / initial_value) * 100',
                'inputs' => ['current_value', 'initial_value'],
                'as' => '$loss_percent'
            ],
            
            // 2. Market Analysis
            [
                'id' => 'trend_strength',
                'formula' => 'abs(ema_fast - ema_slow) / atr',
                'inputs' => ['ema_fast', 'ema_slow', 'atr'],
                'as' => '$trend_ratio'
            ],
            [
                'id' => 'volatility',
                'formula' => 'atr / price * 100',
                'inputs' => ['atr', 'price'],
                'as' => '$vol_percent'
            ],
            
            // 3. Risk Assessment
            [
                'id' => 'risk_score',
                'rules' => [
                    [
                        'var' => '$loss_percent',
                        'ranges' => [
                            ['if' => ['op' => '<=', 'value' => -10], 'score' => 50],
                            ['if' => ['op' => '<=', 'value' => -5], 'score' => 25],
                            ['if' => ['op' => '<=', 'value' => -2], 'score' => 10]
                        ]
                    ],
                    [
                        'var' => '$vol_percent',
                        'ranges' => [
                            ['if' => ['op' => '>=', 'value' => 5], 'score' => 20],
                            ['if' => ['op' => '>=', 'value' => 3], 'score' => 10]
                        ]
                    ],
                    [
                        'var' => 'volume_spike',
                        'if' => ['op' => '>=', 'value' => 2],
                        'score' => 15
                    ]
                ]
            ],
            
            // 4. Final Decision with $ notation
            [
                'id' => 'trading_decision',
                'scoring' => [
                    'ifs' => [
                        'vars' => ['$risk_score', '$trend_ratio'],
                        'tree' => [
                            [
                                'if' => ['op' => '>=', 'value' => 50],
                                'ranges' => [
                                    [
                                        'if' => ['op' => '>=', 'value' => 0],
                                        'score' => 0,
                                        'decision' => 'EMERGENCY_STOP',
                                        'action' => 'close_all_positions',
                                        'grid_mode' => 'emergency',
                                        'set_vars' => ['$alert_level' => 'critical', '$grid_mode' => 'emergency']
                                    ]
                                ]
                            ],
                            [
                                'if' => ['op' => 'between', 'value' => [20, 49]],
                                'ranges' => [
                                    [
                                        'if' => ['op' => '>=', 'value' => 2],
                                        'score' => 0,
                                        'decision' => 'REDUCE_EXPOSURE',
                                        'action' => 'trend_following_mode',
                                        'grid_mode' => 'cautious',
                                        'set_vars' => ['$grid_density' => 0.5, '$grid_mode' => 'cautious']
                                    ],
                                    [
                                        'if' => ['op' => '<', 'value' => 2],
                                        'score' => 0,
                                        'decision' => 'CAUTIOUS_TRADING',
                                        'action' => 'reduce_position_size',
                                        'grid_mode' => 'reduced',
                                        'set_vars' => ['$position_multiplier' => 0.7, '$grid_mode' => 'reduced']
                                    ]
                                ]
                            ],
                            [
                                'if' => ['op' => '<', 'value' => 20],
                                'ranges' => [
                                    [
                                        'if' => ['op' => '>=', 'value' => 2],
                                        'score' => 0,
                                        'decision' => 'NORMAL_GRID',
                                        'action' => 'standard_operation',
                                        'grid_mode' => 'standard',
                                        'set_vars' => ['$grid_mode' => 'standard']
                                    ],
                                    [
                                        'if' => ['op' => '<', 'value' => 2],
                                        'score' => 0,
                                        'decision' => 'AGGRESSIVE_GRID',
                                        'action' => 'increase_grid_density',
                                        'grid_mode' => 'aggressive',
                                        'set_vars' => ['$grid_density' => 1.5, '$grid_mode' => 'aggressive']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    // Scenario 1: Emergency situation
    $inputs1 = [
        'current_value' => 85000,
        'initial_value' => 100000,
        'ema_fast' => 1.1800,
        'ema_slow' => 1.1750,
        'atr' => 0.0025,
        'price' => 1.1775,
        'volume_spike' => 3.2
    ];
    
    // Scenario 2: Normal trading
    $inputs2 = [
        'current_value' => 102000,
        'initial_value' => 100000,
        'ema_fast' => 1.1825,
        'ema_slow' => 1.1820,
        'atr' => 0.0015,
        'price' => 1.1822,
        'volume_spike' => 1.1
    ];
    
    echo "Scenario 1: Crisis Mode\n";
    echo "Input: Portfolio -15%, High volatility, Volume spike 3.2x\n";
    $result1 = $ruleFlow->evaluate($config, $inputs1);
    echo "Portfolio Loss: " . round($result1['loss_percent'], 1) . "%\n";
    echo "Trend Strength: " . round($result1['trend_ratio'], 2) . "\n";
    echo "Volatility: " . round($result1['vol_percent'], 2) . "%\n";
    echo "Risk Score: {$result1['risk_score']}\n";
    echo "Decision: {$result1['trading_decision_decision']}\n";
    echo "Action: {$result1['trading_decision_action']}\n";
    echo "Grid Mode: {$result1['trading_decision_grid_mode']}\n";
    echo "Alert Level: {$result1['alert_level']}\n\n";
    
    echo "Scenario 2: Normal Trading\n";
    echo "Input: Portfolio +2%, Low volatility, Normal volume\n";
    $result2 = $ruleFlow->evaluate($config, $inputs2);
    echo "Portfolio Gain: " . round($result2['loss_percent'], 1) . "%\n";
    echo "Trend Strength: " . round($result2['trend_ratio'], 2) . "\n";
    echo "Volatility: " . round($result2['vol_percent'], 2) . "%\n";
    echo "Risk Score: {$result2['risk_score']}\n";
    echo "Decision: {$result2['trading_decision_decision']}\n";
    echo "Action: {$result2['trading_decision_action']}\n";
    echo "Grid Mode: {$result2['trading_decision_grid_mode']}\n";
}

// Run all examples
echo "Grid Bot RuleFlow Examples (Updated with \$ Notation)\n";
echo "========================================================\n";

example1_portfolio_stop_loss();
example2_market_trend_analysis();
example3_grid_position_management();
example4_performance_evaluation();
example5_complete_flow();

echo "\n✅ All examples completed!\n";

/**
 * Generate PHP Functions for Production Use
 */
function generate_production_functions() {
    echo "\n=== GENERATED PHP FUNCTIONS FOR PRODUCTION ===\n";
    
    $ruleFlow = new RuleFlow();
    
    // Generate function for portfolio stop loss
    $stopLossConfig = [
        'formulas' => [
            [
                'id' => 'portfolio_loss_percent',
                'formula' => '((current_portfolio_value - initial_portfolio_value) / initial_portfolio_value) * 100',
                'inputs' => ['current_portfolio_value', 'initial_portfolio_value'],
                'as' => '$loss_percent'
            ],
            [
                'id' => 'asset_type_stop_loss',
                'switch' => 'asset_type',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'stock'],
                        'result' => -3,
                        'set_vars' => ['$stop_loss_threshold' => -3]
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'crypto'],
                        'result' => -15,
                        'set_vars' => ['$stop_loss_threshold' => -15]
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'forex'],
                        'result' => -2,
                        'set_vars' => ['$stop_loss_threshold' => -2]
                    ]
                ],
                'default' => -5,
                'default_vars' => ['$stop_loss_threshold' => -5]
            ],
            [
                'id' => 'stop_loss_decision',
                'switch' => '$loss_percent',
                'when' => [
                    [
                        'if' => ['op' => '<=', 'value' => -15],
                        'result' => 'EMERGENCY_EXIT',
                        'set_vars' => ['$action' => 'stop_system', '$priority' => 'critical']
                    ],
                    [
                        'if' => ['op' => '<=', 'value' => -3],
                        'result' => 'EMERGENCY_EXIT',
                        'set_vars' => ['$action' => 'stop_system', '$priority' => 'critical']
                    ],
                    [
                        'if' => ['op' => '<=', 'value' => -2],
                        'result' => 'EMERGENCY_EXIT',
                        'set_vars' => ['$action' => 'stop_system', '$priority' => 'critical']
                    ]
                ],
                'default' => 'CONTINUE',
                'default_vars' => ['$action' => 'continue', '$priority' => 'normal']
            ]
        ]
    ];
    
    echo "\n// Portfolio Stop Loss Function\n";
    echo "\$portfolioStopLossFunction = " . $ruleFlow->generateFunctionAsString($stopLossConfig) . ";\n\n";
    
    // Generate function for market analysis
    $marketAnalysisConfig = [
        'formulas' => [
            [
                'id' => 'trend_strength',
                'formula' => 'abs(ema_fast - ema_slow) / atr',
                'inputs' => ['ema_fast', 'ema_slow', 'atr'],
                'as' => '$trend_strength_ratio'
            ],
            [
                'id' => 'volatility_check',
                'formula' => 'atr / price * 100',
                'inputs' => ['atr', 'price'],
                'as' => '$volatility_percent'
            ],
            [
                'id' => 'market_condition',
                'scoring' => [
                    'ifs' => [
                        'vars' => ['$trend_strength_ratio', '$volatility_percent'],
                        'tree' => [
                            [
                                'if' => ['op' => '>=', 'value' => 2.0],
                                'ranges' => [
                                    [
                                        'if' => ['op' => '<=', 'value' => 3.0],
                                        'score' => 1,
                                        'condition' => 'STRONG_TREND',
                                        'grid_type' => 'trend_following',
                                        'grid_mode' => 'trend'
                                    ],
                                    [
                                        'if' => ['op' => '>', 'value' => 3.0],
                                        'score' => 2,
                                        'condition' => 'HIGH_VOLATILITY',
                                        'grid_type' => 'wide_range',
                                        'grid_mode' => 'wide'
                                    ]
                                ]
                            ],
                            [
                                'if' => ['op' => '<', 'value' => 2.0],
                                'ranges' => [
                                    [
                                        'if' => ['op' => '>=', 'value' => 0],
                                        'score' => 0,
                                        'condition' => 'SIDEWAYS',
                                        'grid_type' => 'standard',
                                        'grid_mode' => 'standard'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];
    
    echo "// Market Analysis Function\n";
    echo "\$marketAnalysisFunction = " . $ruleFlow->generateFunctionAsString($marketAnalysisConfig) . ";\n\n";
    
    echo "// Usage Example:\n";
    echo "/*\n";
    echo "\$inputs = [\n";
    echo "    'current_portfolio_value' => 95000,\n";
    echo "    'initial_portfolio_value' => 100000,\n";
    echo "    'asset_type' => 'crypto'\n";
    echo "];\n";
    echo "\$result = \$portfolioStopLossFunction(\$inputs);\n";
    echo "if (\$result['stop_loss_decision'] === 'EMERGENCY_EXIT') {\n";
    echo "    // Stop all trading operations\n";
    echo "    stopAllPositions();\n";
    echo "}\n";
    echo "*/\n";
}

generate_production_functions();

?>