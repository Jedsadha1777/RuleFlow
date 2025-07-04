# RuleFlow

A declarative rule engine for complex business logic evaluation from JSON configuration.

## Supported Languages

- **PHP** — [Documentation](./php/README.md)
- **Go** — Coming soon

## Key Features

- **JSON Configuration** — Define rules without code changes
- **Multi-Dimensional Scoring** — Navigate unlimited condition levels
- **Mathematical Expressions** — Variables, functions, operators
- **Conditional Logic** — Switch/case with variable setting
- **Secure Evaluation** — No eval(), safe expression parsing
- **Code Generation** — Generate optimized PHP functions from JSON
- **$ Notation Support** — Enhanced variable referencing with dollar notation

## Quick Examples

### Simple BMI Calculator
```json
{
  "formulas": [
    {
      "id": "bmi",
      "formula": "weight / ((height / 100) ** 2)",
      "inputs": ["weight", "height"],
      "as": "$bmi_result"
    },
    {
      "id": "category",
      "switch": "$bmi_result",
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
                      "set_vars": {"$interest_rate": 4.5}
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

### Dynamic Pricing with $ Variables
```json
{
  "formulas": [
    {
      "id": "base_price_calc",
      "formula": "cost * markup_multiplier",
      "inputs": ["cost", "markup_multiplier"],
      "as": "$base_price"
    },
    {
      "id": "demand_adjustment",
      "switch": "demand_level",
      "when": [
        {
          "if": {"op": "==", "value": "high"},
          "result": "surge",
          "set_vars": {
            "$demand_multiplier": 1.2,
            "$priority_fee": "$base_price * 0.1"
          }
        }
      ],
      "default": "normal",
      "default_vars": {"$demand_multiplier": 1.0}
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
// Get PHP code as string
$phpCode = $engine->generateFunctionAsString($config);
echo $phpCode; // Shows complete optimized PHP function
```

## Configuration Format

### Formula Types

#### Expression Formula
```json
{
  "id": "calculation",
  "formula": "a + b * 2",
  "inputs": ["a", "b"],
  "as": "$result"
}
```

#### Switch/Case Logic
```json
{
  "id": "grade",
  "switch": "$score", 
  "when": [
    {"if": {"op": ">=", "value": 80}, "result": "A"}
  ],
  "default": "F"
}
```

#### Accumulative Scoring
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

#### Multi-Dimensional Scoring
```json
{
  "id": "complex_scoring",
  "scoring": {
    "ifs": {
      "vars": ["$var1", "$var2"],
      "tree": [
        {
          "if": {"op": ">=", "value": 100},
          "ranges": [
            {
              "if": {"op": "<=", "value": 50},
              "score": 25,
              "set_vars": {"$bonus": true}
            }
          ]
        }
      ]
    }
  }
}
```

## Operators & Functions

### Operators
- **Math**: `+`, `-`, `*`, `/`, `**` (power)
- **Comparison**: `<`, `<=`, `>`, `>=`, `==`, `!=`
- **Special**: `between [min, max]`, `in [array]`

### Functions
- `abs(x)`, `min(a,b,c)`, `max(a,b,c)`
- `sqrt(x)`, `round(x,n)`, `ceil(x)`, `floor(x)`

### Variable Operations
- **Input Variables**: Direct field names from input data
- **$ Variables**: Internal calculated values (`$variable_name`)
- **Expressions**: Use $ variables in formulas (`$price * 0.1`)
- **References**: Reference other $ variables (`$base_price`)

## Use Cases

- **Healthcare** — Risk assessment, BMI calculations, patient scoring
- **Finance** — Credit scoring, loan approval, investment analysis
- **E-commerce** — Dynamic pricing, customer tiers, discount systems
- **Business Rules** — Decision workflows, approval processes
- **Trading** — Grid bot strategies, risk management, portfolio analysis
- **Gaming** — Achievement systems, level progression
- **Education** — Grading systems, performance evaluation

## Examples

The PHP implementation includes comprehensive demos:

### Basic Examples (demo.php)
1. **BMI Calculator** — Basic expressions and categorization
2. **Credit Scoring** — Accumulative scoring system
3. **Blood Pressure** — Multi-dimensional health assessment
4. **E-commerce Discounts** — Dynamic pricing and tiers
5. **Academic Grading** — Weighted scoring with bonuses
6. **Dynamic Pricing** — Complex pricing with $ variables

### Unit Converter Examples (demo-converter.php)
1. **Length Converter** — mm, cm, m, km, inch, ft, yard, mile
2. **Weight Converter** — mg, g, kg, ton, oz, lb
3. **Temperature Converter** — Celsius, Fahrenheit, Kelvin, Rankine
4. **Area Converter** — Square units and land measurements
5. **Volume Converter** — Metric and imperial liquid measurements
6. **Time Converter** — ms, seconds, minutes, hours, days, weeks, months, years

### Advanced Trading Examples (demo-futures-analysis.php)
1. **Portfolio Stop Loss** — Risk management with asset-specific thresholds
2. **Market Trend Analysis** — Technical indicator evaluation
3. **Grid Position Management** — Automated trading position sizing
4. **Performance Evaluation** — Win rate and profit analysis
5. **Complete Trading Flow** — Integrated decision-making system

```bash
cd php && php examples/demo.php
cd php && php examples/demo-converter.php
cd php && php examples/demo-futures-analysis.php
```

## Contributing

### Adding a New Language
1. Implement core `RuleFlow` class
2. Support all formula types
3. Add comprehensive tests
4. Follow language conventions
5. Include $ notation support

### Required Features
- Expression evaluation with $ variables
- Switch/case logic with variable setting
- Multi-dimensional scoring
- Accumulative scoring rules
- All operators and functions
- Comprehensive validation
- Code generation capability

## Project Structure

```
ruleflow/
├── README.md
├── LICENSE
└── php/
    ├── README.md
    ├── src/RuleFlow.php
    └── examples/
        ├── demo.php
        ├── demo-converter.php
        └── demo-futures-analysis.php
```

## Version History

### v1.3.0 (Current)
- Added $ notation support for enhanced variable referencing
- Improved code generation with optimization
- New unit converter examples
- Advanced trading system examples
- Enhanced expression evaluation
- Better dependency resolution

### v1.2.0
- Updated parameter names for better clarity
- Shorter, more intuitive syntax
- Consistent naming conventions
- All existing functionality preserved

### v1.1.0
- Multi-dimensional scoring
- Enhanced weight scoring
- Variable setting capabilities
- Advanced operators (`in`, `between`)
- Real-world examples

### v1.0.0
- Initial PHP implementation
- Basic expression evaluation
- Switch/case logic
- Core rule engine functionality

## Performance

RuleFlow is designed for high performance:

- **Code Generation**: Converts JSON rules to optimized PHP functions
- **Dependency Optimization**: Automatically orders formula execution
- **Safe Evaluation**: No eval() usage, custom expression parser
- **Memory Efficient**: Minimal overhead for rule processing
- **Scalable**: Handle complex multi-dimensional rules efficiently

## Author

Created by [Jedsadha Rojanaphan](https://github.com/Jedsadha1777)

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/Jedsadha1777/RuleFlow/issues)
- **PHP Documentation**: [php/README.md](./php/README.md)
- **Examples**: See `/php/examples/` directory

---

**RuleFlow** - Declarative business logic made simple.