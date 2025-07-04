<?php 
require_once "../src/RuleFlow.php";

/**
 * Demo 1: Length Unit Converter
 */
function demo1_length_converter() {
    echo "\n=== DEMO 1: Length Unit Converter ===\n";
    
    $config = [
        'formulas' => [
            // Convert input to meters first (base unit)
            [
                'id' => 'to_meters',
                'switch' => 'from_unit',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'mm'],
                        'result' => 'millimeter',
                        'set_vars' => ['$meters' => '$value * 0.001']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'cm'],
                        'result' => 'centimeter', 
                        'set_vars' => ['$meters' => '$value * 0.01']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'm'],
                        'result' => 'meter',
                        'set_vars' => ['$meters' => '$value']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'km'],
                        'result' => 'kilometer',
                        'set_vars' => ['$meters' => '$value * 1000']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'inch'],
                        'result' => 'inch',
                        'set_vars' => ['$meters' => '$value * 0.0254']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'ft'],
                        'result' => 'foot',
                        'set_vars' => ['$meters' => '$value * 0.3048']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'yard'],
                        'result' => 'yard',
                        'set_vars' => ['$meters' => '$value * 0.9144']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'mile'],
                        'result' => 'mile',
                        'set_vars' => ['$meters' => '$value * 1609.344']
                    ]
                ],
                'default' => 'unknown',
                'default_vars' => ['$meters' => '$value']
            ],
            
            // Convert from meters to target unit
            [
                'id' => 'convert_result',
                'switch' => 'to_unit',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'mm'],
                        'result' => 'millimeter',
                        'set_vars' => ['$result' => '$meters * 1000', '$unit_name' => 'millimeters']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'cm'],
                        'result' => 'centimeter',
                        'set_vars' => ['$result' => '$meters * 100', '$unit_name' => 'centimeters']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'm'],
                        'result' => 'meter',
                        'set_vars' => ['$result' => '$meters', '$unit_name' => 'meters']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'km'],
                        'result' => 'kilometer',
                        'set_vars' => ['$result' => '$meters / 1000', '$unit_name' => 'kilometers']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'inch'],
                        'result' => 'inch',
                        'set_vars' => ['$result' => '$meters / 0.0254', '$unit_name' => 'inches']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'ft'],
                        'result' => 'foot',
                        'set_vars' => ['$result' => '$meters / 0.3048', '$unit_name' => 'feet']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'yard'],
                        'result' => 'yard',
                        'set_vars' => ['$result' => '$meters / 0.9144', '$unit_name' => 'yards']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'mile'],
                        'result' => 'mile',
                        'set_vars' => ['$result' => '$meters / 1609.344', '$unit_name' => 'miles']
                    ]
                ],
                'default' => 'unknown',
                'default_vars' => ['$result' => '$meters', '$unit_name' => 'meters']
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    // Test cases
    $testCases = [
        ['value' => 100, 'from_unit' => 'cm', 'to_unit' => 'm'],
        ['value' => 5, 'from_unit' => 'ft', 'to_unit' => 'inch'], 
        ['value' => 2, 'from_unit' => 'km', 'to_unit' => 'mile'],
        ['value' => 12, 'from_unit' => 'inch', 'to_unit' => 'cm']
    ];
    
    foreach ($testCases as $i => $inputs) {
        echo "Test Case " . ($i + 1) . ": Convert {$inputs['value']} {$inputs['from_unit']} → {$inputs['to_unit']}\n";
        $result = $ruleFlow->evaluate($config, $inputs);
        echo "Result: " . round($result['result'], 4) . " {$result['unit_name']}\n\n";
    }
}

/**
 * Demo 2: Weight Unit Converter
 */
function demo2_weight_converter() {
    echo "\n=== DEMO 2: Weight Unit Converter ===\n";
    
    $config = [
        'formulas' => [
            // Convert to grams (base unit)
            [
                'id' => 'to_grams',
                'switch' => 'from_unit',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'mg'],
                        'result' => 'milligram',
                        'set_vars' => ['$grams' => '$value * 0.001']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'g'],
                        'result' => 'gram',
                        'set_vars' => ['$grams' => '$value']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'kg'],
                        'result' => 'kilogram',
                        'set_vars' => ['$grams' => '$value * 1000']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'ton'],
                        'result' => 'metric_ton',
                        'set_vars' => ['$grams' => '$value * 1000000']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'oz'],
                        'result' => 'ounce',
                        'set_vars' => ['$grams' => '$value * 28.3495']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'lb'],
                        'result' => 'pound',
                        'set_vars' => ['$grams' => '$value * 453.592']
                    ]
                ],
                'default' => 'unknown',
                'default_vars' => ['$grams' => '$value']
            ],
            
            // Convert from grams to target unit
            [
                'id' => 'weight_result',
                'switch' => 'to_unit',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'mg'],
                        'result' => 'milligram',
                        'set_vars' => ['$result' => '$grams * 1000', '$unit_name' => 'milligrams']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'g'],
                        'result' => 'gram',
                        'set_vars' => ['$result' => '$grams', '$unit_name' => 'grams']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'kg'],
                        'result' => 'kilogram',
                        'set_vars' => ['$result' => '$grams / 1000', '$unit_name' => 'kilograms']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'ton'],
                        'result' => 'metric_ton',
                        'set_vars' => ['$result' => '$grams / 1000000', '$unit_name' => 'metric tons']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'oz'],
                        'result' => 'ounce',
                        'set_vars' => ['$result' => '$grams / 28.3495', '$unit_name' => 'ounces']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'lb'],
                        'result' => 'pound',
                        'set_vars' => ['$result' => '$grams / 453.592', '$unit_name' => 'pounds']
                    ]
                ],
                'default' => 'unknown',
                'default_vars' => ['$result' => '$grams', '$unit_name' => 'grams']
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    $testCases = [
        ['value' => 2.5, 'from_unit' => 'kg', 'to_unit' => 'lb'],
        ['value' => 16, 'from_unit' => 'oz', 'to_unit' => 'g'],
        ['value' => 1000, 'from_unit' => 'mg', 'to_unit' => 'g'],
        ['value' => 1, 'from_unit' => 'ton', 'to_unit' => 'kg']
    ];
    
    foreach ($testCases as $i => $inputs) {
        echo "Test Case " . ($i + 1) . ": Convert {$inputs['value']} {$inputs['from_unit']} → {$inputs['to_unit']}\n";
        $result = $ruleFlow->evaluate($config, $inputs);
        echo "Result: " . round($result['result'], 4) . " {$result['unit_name']}\n\n";
    }
}

/**
 * Demo 3: Temperature Converter
 */
function demo3_temperature_converter() {
    echo "\n=== DEMO 3: Temperature Converter ===\n";
    
    $config = [
        'formulas' => [
            // Convert to Celsius (base unit)
            [
                'id' => 'to_celsius',
                'switch' => 'from_unit',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'C'],
                        'result' => 'celsius',
                        'set_vars' => ['$celsius' => '$value']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'F'],
                        'result' => 'fahrenheit',
                        'set_vars' => ['$celsius' => '($value - 32) * 5 / 9']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'K'],
                        'result' => 'kelvin',
                        'set_vars' => ['$celsius' => '$value - 273.15']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'R'],
                        'result' => 'rankine',
                        'set_vars' => ['$celsius' => '($value - 491.67) * 5 / 9']
                    ]
                ],
                'default' => 'unknown',
                'default_vars' => ['$celsius' => '$value']
            ],
            
            // Convert from Celsius to target unit
            [
                'id' => 'temp_result',
                'switch' => 'to_unit',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'C'],
                        'result' => 'celsius',
                        'set_vars' => ['$result' => '$celsius', '$unit_name' => '°C', '$unit_desc' => 'Celsius']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'F'],
                        'result' => 'fahrenheit',
                        'set_vars' => ['$result' => '$celsius * 9 / 5 + 32', '$unit_name' => '°F', '$unit_desc' => 'Fahrenheit']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'K'],
                        'result' => 'kelvin',
                        'set_vars' => ['$result' => '$celsius + 273.15', '$unit_name' => 'K', '$unit_desc' => 'Kelvin']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'R'],
                        'result' => 'rankine',
                        'set_vars' => ['$result' => '($celsius + 273.15) * 9 / 5', '$unit_name' => '°R', '$unit_desc' => 'Rankine']
                    ]
                ],
                'default' => 'unknown',
                'default_vars' => ['$result' => '$celsius', '$unit_name' => '°C', '$unit_desc' => 'Celsius']
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    $testCases = [
        ['value' => 0, 'from_unit' => 'C', 'to_unit' => 'F'],      // Freezing point
        ['value' => 100, 'from_unit' => 'C', 'to_unit' => 'K'],    // Boiling point
        ['value' => 98.6, 'from_unit' => 'F', 'to_unit' => 'C'],  // Body temperature
        ['value' => 300, 'from_unit' => 'K', 'to_unit' => 'F'],   // Room temperature
        ['value' => -40, 'from_unit' => 'C', 'to_unit' => 'F']    // Special point where C = F
    ];
    
    foreach ($testCases as $i => $inputs) {
        echo "Test Case " . ($i + 1) . ": Convert {$inputs['value']}°{$inputs['from_unit']} → °{$inputs['to_unit']}\n";
        $result = $ruleFlow->evaluate($config, $inputs);
        echo "Result: " . round($result['result'], 2) . " {$result['unit_name']} ({$result['unit_desc']})\n\n";
    }
}

/**
 * Demo 4: Area Unit Converter
 */
function demo4_area_converter() {
    echo "\n=== DEMO 4: Area Unit Converter ===\n";
    
    $config = [
        'formulas' => [
            // Convert to square meters (base unit)
            [
                'id' => 'to_square_meters',
                'switch' => 'from_unit',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'mm2'],
                        'result' => 'square_millimeter',
                        'set_vars' => ['$sq_meters' => '$value * 0.000001']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'cm2'],
                        'result' => 'square_centimeter',
                        'set_vars' => ['$sq_meters' => '$value * 0.0001']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'm2'],
                        'result' => 'square_meter',
                        'set_vars' => ['$sq_meters' => '$value']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'km2'],
                        'result' => 'square_kilometer',
                        'set_vars' => ['$sq_meters' => '$value * 1000000']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'in2'],
                        'result' => 'square_inch',
                        'set_vars' => ['$sq_meters' => '$value * 0.00064516']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'ft2'],
                        'result' => 'square_foot',
                        'set_vars' => ['$sq_meters' => '$value * 0.092903']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'acre'],
                        'result' => 'acre',
                        'set_vars' => ['$sq_meters' => '$value * 4046.86']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'hectare'],
                        'result' => 'hectare',
                        'set_vars' => ['$sq_meters' => '$value * 10000']
                    ]
                ],
                'default' => 'unknown',
                'default_vars' => ['$sq_meters' => '$value']
            ],
            
            // Convert from square meters to target unit
            [
                'id' => 'area_result',
                'switch' => 'to_unit',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'mm2'],
                        'result' => 'square_millimeter',
                        'set_vars' => ['$result' => '$sq_meters * 1000000', '$unit_name' => 'square millimeters']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'cm2'],
                        'result' => 'square_centimeter',
                        'set_vars' => ['$result' => '$sq_meters * 10000', '$unit_name' => 'square centimeters']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'm2'],
                        'result' => 'square_meter',
                        'set_vars' => ['$result' => '$sq_meters', '$unit_name' => 'square meters']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'km2'],
                        'result' => 'square_kilometer',
                        'set_vars' => ['$result' => '$sq_meters / 1000000', '$unit_name' => 'square kilometers']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'in2'],
                        'result' => 'square_inch',
                        'set_vars' => ['$result' => '$sq_meters / 0.00064516', '$unit_name' => 'square inches']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'ft2'],
                        'result' => 'square_foot',
                        'set_vars' => ['$result' => '$sq_meters / 0.092903', '$unit_name' => 'square feet']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'acre'],
                        'result' => 'acre',
                        'set_vars' => ['$result' => '$sq_meters / 4046.86', '$unit_name' => 'acres']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'hectare'],
                        'result' => 'hectare',
                        'set_vars' => ['$result' => '$sq_meters / 10000', '$unit_name' => 'hectares']
                    ]
                ],
                'default' => 'unknown',
                'default_vars' => ['$result' => '$sq_meters', '$unit_name' => 'square meters']
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    $testCases = [
        ['value' => 1, 'from_unit' => 'hectare', 'to_unit' => 'm2'],
        ['value' => 1000, 'from_unit' => 'ft2', 'to_unit' => 'm2'],
        ['value' => 2.5, 'from_unit' => 'acre', 'to_unit' => 'hectare'],
        ['value' => 500, 'from_unit' => 'cm2', 'to_unit' => 'in2']
    ];
    
    foreach ($testCases as $i => $inputs) {
        echo "Test Case " . ($i + 1) . ": Convert {$inputs['value']} {$inputs['from_unit']} → {$inputs['to_unit']}\n";
        $result = $ruleFlow->evaluate($config, $inputs);
        echo "Result: " . round($result['result'], 4) . " {$result['unit_name']}\n\n";
    }
}

/**
 * Demo 5: Volume Unit Converter
 */
function demo5_volume_converter() {
    echo "\n=== DEMO 5: Volume Unit Converter ===\n";
    
    $config = [
        'formulas' => [
            // Convert to liters (base unit)
            [
                'id' => 'to_liters',
                'switch' => 'from_unit',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'ml'],
                        'result' => 'milliliter',
                        'set_vars' => ['$liters' => '$value * 0.001']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'l'],
                        'result' => 'liter',
                        'set_vars' => ['$liters' => '$value']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'm3'],
                        'result' => 'cubic_meter',
                        'set_vars' => ['$liters' => '$value * 1000']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'fl_oz'],
                        'result' => 'fluid_ounce',
                        'set_vars' => ['$liters' => '$value * 0.0295735']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'cup'],
                        'result' => 'cup',
                        'set_vars' => ['$liters' => '$value * 0.236588']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'pint'],
                        'result' => 'pint',
                        'set_vars' => ['$liters' => '$value * 0.473176']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'quart'],
                        'result' => 'quart',
                        'set_vars' => ['$liters' => '$value * 0.946353']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'gallon'],
                        'result' => 'gallon',
                        'set_vars' => ['$liters' => '$value * 3.78541']
                    ]
                ],
                'default' => 'unknown',
                'default_vars' => ['$liters' => '$value']
            ],
            
            // Convert from liters to target unit
            [
                'id' => 'volume_result',
                'switch' => 'to_unit',
                'when' => [
                    [
                        'if' => ['op' => '==', 'value' => 'ml'],
                        'result' => 'milliliter',
                        'set_vars' => ['$result' => '$liters * 1000', '$unit_name' => 'milliliters']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'l'],
                        'result' => 'liter',
                        'set_vars' => ['$result' => '$liters', '$unit_name' => 'liters']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'm3'],
                        'result' => 'cubic_meter',
                        'set_vars' => ['$result' => '$liters / 1000', '$unit_name' => 'cubic meters']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'fl_oz'],
                        'result' => 'fluid_ounce',
                        'set_vars' => ['$result' => '$liters / 0.0295735', '$unit_name' => 'fluid ounces']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'cup'],
                        'result' => 'cup',
                        'set_vars' => ['$result' => '$liters / 0.236588', '$unit_name' => 'cups']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'pint'],
                        'result' => 'pint',
                        'set_vars' => ['$result' => '$liters / 0.473176', '$unit_name' => 'pints']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'quart'],
                        'result' => 'quart',
                        'set_vars' => ['$result' => '$liters / 0.946353', '$unit_name' => 'quarts']
                    ],
                    [
                        'if' => ['op' => '==', 'value' => 'gallon'],
                        'result' => 'gallon',
                        'set_vars' => ['$result' => '$liters / 3.78541', '$unit_name' => 'gallons']
                    ]
                ],
                'default' => 'unknown',
                'default_vars' => ['$result' => '$liters', '$unit_name' => 'liters']
            ]
        ]
    ];
    
    $ruleFlow = new RuleFlow();
    
    $testCases = [
        ['value' => 2, 'from_unit' => 'gallon', 'to_unit' => 'l'],
        ['value' => 500, 'from_unit' => 'ml', 'to_unit' => 'cup'],
        ['value' => 1, 'from_unit' => 'm3', 'to_unit' => 'gallon'],
        ['value' => 16, 'from_unit' => 'fl_oz', 'to_unit' => 'ml']
    ];
    
    foreach ($testCases as $i => $inputs) {
        echo "Test Case " . ($i + 1) . ": Convert {$inputs['value']} {$inputs['from_unit']} → {$inputs['to_unit']}\n";
        $result = $ruleFlow->evaluate($config, $inputs);
        echo "Result: " . round($result['result'], 4) . " {$result['unit_name']}\n\n";
    }
}

/*
* Demo 6: Time Unit Converter (Fixed)
*/
function demo6_time_converter() {
   echo "\n=== DEMO 6: Time Unit Converter ===\n";
   
   $config = [
       'formulas' => [
           // Convert to seconds (base unit)
           [
               'id' => 'to_seconds',
               'switch' => 'from_unit',
               'when' => [
                   [
                       'if' => ['op' => '==', 'value' => 'ms'],
                       'result' => 'millisecond',
                       'set_vars' => ['$seconds' => '$value * 0.001']
                   ],
                   [
                       'if' => ['op' => '==', 'value' => 's'],
                       'result' => 'second',
                       'set_vars' => ['$seconds' => '$value']
                   ],
                   [
                       'if' => ['op' => '==', 'value' => 'min'],
                       'result' => 'minute',
                       'set_vars' => ['$seconds' => '$value * 60']
                   ],
                   [
                       'if' => ['op' => '==', 'value' => 'hr'],
                       'result' => 'hour',
                       'set_vars' => ['$seconds' => '$value * 3600']
                   ],
                   [
                       'if' => ['op' => '==', 'value' => 'day'],
                       'result' => 'day',
                       'set_vars' => ['$seconds' => '$value * 86400']
                   ],
                   [
                       'if' => ['op' => '==', 'value' => 'week'],
                       'result' => 'week',
                       'set_vars' => ['$seconds' => '$value * 604800']
                   ],
                   [
                       'if' => ['op' => '==', 'value' => 'month'],
                       'result' => 'month',
                       'set_vars' => ['$seconds' => '$value * 2629800'] // approx. 30.44 days
                   ],
                   [
                       'if' => ['op' => '==', 'value' => 'year'],
                       'result' => 'year',
                       'set_vars' => ['$seconds' => '$value * 31557600'] // approx. 365.25 days
                   ]
               ],
               'default' => 'unknown',
               'default_vars' => ['$seconds' => '$value']
           ],
           
           // Convert from seconds to target unit
           [
               'id' => 'time_result',
               'switch' => 'to_unit',
               'when' => [
                   [
                       'if' => ['op' => '==', 'value' => 'ms'],
                       'result' => 'millisecond',
                       'set_vars' => ['$result' => '$seconds * 1000', '$unit_name' => 'milliseconds']
                   ],
                   [
                       'if' => ['op' => '==', 'value' => 's'],
                       'result' => 'second',
                       'set_vars' => ['$result' => '$seconds', '$unit_name' => 'seconds']
                   ],
                   [
                       'if' => ['op' => '==', 'value' => 'min'],
                       'result' => 'minute',
                       'set_vars' => ['$result' => '$seconds / 60', '$unit_name' => 'minutes']
                   ],
                   [
                       'if' => ['op' => '==', 'value' => 'hr'],
                       'result' => 'hour',
                       'set_vars' => ['$result' => '$seconds / 3600', '$unit_name' => 'hours']
                   ],
                   [
                       'if' => ['op' => '==', 'value' => 'day'],
                       'result' => 'day',
                       'set_vars' => ['$result' => '$seconds / 86400', '$unit_name' => 'days']
                   ],
                   [
                       'if' => ['op' => '==', 'value' => 'week'],
                       'result' => 'week',
                       'set_vars' => ['$result' => '$seconds / 604800', '$unit_name' => 'weeks']
                   ],
                   [
                       'if' => ['op' => '==', 'value' => 'month'],
                       'result' => 'month',
                       'set_vars' => ['$result' => '$seconds / 2629800', '$unit_name' => 'months']
                   ],
                   [
                       'if' => ['op' => '==', 'value' => 'year'],
                       'result' => 'year',
                       'set_vars' => ['$result' => '$seconds / 31557600', '$unit_name' => 'years']
                   ]
               ],
               'default' => 'unknown',
               'default_vars' => ['$result' => '$seconds', '$unit_name' => 'seconds']
           ]
       ]
   ];

   $ruleFlow = new RuleFlow();

   $testCases = [
       ['value' => 2, 'from_unit' => 'day', 'to_unit' => 'hr'],
       ['value' => 90, 'from_unit' => 'min', 'to_unit' => 'hr'],
       ['value' => 1, 'from_unit' => 'year', 'to_unit' => 'month'],
       ['value' => 3600000, 'from_unit' => 'ms', 'to_unit' => 'hr'],
       ['value' => 1, 'from_unit' => 'week', 'to_unit' => 's']
   ];

   foreach ($testCases as $i => $inputs) {
       echo "Test Case " . ($i + 1) . ": Convert {$inputs['value']} {$inputs['from_unit']} → {$inputs['to_unit']}\n";
       $result = $ruleFlow->evaluate($config, $inputs);
       echo "Result: " . round($result['result'], 4) . " {$result['unit_name']}\n\n";
   }
}


// Run all examples
echo "Converter Examples\n";
echo "========================================================\n";

demo1_length_converter();
demo2_weight_converter();
demo3_temperature_converter();
demo4_area_converter();
demo5_volume_converter();
demo6_time_converter();

echo "\n✅ All examples completed!\n";