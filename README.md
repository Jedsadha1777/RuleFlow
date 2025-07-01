# RuleFlow

A declarative, multi-language rule engine for evaluating business logic from JSON configuration.

## Supported Languages

- ✅ **PHP** — [See PHP implementation](./php/README.md)
- ⏳ **Go** — coming soon

## Key Features

- **Declarative Configuration** — Define business rules in JSON format
- **Expression Evaluation** — Mathematical formulas with variables and functions
- **Conditional Logic** — Switch/case statements for decision-making
- **Scoring System** — Weight-based scoring mechanisms
- **Safe Evaluation** — No eval() functions, secure expression parsing
- **Multi-language Support** — Consistent API across different programming languages

## Quick Example

```json
{
  "formulas": [
    {
      "id": "bmi",
      "expression": "round((weight / ((height / 100) ** 2)), 2)",
      "inputs": ["weight", "height"],
      "store_as": "bmi_value"
    },
    {
      "id": "bmi_category",
      "switch_on": "bmi_value",
      "cases": [
        {"condition": {"operator": "<", "value": 18.5}, "result": "Underweight"},
        {"condition": {"operator": "between", "value": [18.5, 24.9]}, "result": "Normal"},
        {"condition": {"operator": ">=", "value": 25}, "result": "Overweight"}
      ]
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

### JavaScript (Coming Soon)
```javascript
const { RuleFlow } = require('@ruleflow/engine');

const engine = new RuleFlow();
const result = engine.evaluate(config, inputs);
```

## Use Cases

- **Health Assessment Systems** — BMI calculations, risk scoring
- **Insurance Premium Calculators** — Dynamic pricing based on risk factors
- **Financial Credit Scoring** — Multi-factor credit evaluation
- **Business Rule Engines** — Complex decision-making workflows
- **Configuration-Driven Applications** — Rules without code deployment

## Language-Specific Documentation

| Language | Status | Documentation | Installation |
|----------|--------|---------------|--------------|
| PHP | ✅ Ready | [php/README.md](./php/README.md) | Manual download |
| JavaScript | ⏳ Development | Coming soon | `npm install @ruleflow/engine` |
| Python | ⏳ Planned | Coming soon | `pip install ruleflow` |
| Go | ⏳ Planned | Coming soon | `go get github.com/ruleflow/engine` |

## Project Structure

```
ruleflow/
├── README.md                 # This file - main documentation
├── LICENSE
└── php/
    ├── README.md            # PHP-specific documentation
    ├── src/RuleFlow.php     # PHP implementation
    ├── tests/               # PHP unit tests
    └── examples/            # PHP examples
```

## Configuration Format

RuleFlow uses a standardized JSON configuration format across all languages:

```json
{
  "inputs": {
    "variable_name": {
      "label": "Display Name",
      "unit": "Unit"
    }
  },
  "formulas": [
    {
      "id": "unique_id",
      "expression": "mathematical_expression",
      "inputs": ["input_variables"],
      "store_as": "result_variable"
    },
    {
      "id": "conditional_logic",
      "switch_on": "variable_to_evaluate",
      "cases": [
        {
          "condition": {"operator": ">=", "value": 18},
          "result": "Adult"
        }
      ],
      "default": "Minor"
    }
  ]
}
```

## Supported Operations

### Mathematical Operators
- `+`, `-`, `*`, `/`, `**` (exponentiation)
- Parentheses for operation precedence

### Functions
- `abs(x)`, `min(a,b,c)`, `max(a,b,c)`
- `sqrt(x)`, `round(x,n)`, `ceil(x)`, `floor(x)`

### Conditional Operators
- `<`, `<=`, `>`, `>=`, `==`, `!=`
- `between` for range checking

## Contributing

We welcome contributions in all supported languages!

### Adding a New Language

1. Create a new directory: `/{language}/`
2. Implement the core `RuleFlow` class following the PHP example
3. Add comprehensive tests
4. Create language-specific README.md
5. Update this main README.md

### Development Guidelines

- Maintain consistent API across languages
- Follow each language's best practices and conventions
- Ensure comprehensive test coverage
- Document all public methods and configuration options

## Testing

Each language implementation includes its own examples:

```bash
# PHP
cd php && php examples/demo.php
```

## Examples

Find examples in each language-specific directory under `examples/`.

## Roadmap

- [x] PHP implementation
- [ ] Go implementation

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/Jedsadha1777/RuleFlow/issues)
- **Discussions**: [GitHub Discussions](https://github.com/Jedsadha1777/RuleFlow/discussions)

---

**RuleFlow** - Making business logic declarative, testable, and maintainable across all platforms.