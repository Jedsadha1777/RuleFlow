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
            "expression" => "round((weight / ((height / 100) ** 2)), 2)",
            "inputs" => ["weight", "height"]
        ],
        [
            "id" => "category",
            "switch_on" => "bmi",
            "cases" => [
                ["condition" => ["operator" => "<", "value" => 18.5], "result" => "Underweight"],
                ["condition" => ["operator" => "between", "value" => [18.5, 24.9]], "result" => "Normal"],
                ["condition" => ["operator" => ">=", "value" => 25], "result" => "Overweight"]
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

## Configuration Types

### 1. Expression Formula
```json
{
  "id": "calculation",
  "expression" => "a + b * 2",
  "inputs" => ["a", "b"],
  "store_as" => "result"
}
```

### 2. Switch/Case Logic
```json
{
  "id" => "grade",
  "switch_on" => "score",
  "cases" => [
    {"condition" => {"operator" => ">=", "value" => 80}, "result" => "A"},
    {"condition" => {"operator" => ">=", "value" => 70}, "result" => "B"}
  ],
  "default" => "F"
}
```

### 3. Scoring System
```json
{
  "id" => "credit_score",
  "score_rules" => [
    {
      "variable" => "income",
      "ranges" => [
        {"condition" => {"operator" => ">=", "value" => 50000}, "score" => 25},
        {"condition" => {"operator" => ">=", "value" => 30000}, "score" => 15}
      ]
    }
  ]
}
```

### 4. Multi-Dimensional Scoring
```json
{
  "id" => "complex_scoring",
  "weight_score" => {
    "multi_condition" => {
      "variables" => ["age", "income"],
      "score_matrix" => [
        {
          "condition" => {"operator" => "between", "value" => [25, 45]},
          "ranges" => [
            {
              "condition" => {"operator" => ">=", "value" => 50000},
              "score" => 100,
              "set_variables" => {"approved" => true}
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
- **Range**: `between` → `{"operator": "between", "value": [10, 20]}`
- **Array**: `in` → `{"operator": "in", "value": ["red", "blue"]}`

### Functions
- `abs(x)`, `min(a,b,c)`, `max(a,b,c)`
- `sqrt(x)`, `round(x,n)`, `ceil(x)`, `floor(x)`

### Variable Setting
```json
"set_variables" => {"status" => "approved", "rate" => 5.5}
```

## Examples

### Credit Scoring
```php
$config = [
    "formulas" => [
        [
            "id" => "credit_assessment",
            "score_rules" => [
                [
                    "variable" => "income",
                    "ranges" => [
                        ["condition" => ["operator" => ">=", "value" => 100000], "score" => 40],
                        ["condition" => ["operator" => ">=", "value" => 50000], "score" => 25]
                    ]
                ],
                [
                    "variable" => "employment_years", 
                    "ranges" => [
                        ["condition" => ["operator" => ">=", "value" => 5], "score" => 20]
                    ]
                ]
            ]
        ],
        [
            "id" => "decision",
            "switch_on" => "credit_assessment_score",
            "cases" => [
                [
                    "condition" => ["operator" => ">=", "value" => 60],
                    "result" => "Approved",
                    "set_variables" => ["interest_rate" => 5.5]
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
            "expression" => "weight / ((height / 100) ** 2)",
            "inputs" => ["weight", "height"]
        ],
        [
            "id" => "risk_score",
            "weight_score" => [
                "multi_condition" => [
                    "variables" => ["age", "bmi"],
                    "score_matrix" => [
                        [
                            "condition" => ["operator" => ">", "value" => 50],
                            "ranges" => [
                                [
                                    "condition" => ["operator" => ">=", "value" => 25],
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