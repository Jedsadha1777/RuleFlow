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
- **Code Generation** — Generate optimized PHP functions from JSON
- **Deployment Ready** — Export as files, classes, or packages

## Quick Examples

### Simple BMI Calculator
```json
{
  "formulas": [
    {
      "id": "bmi",
      "formula": "weight / ((height / 100) ** 2)",
      "inputs": ["weight", "height"]
    },
    {
      "id": "category",
      "switch": "bmi",
      "when": [
        {"if": {"op": "<", "value": 18.5}, "result": "Underweight"},
        {"if": {"op": "between", "value": [18.5, 24.9]}, "result": "Normal"},
        {"if": {"op": ">=", "value": 25}, "result": "Overweight"}
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
      "scoring": {
        "ifs": {
          "vars": ["age", "income", "credit_score"],
          "tree": [
            {
              "if": {"op": "between", "value": [25, 45]},
              "ranges": [
                {
                  "if": {"op": ">=", "value": 50000},
                  "ranges": [
                    {
                      "if": {"op": ">=", "value": 700},
                      "score": 100,
                      "decision": "approved",
                      "set_vars": {"interest_rate": 4.5}
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

### PHP - Runtime Evaluation
```php
require_once './php/src/RuleFlow.php';

$engine = new RuleFlow();
$result = $engine->evaluate($config, $inputs);
```

### PHP - Code Generation
```php
// Generate optimized function
$calculator = $engine->generateFunction($config);
$result = $calculator($inputs);

// Or get PHP code as string
$phpCode = $engine->generateFunctionAsString($config);
```

## Configuration Format

### Formula Types

#### Expression
```json
{"id": "calc", "formula": "a + b", "inputs": ["a", "b"]}
```

#### Switch/Case
```json
{
  "id": "grade",
  "switch": "score", 
  "when": [
    {"if": {"op": ">=", "value": 80}, "result": "A"}
  ],
  "default": "F"
}
```

#### Scoring
```json
{
  "id": "points",
  "rules": [
    {
      "var": "performance",
      "ranges": [
        {"if": {"op": ">=", "value": 90}, "score": 10}
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
- **High Performance** — Pre-compiled rules with generated functions

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
- **Basic code generation (in-memory functions)**

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

### v1.2.0 (Current)
- Updated parameter names for better clarity
- Shorter, more intuitive syntax
- Consistent naming conventions
- All existing functionality preserved

### v1.1.0
- Multi-dimensional scoring
- Enhanced weight scoring
- Variable setting
- Advanced operators (`in`)
- Real-world examples

### v1.0.0
- Initial PHP implementation
- Basic expression evaluation
- Switch/case logic


## Author

Created by [Jedsadha Rojanaphan](https://github.com/Jedsadha1777)

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/Jedsadha1777/RuleFlow/issues)
- **PHP Docs**: [php/README.md](./php/README.md)

---

**RuleFlow** - Declarative business logic made simple.