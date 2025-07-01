# RuleFlow PHP

PHP implementation of the RuleFlow declarative rule engine.

## Requirements

- PHP 8.0 or higher
- No external dependencies required

## Installation

### Manual Installation (Recommended)

```bash
git clone https://github.com/Jedsadha1777/RuleFlow.git
cd RuleFlow/php
```

### Via Composer (Coming Soon)


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
            "inputs" => ["weight", "height"],
            "store_as" => "bmi_value"
        ],
        [
            "id" => "bmi_category",
            "switch_on" => "bmi_value",
            "cases" => [
                ["condition" => ["operator" => "<", "value" => 18.5], "result" => "Underweight"],
                ["condition" => ["operator" => "between", "value" => [18.5, 24.9]], "result" => "Normal"],
                ["condition" => ["operator" => ">=", "value" => 25], "result" => "Overweight"]
            ]
        ]
    ]
];

$inputs = [
    "weight" => 70,
    "height" => 175
];

$result = $engine->evaluate($config, $inputs);
print_r($result);
```

## API Reference

### RuleFlow Class

#### Methods

##### `evaluate(array $config, array $inputs): array`

Evaluates the configuration with provided inputs.

```php
$result = $engine->evaluate($config, $inputs);
```

**Parameters:**
- `$config` (array): JSON configuration array
- `$inputs` (array): Input values

**Returns:** Array with computed results

**Throws:** `Exception` on validation or execution errors

##### `validateConfig(array $config): array`

Validates configuration format and returns errors.

```php
$errors = $engine->validateConfig($config);
if (empty($errors)) {
    echo "Configuration is valid";
}
```

**Parameters:**
- `$config` (array): Configuration to validate

**Returns:** Array of error messages (empty if valid)

##### `testConfig(array $config, array $sampleInputs = []): array`

Comprehensive configuration testing with optional sample data.

```php
$testResult = $engine->testConfig($config, $sampleInputs);
```

**Parameters:**
- `$config` (array): Configuration to test
- `$sampleInputs` (array): Optional sample inputs for testing

**Returns:** Array with validation results:
```php
[
    'valid' => true|false,
    'errors' => [],
    'warnings' => [],
    'test_results' => []
]
```

## Configuration Examples

### Expression Formula

```php
$config = [
    "formulas" => [
        [
            "id" => "compound_interest",
            "expression" => "principal * ((1 + rate) ** years)",
            "inputs" => ["principal", "rate", "years"],
            "store_as" => "final_amount"
        ]
    ]
];
```

### Switch/Case Logic

```php
$config = [
    "formulas" => [
        [
            "id" => "grade_letter",
            "switch_on" => "score",
            "cases" => [
                ["condition" => ["operator" => ">=", "value" => 90], "result" => "A"],
                ["condition" => ["operator" => ">=", "value" => 80], "result" => "B"],
                ["condition" => ["operator" => ">=", "value" => 70], "result" => "C"],
                ["condition" => ["operator" => ">=", "value" => 60], "result" => "D"]
            ],
            "default" => "F"
        ]
    ]
];
```

### Scoring System

```php
$config = [
    "formulas" => [
        [
            "id" => "health_metric",
            "expression" => "exercise_hours * 2 + sleep_hours",
            "inputs" => ["exercise_hours", "sleep_hours"],
            "weight_score" => [
                "condition" => ["operator" => ">=", "value" => 20],
                "score" => 100
            ]
        ]
    ]
];
```

## Supported Features

### Mathematical Functions

| Function | Description | Example |
|----------|-------------|---------|
| `abs(x)` | Absolute value | `abs(-5)` → `5` |
| `min(a,b,c)` | Minimum | `min(1,2,3)` → `1` |
| `max(a,b,c)` | Maximum | `max(1,2,3)` → `3` |
| `sqrt(x)` | Square root | `sqrt(25)` → `5` |
| `round(x,n)` | Round to n decimals | `round(3.14159, 2)` → `3.14` |
| `ceil(x)` | Round up | `ceil(3.1)` → `4` |
| `floor(x)` | Round down | `floor(3.9)` → `3` |

### Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `+` `-` `*` `/` | Basic arithmetic | `2 + 3 * 4` |
| `**` | Exponentiation | `2 ** 3` → `8` |
| `()` | Grouping | `(2 + 3) * 4` |

### Conditional Operators

| Operator | Description | JSON Example |
|----------|-------------|--------------|
| `<` | Less than | `{"operator": "<", "value": 18}` |
| `<=` | Less than or equal | `{"operator": "<=", "value": 25}` |
| `>` | Greater than | `{"operator": ">", "value": 65}` |
| `>=` | Greater than or equal | `{"operator": ">=", "value": 18}` |
| `==` | Equal to | `{"operator": "==", "value": 1}` |
| `!=` | Not equal to | `{"operator": "!=", "value": 0}` |
| `between` | Range check | `{"operator": "between", "value": [18, 65]}` |

### Error Handling

### Common Exceptions

```php
try {
    $result = $engine->evaluate($config, $inputs);
} catch (Exception $e) {
    switch (true) {
        case strpos($e->getMessage(), 'Missing input') !== false:
            // Handle missing input variable
            break;
        case strpos($e->getMessage(), 'Division by zero') !== false:
            // Handle division by zero
            break;
        case strpos($e->getMessage(), 'Invalid configuration') !== false:
            // Handle configuration errors
            break;
        default:
            // Handle other errors
            break;
    }
}
```

### Validation Before Execution

```php
$errors = $engine->validateConfig($config);
if (!empty($errors)) {
    throw new Exception('Configuration invalid: ' . implode(', ', $errors));
}

$result = $engine->evaluate($config, $inputs);
```

## Testing

### Running Examples

```bash
cd php
php examples/demo.php
```

### Example Structure

```bash
php/
├── src/RuleFlow.php
└── examples/
    └── demo.php
```

### Writing Tests

```php
<?php
use PHPUnit\Framework\TestCase;

class CustomRuleTest extends TestCase
{
    private $engine;

    protected function setUp(): void
    {
        $this->engine = new RuleFlow();
    }

    public function testCustomRule(): void
    {
        $config = [
            "formulas" => [
                [
                    "id" => "test_formula",
                    "expression" => "a + b",
                    "inputs" => ["a", "b"]
                ]
            ]
        ];

        $inputs = ["a" => 2, "b" => 3];
        $result = $this->engine->evaluate($config, $inputs);

        $this->assertEquals(5, $result['test_formula']);
    }
}
```

## Performance Optimization

### Best Practices

1. **Order Dependencies**: Arrange formulas in dependency order
```php
// Good - dependencies in order
$formulas = [
    ["id" => "base", "expression" => "a + b"],
    ["id" => "derived", "expression" => "base * 2"]
];
```

2. **Avoid Complex Expressions**: Break into smaller parts
```php
// Instead of complex single expression
["expression" => "((a + b) * c - d) / ((e + f) * g)"]

// Use multiple simpler expressions
[
    ["id" => "sum1", "expression" => "a + b"],
    ["id" => "sum2", "expression" => "e + f"],
    ["id" => "numerator", "expression" => "sum1 * c - d"],
    ["id" => "denominator", "expression" => "sum2 * g"],
    ["id" => "result", "expression" => "numerator / denominator"]
]
```

3. **Validate Inputs**: Pre-validate inputs to avoid runtime errors
```php
function validateInputs($inputs, $required) {
    foreach ($required as $field) {
        if (!isset($inputs[$field]) || !is_numeric($inputs[$field])) {
            throw new InvalidArgumentException("Invalid input: $field");
        }
    }
}
```

## Examples

### Healthcare Risk Assessment

```php
$healthConfig = [
    "formulas" => [
        [
            "id" => "bmi",
            "expression" => "round(weight / ((height / 100) ** 2), 2)",
            "inputs" => ["weight", "height"],
            "store_as" => "bmi_value"
        ],
        [
            "id" => "age_risk",
            "switch_on" => "age",
            "cases" => [
                ["condition" => ["operator" => "<", "value" => 40], "result" => "Low"],
                ["condition" => ["operator" => "between", "value" => [40, 60]], "result" => "Medium"],
                ["condition" => ["operator" => ">", "value" => 60], "result" => "High"]
            ]
        ],
        [
            "id" => "overall_risk",
            "expression" => "bmi_risk_score + age_risk_score + lifestyle_score",
            "inputs" => ["bmi_risk_score", "age_risk_score", "lifestyle_score"]
        ]
    ]
];
```

### Financial Loan Approval

```php
$loanConfig = [
    "formulas" => [
        [
            "id" => "debt_to_income",
            "expression" => "round((monthly_debt / monthly_income) * 100, 2)",
            "inputs" => ["monthly_debt", "monthly_income"],
            "store_as" => "dti_ratio"
        ],
        [
            "id" => "credit_score_category",
            "switch_on" => "credit_score",
            "cases" => [
                ["condition" => ["operator" => ">=", "value" => 750], "result" => "Excellent"],
                ["condition" => ["operator" => ">=", "value" => 700], "result" => "Good"],
                ["condition" => ["operator" => ">=", "value" => 650], "result" => "Fair"],
                ["condition" => ["operator" => ">=", "value" => 600], "result" => "Poor"]
            ],
            "default" => "Very Poor"
        ],
        [
            "id" => "loan_approval",
            "switch_on" => "combined_score",
            "cases" => [
                ["condition" => ["operator" => ">=", "value" => 80], "result" => "Approved"],
                ["condition" => ["operator" => ">=", "value" => 60], "result" => "Manual Review"]
            ],
            "default" => "Declined"
        ]
    ]
];
```

## Security Considerations

### Input Validation

RuleFlow automatically validates expressions but you should validate user inputs:

```php
function sanitizeInputs(array $inputs): array {
    $sanitized = [];
    foreach ($inputs as $key => $value) {
        if (is_numeric($value)) {
            $sanitized[$key] = (float)$value;
        } else {
            throw new InvalidArgumentException("Non-numeric input: $key");
        }
    }
    return $sanitized;
}

$safeInputs = sanitizeInputs($userInputs);
$result = $engine->evaluate($config, $safeInputs);
```

### Configuration Security

- Never execute user-provided configurations without validation
- Store configurations in version control
- Use configuration validation in CI/CD pipelines

```php
// Validate configuration before deployment
$errors = $engine->validateConfig($config);
if (!empty($errors)) {
    throw new Exception('Invalid configuration detected');
}
```

## Changelog

### Version 1.0.0
- Initial PHP implementation
- Expression evaluation with mathematical functions
- Switch/case conditional logic
- Scoring system with weight-based calculations
- Comprehensive validation and testing
- PSR-12 compliant codebase

## Contributing

1. Fork the repository
2. Create a feature branch
3. Write tests for new functionality
4. Ensure all tests pass
5. Submit a pull request

### Development Setup

```bash
git clone https://github.com/Jedsadha1777/RuleFlow.git
cd RuleFlow/php
php examples/demo.php
```

## License

MIT License - see [LICENSE](../LICENSE) file for details.

## Support

- **Main Documentation**: [../README.md](../README.md)
- **PHP Issues**: [GitHub Issues](https://github.com/Jedsadha1777/RuleFlow/issues)

---

**RuleFlow PHP** - Bringing declarative business logic to PHP applications.