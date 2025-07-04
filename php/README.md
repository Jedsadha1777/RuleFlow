# RuleFlow PHP

A declarative rule engine for evaluating business logic from JSON configuration with multi-dimensional scoring and $ notation support.

## Requirements

- PHP 8.0 or higher
- No external dependencies

## Installation

```bash
git clone https://github.com/Jedsadha1777/RuleFlow.git
cd RuleFlow/php
```

## Quick Start

```php
<?php
require_once 'src/RuleFlow.php';

$engine = new RuleFlow();

$config = [
    "formulas" => [
        [
            "id" => "bmi",
            "formula" => "round((weight / ((height / 100) ** 2)), 2)",
            "inputs" => ["weight", "height"],
            "as" => "$bmi_value"
        ],
        [
            "id" => "category",
            "switch" => "$bmi_value",
            "when" => [
                ["if" => ["op" => "<", "value" => 18.5], "result" => "Underweight"],
                ["if" => ["op" => "between", "value" => [18.5, 24.9]], "result" => "Normal"],
                ["if" => ["op" => ">=", "value" => 25], "result" => "Overweight"]
            ]
        ]
    ]
];

$result = $engine->evaluate($config, ["weight" => 70, "height" => 175]);
// Result: ["weight" => 70, "height" => 175, "bmi_value" => 22.86, "category" => "Normal"]
```

## API Reference

### Main Methods

```php
// Evaluate configuration
$result = $engine->evaluate($config, $inputs);

// Validate configuration
$errors = $engine->validateConfig($config);

// Test with sample data
$testResult = $engine->testConfig($config, $sampleInputs);
```

### Code Generation Methods

```php
// Generate PHP code as string
$code = $engine->generateFunctionAsString($config);
echo $code; // Shows complete optimized PHP function
```

## Configuration Types

### 1. Expression Formula with $ Notation
```json
{
  "id": "calculation",
  "formula": "a + b * 2",
  "inputs": ["a", "b"],
  "as": "$result"
}
```

### 2. Switch/Case Logic with $ Variables
```json
{
  "id": "grade",
  "switch": "$score",
  "when": [
    {
      "if": {"op": ">=", "value": 80}, 
      "result": "A",
      "set_vars": {"$gpa": 4.0}
    },
    {
      "if": {"op": ">=", "value": 70}, 
      "result": "B",
      "set_vars": {"$gpa": 3.0}
    }
  ],
  "default": "F",
  "default_vars": {"$gpa": 0.0}
}
```

### 3. Accumulative Scoring System
```json
{
  "id": "credit_score",
  "rules": [
    {
      "var": "income",
      "ranges": [
        {"if": {"op": ">=", "value": 50000}, "score": 25},
        {"if": {"op": ">=", "value": 30000}, "score": 15}
      ]
    },
    {
      "var": "has_property",
      "if": {"op": "==", "value": 1},
      "score": 20
    }
  ]
}
```

### 4. Multi-Dimensional Scoring with $ Variables
```json
{
  "id": "complex_scoring",
  "scoring": {
    "ifs": {
      "vars": ["$risk_score", "$trend_ratio"],
      "tree": [
        {
          "if": {"op": ">=", "value": 50},
          "ranges": [
            {
              "if": {"op": ">=", "value": 2},
              "score": 0,
              "decision": "EMERGENCY_STOP",
              "set_vars": {
                "$alert_level": "critical",
                "$action": "close_all_positions"
              }
            }
          ]
        }
      ]
    }
  }
}
```

## Advanced Features

### $ Notation Support

RuleFlow supports enhanced variable referencing with $ notation:

#### Variable Storage
```json
{
  "id": "price_calculation",
  "formula": "cost * markup",
  "inputs": ["cost", "markup"],
  "as": "$base_price"
}
```

#### Variable References
```json
{
  "id": "final_price",
  "switch": "demand_level",
  "when": [
    {
      "if": {"op": "==", "value": "high"},
      "result": "surge_pricing",
      "set_vars": {
        "$multiplier": 1.5,
        "$final_amount": "$base_price * $multiplier"
      }
    }
  ]
}
```

#### Expression Variables
```json
{
  "set_vars": {
    "$discount": "$base_price * 0.1",
    "$tax": "($base_price - $discount) * 0.07",
    "$total": "$base_price - $discount + $tax"
  }
}
```

## Supported Features

### Operators
- **Math**: `+`, `-`, `*`, `/`, `**` (power)
- **Comparison**: `<`, `<=`, `>`, `>=`, `==`, `!=`
- **Range**: `between` → `{"op": "between", "value": [10, 20]}`
- **Array**: `in` → `{"op": "in", "value": ["red", "blue"]}`

### Functions
- `abs(x)`, `min(a,b,c)`, `max(a,b,c)`
- `sqrt(x)`, `round(x,n)`, `ceil(x)`, `floor(x)`

### Variable Setting with $ Notation
```json
"set_vars": {
  "$status": "approved", 
  "$rate": 5.5,
  "$calculated_amount": "$base * $rate"
}
```

## Code Generation Features

RuleFlow can generate optimized PHP code from your JSON configurations:

### PHP Code as String
```php
// Get the generated code
$phpCode = $engine->generateFunctionAsString($config);
echo $phpCode;
/*
function(array $inputs): array {
    $context = $inputs;
    
    // Formula: bmi_calculation
    if (!isset($context['weight'])) {
        throw new Exception('Missing input: weight');
    }
    if (!isset($context['height'])) {
        throw new Exception('Missing input: height');
    }
    $context['bmi_value'] = $context['weight'] / pow($context['height'], 2);
    
    // Formula: category
    $switchValue = $context['bmi_value'] ?? null;
    if ($switchValue === null) {
        throw new Exception('Switch value \'$bmi_value\' not found');
    }
    if ($switchValue < 18.5) {
        $context['category'] = 'Underweight';
    } elseif ($switchValue >= 18.5 && $switchValue <= 24.9) {
        $context['category'] = 'Normal';
    } elseif ($switchValue >= 25) {
        $context['category'] = 'Overweight';
    }
    
    return $context;
}
*/
```

### Function Generation Features
- **Dependency Optimization**: Automatically orders formula execution
- **Input Validation**: Checks for required inputs
- **Error Handling**: Comprehensive error messages
- **Type Safety**: Proper type checking and conversion
- **Performance**: Optimized code without runtime overhead

## Examples

### Basic Usage Examples

### Credit Scoring System
```php
$config = [
    "formulas" => [
        [
            "id" => "credit_assessment",
            "rules" => [
                [
                    "var" => "income",
                    "ranges" => [
                        ["if" => ["op" => ">=", "value" => 100000], "score" => 40],
                        ["if" => ["op" => ">=", "value" => 50000], "score" => 25]
                    ]
                ],
                [
                    "var" => "employment_years", 
                    "ranges" => [
                        ["if" => ["op" => ">=", "value" => 5], "score" => 20]
                    ]
                ]
            ]
        ],
        [
            "id" => "decision",
            "switch" => "credit_assessment",
            "when" => [
                [
                    "if" => ["op" => ">=", "value" => 60],
                    "result" => "Approved",
                    "set_vars" => ["$interest_rate" => 5.5]
                ]
            ],
            "default" => "Rejected"
        ]
    ]
];

$result = $engine->evaluate($config, [
    "income" => 75000,
    "employment_years" => 8
]);
// Result: credit_assessment = 45, decision = "Rejected"
```

### Healthcare Assessment with $ Variables
```php
$config = [
    "formulas" => [
        [
            "id" => "bmi_calc",
            "formula" => "weight / ((height / 100) ** 2)",
            "inputs" => ["weight", "height"],
            "as" => "$bmi"
        ],
        [
            "id" => "risk_assessment",
            "scoring" => [
                "ifs" => [
                    "vars" => ["age", "$bmi"],
                    "tree" => [
                        [
                            "if" => ["op" => ">", "value" => 50],
                            "ranges" => [
                                [
                                    "if" => ["op" => ">=", "value" => 25],
                                    "score" => 8,
                                    "risk_level" => "high",
                                    "set_vars" => {"$requires_consultation" => true}
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];
```

### Dynamic Pricing with Complex $ Expressions
```php
$config = [
    "formulas" => [
        [
            "id" => "base_price_calc",
            "formula" => "cost * markup_multiplier",
            "inputs" => ["cost", "markup_multiplier"],
            "as" => "$base_price"
        ],
        [
            "id" => "demand_adjustment",
            "switch" => "demand_level",
            "when" => [
                [
                    "if" => ["op" => "==", "value" => "high"],
                    "result" => "surge",
                    "set_vars" => [
                        "$demand_multiplier" => 1.2,
                        "$priority_fee" => "$base_price * 0.1"
                    ]
                ]
            ],
            "default" => "normal",
            "default_vars" => [
                "$demand_multiplier" => 1.0,
                "$priority_fee" => 0
            ]
        ],
        [
            "id" => "final_price_calc",
            "formula" => "(base_price * demand_multiplier) + priority_fee",
            "inputs" => ["base_price", "demand_multiplier", "priority_fee"],
            "as" => "$final_price"
        ]
    ]
];
```

## Comprehensive Examples

The PHP implementation includes three main example files:

### demo.php - Basic Functionality
Run with: `php examples/demo.php`

1. **BMI Calculator** — Basic expressions and categorization
2. **Credit Scoring** — Accumulative scoring system
3. **Blood Pressure Assessment** — Multi-dimensional health evaluation
4. **E-commerce Discounts** — Dynamic pricing and customer tiers
5. **Academic Grading** — Weighted scoring with bonuses
6. **Dynamic Pricing** — Complex pricing with $ variables

### demo-converter.php - Unit Conversion Systems
Run with: `php examples/demo-converter.php`

1. **Length Converter** — mm, cm, m, km, inch, ft, yard, mile
2. **Weight Converter** — mg, g, kg, ton, oz, lb
3. **Temperature Converter** — Celsius, Fahrenheit, Kelvin, Rankine
4. **Area Converter** — Square units and land measurements
5. **Volume Converter** — Metric and imperial liquid measurements
6. **Time Converter** — ms, seconds, minutes, hours, days, weeks

### demo-futures-analysis.php - Advanced Trading Systems
Run with: `php examples/demo-futures-analysis.php`

1. **Portfolio Stop Loss** — Risk management with asset-specific thresholds
2. **Market Trend Analysis** — Technical indicator evaluation
3. **Grid Position Management** — Automated position sizing
4. **Performance Evaluation** — Win rate and profit analysis
5. **Complete Trading Flow** — Integrated decision-making system

## Testing

```bash
# Run all examples
php examples/demo.php
php examples/demo-converter.php
php examples/demo-futures-analysis.php

# Or run individual examples
cd examples && php demo.php
```

## Error Handling

```php
try {
    $errors = $engine->validateConfig($config);
    if (!empty($errors)) {
        throw new Exception('Invalid config: ' . implode(', ', $errors));
    }
    
    $result = $engine->evaluate($config, $inputs);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Validation Features

```php
// Validate configuration
$errors = $engine->validateConfig($config);
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "Config Error: $error\n";
    }
}

// Test configuration with sample data
$testResult = $engine->testConfig($config, $sampleInputs);
if (!$testResult['valid']) {
    echo "Test Failed: " . implode(', ', $testResult['errors']);
}

// Check for warnings
if (!empty($testResult['warnings'])) {
    foreach ($testResult['warnings'] as $warning) {
        echo "Warning: $warning\n";
    }
}
```

## Performance Considerations

- **Code Generation**: Use `generateFunctionAsString()` for production environments
- **Validation**: Validate configurations during development, not runtime
- **Dependencies**: Automatic formula ordering for optimal execution
- **Memory**: Minimal overhead with efficient context management
- **Safety**: No eval() usage, custom secure expression parser

## Best Practices

### Configuration Design
- Use descriptive formula IDs
- Group related calculations
- Utilize $ notation for internal variables
- Set clear default values
- Include comprehensive validation

### Variable Naming
- Use `$` prefix for calculated/internal variables
- Keep input variable names simple and clear
- Use consistent naming conventions
- Document complex expressions

### Performance Optimization
- Order formulas by dependency
- Use code generation for production
- Minimize complex nested scoring
- Cache frequently used configurations

## License

MIT License - see [LICENSE](../LICENSE) file for details.