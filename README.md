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

The PHP implementation includes demo files organized by functionality:

### Basic Usage
1. **demo1-basic.php** — Math expressions, conditional logic, built-in functions
2. **demo2-templates.php** — Using pre-built templates (BMI, loans, performance)  
3. **demo3-validation.php** — Input validation and error handling

### Real-World Applications
4. **demo4-loan-application.php** — Loan assessment with multiple test cases
5. **demo5-bmi-calculator.php** — Health assessment and BMI categorization
6. **demo6-employee-review.php** — Performance reviews and candidate scoring
7. **demo7-dynamic-pricing.php** — E-commerce pricing with market conditions

### Advanced Features
8. **demo8-dollar-notation.php** — $ variable usage and complex expressions
9. **demo9-custom-functions.php** — Custom business functions (shipping, LTV, risk)
10. **demo10-multi-dimensional.php** — Matrix scoring (bonuses, credit limits)
11. **demo11-code-generation.php** — Generate optimized PHP functions

### Collections
12. **demo12-multi_demo_collection.php** — 6 demos in one file
13. **demo13-converter.php** — Unit conversion systems
14. **demo14-futures-analysis.php** — Trading analysis examples

**Run examples:**
```bash
cd php && php demo1-basic.php
cd php && php demo4-loan-application.php
cd php && php demo12-multi_demo_collection.php
```

## Testing

### Test Environment
```bash
cd php && php RunAllTests.php
```

### Available Test Coverage
- **Basic functionality** — Expression evaluation, switch logic
- **$ Notation** — Variable setting and referencing
- **Multi-dimensional scoring** — Complex nested conditions
- **Error handling** — Input validation and edge cases
- **Code generation** — Function optimization and safety

### Known Test Limitations
- Coverage is approximately 80% of all features
- Some complex edge cases may not be covered
- Performance tests are basic benchmarks only

## Known Issues & Limitations

### Expression Parsing
- **Operator precedence**: `-2 ** 2` returns `-4` instead of `4`
  - **Workaround**: Use parentheses: `(-2) ** 2` or `-(2 ** 2)`
- **Complex expressions**: Very long formulas may hit parsing limits
- **Variable scoping**: $ variables are global within formula execution

### Performance Considerations
- **Large datasets**: Not optimized for processing thousands of records
- **Deep nesting**: Very deep multi-dimensional scoring may be slow
- **Memory usage**: Each formula execution creates new context

### Configuration Validation
- **Runtime errors**: Some invalid configs only fail during execution
- **Dependency resolution**: Circular references are not always detected
- **Type checking**: Limited type validation for input variables

### Workarounds & Solutions
- **Use code generation** for production environments (better performance)
- **Validate configurations** during development, not runtime
- **Keep formulas simple** and break complex logic into smaller steps
- **Test thoroughly** with representative data before production use

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
    └── RunAllTests.php
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

RuleFlow is designed for moderate-scale business logic:

- **Code Generation**: Converts JSON rules to optimized PHP functions
- **Dependency Optimization**: Automatically orders formula execution
- **Safe Evaluation**: No eval() usage, custom expression parser
- **Memory Efficient**: Minimal overhead for rule processing
- **Moderate Scale**: Handle complex rules efficiently for typical business needs

**Performance expectations:**
- Suitable for processing hundreds of records per second
- Complex multi-dimensional rules may reduce throughput
- Code generation provides 2-5x performance improvement

## Author

Created by [Jedsadha Rojanaphan](https://github.com/Jedsadha1777)

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/Jedsadha1777/RuleFlow/issues)
- **PHP Documentation**: [php/README.md](./php/README.md)
- **Examples**: See demo files in `/php/` directory

---

**RuleFlow** - Declarative business logic made simple.