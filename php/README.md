# RuleFlow PHP

A declarative rule engine for evaluating business logic from JSON configuration with multi-dimensional scoring.

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
            "inputs" => ["weight", "height"]
        ],
        [
            "id" => "category",
            "switch" => "bmi",
            "when" => [
                ["if" => ["op" => "<", "value" => 18.5], "result" => "Underweight"],
                ["if" => ["op" => "between", "value" => [18.5, 24.9]], "result" => "Normal"],
                ["if" => ["op" => ">=", "value" => 25], "result" => "Overweight"]
            ]
        ]
    ]
];

$result = $engine->evaluate($config, ["weight" => 70, "height" => 175]);
// Result: ["weight" => 70, "height" => 175, "bmi" => 22.86, "category" => "Normal"]
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
// Generate executable function in memory
$func = $engine->generateFunction($config);
$result = $func($inputs);

// Generate PHP code as string
$code = $engine->generateFunctionAsString($config);
echo $code; // Shows complete PHP function
```

## Configuration Types

### 1. Expression Formula
```json
{
  "id": "calculation",
  "formula": "a + b * 2",
  "inputs": ["a", "b"],
  "as": "result"
}
```

### 2. Switch/Case Logic
```json
{
  "id": "grade",
  "switch": "score",
  "when": [
    {"if": {"op": ">=", "value": 80}, "result": "A"},
    {"if": {"op": ">=", "value": 70}, "result": "B"}
  ],
  "default": "F"
}
```

### 3. Scoring System
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
    }
  ]
}
```

### 4. Multi-Dimensional Scoring
```json
{
  "id": "complex_scoring",
  "scoring": {
    "ifs": {
      "vars": ["age", "income"],
      "tree": [
        {
          "if": {"op": "between", "value": [25, 45]},
          "ranges": [
            {
              "if": {"op": ">=", "value": 50000},
              "score": 100,
              "set_vars": {"approved": true}
            }
          ]
        }
      ]
    }
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

### Variable Setting
```json
"set_vars": {"status": "approved", "rate": 5.5}
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
    $context['bmi'] = $context['weight'] / pow($context['height'], 2);
    
    return $context;
}
*/
```

## Examples

### Basic Usage Examples

### Credit Scoring
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
            "switch" => "credit_assessment_score",
            "when" => [
                [
                    "if" => ["op" => ">=", "value" => 60],
                    "result" => "Approved",
                    "set_vars" => ["interest_rate" => 5.5]
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
// Result: credit_assessment_score = 45, decision = "Rejected"
```

### Healthcare Assessment
```php
$config = [
    "formulas" => [
        [
            "id" => "bmi",
            "formula" => "weight / ((height / 100) ** 2)",
            "inputs" => ["weight", "height"]
        ],
        [
            "id" => "risk_score",
            "scoring" => [
                "ifs" => [
                    "vars" => ["age", "bmi"],
                    "tree" => [
                        [
                            "if" => ["op" => ">", "value" => 50],
                            "ranges" => [
                                [
                                    "if" => ["op" => ">=", "value" => 25],
                                    "score" => 8,
                                    "risk_level" => "high"
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

## Testing

```bash
# Run examples
php examples/demo.php
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

## License

MIT License - see [LICENSE](../LICENSE) file for details.