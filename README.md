# RuleFlow

A declarative rule engine for complex business logic evaluation from JSON configuration.

## Supported Languages

- ✅ **PHP** — [Documentation](./php/README.md)
- ⏳ **Go** — coming soon

## Key Features

- **JSON Configuration** — Define rules without code changes
- **Multi-Dimensional Scoring** — Navigate unlimited condition levels
- **Mathematical Expressions** — Variables, functions, operators
- **Conditional Logic** — Switch/case with variable setting
- **Secure Evaluation** — No eval(), safe expression parsing

## Quick Examples

### Simple BMI Calculator
```json
{
  "formulas": [
    {
      "id": "bmi",
      "expression": "weight / ((height / 100) ** 2)",
      "inputs": ["weight", "height"]
    },
    {
      "id": "category",
      "switch_on": "bmi",
      "cases": [
        {"condition": {"operator": "<", "value": 18.5}, "result": "Underweight"},
        {"condition": {"operator": "between", "value": [18.5, 24.9]}, "result": "Normal"},
        {"condition": {"operator": ">=", "value": 25}, "result": "Overweight"}
      ]
    }
  ]
}
```

### Multi-Dimensional Scoring
```json
{
  "formulas": [
    {
      "id": "loan_approval",
      "weight_score": {
        "multi_condition": {
          "variables": ["age", "income", "credit_score"],
          "score_matrix": [
            {
              "condition": {"operator": "between", "value": [25, 45]},
              "ranges": [
                {
                  "condition": {"operator": ">=", "value": 50000},
                  "ranges": [
                    {
                      "condition": {"operator": ">=", "value": 700},
                      "score": 100,
                      "decision": "approved",
                      "set_variables": {"interest_rate": 4.5}
                    }
                  ]
                }
              ]
            }
          ]
        }
      }
    }
  ]
}
```

## Quick Start

### PHP
```php
require_once './php/src/RuleFlow.php';

$engine = new RuleFlow();
$result = $engine->evaluate($config, $inputs);
```

## Configuration Format

### Formula Types

#### Expression
```json
{"id": "calc", "expression": "a + b", "inputs": ["a", "b"]}
```

#### Switch/Case
```json
{
  "id": "grade",
  "switch_on": "score", 
  "cases": [
    {"condition": {"operator": ">=", "value": 80}, "result": "A"}
  ],
  "default": "F"
}
```

#### Scoring
```json
{
  "id": "points",
  "score_rules": [
    {
      "variable": "performance",
      "ranges": [
        {"condition": {"operator": ">=", "value": 90}, "score": 10}
      ]
    }
  ]
}
```

## Operators & Functions

### Operators
- **Math**: `+`, `-`, `*`, `/`, `**`
- **Comparison**: `<`, `<=`, `>`, `>=`, `==`, `!=`
- **Special**: `between`, `in`

### Functions
- `abs(x)`, `min(a,b)`, `max(a,b)`
- `sqrt(x)`, `round(x,n)`, `ceil(x)`, `floor(x)`

## Use Cases

- **Healthcare** — Risk assessment, BMI calculations
- **Finance** — Credit scoring, loan approval
- **E-commerce** — Dynamic pricing, customer tiers
- **Business Rules** — Decision workflows
- **Gaming** — Achievement systems

## Examples

The PHP implementation includes 5 comprehensive demos:

1. **BMI Calculator** — Basic expressions
2. **Credit Scoring** — Accumulative scoring
3. **Blood Pressure** — Multi-dimensional health assessment
4. **E-commerce Discounts** — Dynamic pricing
5. **Academic Grading** — Weighted scoring

```bash
cd php && php examples/demo.php
```

## Contributing

### Adding a New Language
1. Implement core `RuleFlow` class
2. Support all formula types
3. Add comprehensive tests
4. Follow language conventions

### Required Features
- Expression evaluation
- Switch/case logic
- Multi-dimensional scoring
- Variable setting
- All operators and functions
- Comprehensive validation

## Project Structure

```
ruleflow/
├── README.md
├── LICENSE
└── php/
    ├── README.md
    ├── src/RuleFlow.php
    └── examples/demo.php
```

## Version History

### v1.1.0 (Current)
- Multi-dimensional scoring
- Enhanced weight scoring
- Variable setting
- Advanced operators (`in`)
- Real-world examples

### v1.0.0
- Initial PHP implementation
- Basic expression evaluation
- Switch/case logic

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/Jedsadha1777/RuleFlow/issues)
- **PHP Docs**: [php/README.md](./php/README.md)

---

**RuleFlow** - Declarative business logic made simple.