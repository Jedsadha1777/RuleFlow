# RuleFlow

A declarative business logic engine for PHP that transforms complex business rules into maintainable JSON configurations with support for nested logical conditions, multi-dimensional scoring, and custom functions.

## Key Features

### Core Engine
- **Expression Evaluation**: Mathematical expressions with $ notation variable support
- **Nested Logic**: Complex AND/OR conditions with unlimited nesting depth
- **Switch/Case Logic**: Dynamic branching with variable setting capabilities
- **Multi-dimensional Scoring**: Complex scoring trees with weighted calculations
- **Accumulative Scoring**: Progressive rule evaluation with context preservation
- **Code Generation**: Converts JSON rules to optimized PHP functions (2-5x performance boost)

### Template System
- **Pre-built Templates**: Ready-to-use configurations for common business scenarios
- **Template Categories**: Organized by domain (Financial, Healthcare, HR, E-commerce, etc.)
- **Custom Templates**: Create and register your own reusable templates
- **Parameter Substitution**: Dynamic template customization with variables

### Custom Functions System
- **Auto-Discovery**: Automatically loads custom functions from `Functions/` folder
- **Extensible Logic**: Add domain-specific functions (booking validation, date calculations, etc.)
- **Plugin Architecture**: Clean interface for function providers

## Quick Start

```php
<?php
require_once 'src/RuleFlow.php';

$ruleFlow = new RuleFlow();

// Simple expression evaluation
$config = [
    'formulas' => [
        ['id' => 'total', 'formula' => 'price * quantity * (1 + tax_rate)', 'inputs' => ['price', 'quantity', 'tax_rate']]
    ]
];

$inputs = ['price' => 100, 'quantity' => 2, 'tax_rate' => 0.1];
$result = $ruleFlow->evaluate($config, $inputs);
echo "Total: {$result['total']}"; // Output: Total: 220
```

## Nested Logic Examples

### Loan Approval with Complex Conditions
```php
$config = [
    'formulas' => [
        [
            'id' => 'loan_decision',
            'switch' => 'application_type',
            'when' => [
                [
                    'if' => [
                        'and' => [
                            ['op' => '>', 'var' => 'age', 'value' => 25],
                            [
                                'or' => [
                                    ['op' => '>', 'var' => 'income', 'value' => 30000],
                                    ['op' => '==', 'var' => 'has_collateral', 'value' => true]
                                ]
                            ],
                            ['op' => '!=', 'var' => 'status', 'value' => 'blacklist']
                        ]
                    ],
                    'result' => 'approved'
                ]
            ],
            'default' => 'rejected'
        ]
    ]
];

$result = $ruleFlow->evaluate($config, [
    'application_type' => 'standard',
    'age' => 30,
    'income' => 25000,  // Low income
    'has_collateral' => true,  // But has collateral
    'status' => 'normal'
]);
// Result: approved (because has_collateral compensates for low income)
```

## Custom Functions System

### Creating Function Providers

```php
// src/Functions/BookingFunctions.php
class BookingFunctions implements RuleFlowFunctionProvider
{
    public static function getFunctions(): array
    {
        return [
            'is_valid_booking_date' => [self::class, 'isValidBookingDate'],
            'calculate_weekend_fee' => [self::class, 'calculateWeekendFee']
        ];
    }
    
    public static function getInfo(): array
    {
        return [
            'name' => 'Booking Functions',
            'version' => '1.0.0',
            'description' => 'Hotel booking validation and pricing functions'
        ];
    }
    
    public static function isValidBookingDate($date): bool
    {
        $bookingDate = new DateTime($date);
        $now = new DateTime();
        $maxAdvance = new DateTime('+6 months');
        
        return $bookingDate > $now && $bookingDate <= $maxAdvance;
    }
    
    public static function calculateWeekendFee($basePrice, $isWeekend): float
    {
        return $isWeekend ? $basePrice * 1.5 : $basePrice;
    }
}
```

### Using Custom Functions in Rules

```php
$bookingConfig = [
    'formulas' => [
        [
            'id' => 'booking_validation',
            'switch' => 'validate',
            'when' => [
                [
                    'if' => [
                        'and' => [
                            ['op' => 'function', 'function' => 'is_valid_booking_date', 'var' => 'booking_date'],
                            ['op' => '>', 'var' => 'guest_count', 'value' => 0]
                        ]
                    ],
                    'result' => 'valid'
                ]
            ],
            'default' => 'invalid'
        ],
        [
            'id' => 'booking_fee',
            'function_call' => 'calculate_weekend_fee',
            'params' => ['base_price', 'is_weekend']
        ]
    ]
];
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

### 2. Nested Logical Conditions
```json
{
  "id": "complex_decision",
  "switch": "trigger",
  "when": [
    {
      "if": {
        "and": [
          {"op": ">", "var": "score", "value": 80},
          {
            "or": [
              {"op": "==", "var": "premium_member", "value": true},
              {"op": ">=", "var": "years_member", "value": 3}
            ]
          }
        ]
      },
      "result": "qualified"
    }
  ],
  "default": "not_qualified"
}
```

### 3. Switch/Case Logic with Variables
```json
{
  "id": "grade",
  "switch": "$score",
  "when": [
    {
      "if": {"op": ">=", "value": 80}, 
      "result": "A",
      "set_vars": {"$gpa": 4.0}
    }
  ],
  "default": "F"
}
```

### 4. Multi-Dimensional Scoring
```json
{
  "id": "complex_scoring",
  "scoring": {
    "ifs": {
      "vars": ["performance", "experience"],
      "tree": [
        {
          "if": {"op": ">=", "value": 90},
          "ranges": [
            {"if": {"op": ">=", "value": 5}, "score": 100, "level": "Expert"}
          ]
        }
      ]
    }
  }
}
```

## Documentation

### Complete Guide
- [Introduction & Overview](https://github.com/Jedsadha1777/RuleFlow/wiki/)
- [Basic Rule Types](https://github.com/Jedsadha1777/RuleFlow/wiki/part2-basic-rule-types)
- [Advanced Features](https://github.com/Jedsadha1777/RuleFlow/wiki/part3-advanced-features)
- [Real-World Applications](https://github.com/Jedsadha1777/RuleFlow/wiki/part4-real-world-applications)
- [Production Implementation](https://github.com/Jedsadha1777/RuleFlow/wiki/part5-production-implementation)
- [Reference & Troubleshooting](https://github.com/Jedsadha1777/RuleFlow/wiki/part6-reference-troubleshooting)

### API Reference
- **Main Methods**: `evaluate()`, `validateConfig()`, `testConfig()`
- **Code Generation**: `generateFunctionAsString()`, `createCachedEvaluator()`
- **Templates**: `getTemplates()`, `getTemplate()`, `searchTemplates()`
- **Custom Functions**: `registerFunction()`, `getAvailableFunctions()`

## Template System

RuleFlow includes 50+ pre-built templates across multiple domains:

```php
// List all templates
$templates = $ruleFlow->getTemplates();

// Use specific template
$loanTemplate = $ruleFlow->getTemplate('loan_application');
$result = $ruleFlow->evaluate($loanTemplate['config'], $loanInputs);

// Get templates by category
$financialTemplates = $ruleFlow->getTemplates('financial');
```

### Available Categories
- **Financial**: Loan applications, credit scoring, payment processing
- **Healthcare**: BMI assessment, health risk evaluation
- **HR**: Performance reviews, candidate scoring, salary calculations
- **E-commerce**: Dynamic pricing, customer LTV, recommendation engines
- **Education**: Student grading, assessment scoring
- **Insurance**: Risk assessment, premium calculations

## Project Structure

```
RuleFlow/
├── README.md                    # This file
├── LICENSE                      # MIT License
└── php/                        # PHP Implementation
    ├── README.md               # PHP-specific documentation
    ├── src/                    # Source code
    │   ├── RuleFlow.php        # Main engine class
    │   ├── Functions/          # Custom function directory
    │   └── Templates/          # Template system
    ├── tests/                  # Comprehensive test suite
    └── demos/                  # Example demonstrations
```

## Installation & Testing

```bash
# Clone repository
git clone https://github.com/Jedsadha1777/RuleFlow.git
cd RuleFlow/php

# Run all tests
php tests/RunAllTests.php

# Run specific test suite
php tests/RunAllTests.php --test=nested     # Nested logic tests
php tests/RunAllTests.php --test=template   # Template tests
```

## Use Cases

### Financial Services
- **Loan Approval**: Multi-criteria decision making with nested conditions
- **Credit Scoring**: Complex risk assessment with weighted factors
- **Investment Eligibility**: Sophisticated customer qualification rules

### Insurance
- **Premium Calculation**: Risk-based pricing with multiple factors
- **Claims Processing**: Automated approval workflows
- **Underwriting**: Complex policy evaluation rules

### E-commerce
- **Dynamic Pricing**: Market-driven pricing strategies
- **Promotion Eligibility**: Customer segmentation rules
- **Inventory Management**: Automated reorder triggers

### Booking & Hospitality
- **Date Validation**: Custom booking window rules
- **Availability Checking**: Real-time room/service availability
- **Pricing Rules**: Dynamic fee calculation based on demand

## Performance

RuleFlow is optimized for moderate-scale business logic processing:

- **Runtime Processing**: Handles hundreds of records per second
- **Nested Logic**: Minimal performance impact with smart short-circuiting
- **Code Generation**: 2-5x performance improvement over runtime evaluation
- **Memory Efficient**: Minimal overhead for rule processing

## Version History

### v1.5.0 (Current)
- **Nested Logic Support**: Complex AND/OR conditions with unlimited nesting
- **Custom Functions System**: Auto-discovery from Functions/ folder
- **Enhanced Testing**: Comprehensive nested logic test suite
- **Backward Compatibility**: All existing configurations work unchanged

### v1.4.0 
- Added comprehensive template system with 7 categories
- Enhanced $ notation support for variable referencing
- Improved code generation with optimization
- Advanced business logic examples across domains

## Known Limitations

### Configuration Limitations
- Cannot nest switch inside switch (use separate formulas instead)
- Special characters in strings cause expression errors
- Very deep nesting (>6 levels) impacts performance

### Performance Limitations
- Not optimized for processing thousands of records simultaneously
- Runtime evaluation is 2-5x slower than generated functions
- Complex logical trees with many branches slow down evaluation

### Solutions
- **Large Datasets**: Use batching or code generation
- **Complex Logic**: Break into multiple simpler formulas
- **Production Use**: Generate optimized PHP functions

## Support

- **Documentation**: [GitHub Wiki](https://github.com/Jedsadha1777/RuleFlow/wiki)
- **Issues**: [GitHub Issues](https://github.com/Jedsadha1777/RuleFlow/issues)
- **PHP Documentation**: [php/README.md](./php/README.md)
- **Examples**: See demo files in `/php/demos/` directory
- **Tests**: Comprehensive test suite in `/php/tests/` directory

## Author

Created by [Jedsadha Rojanaphan](https://github.com/Jedsadha1777)

## License

MIT License - see [LICENSE](LICENSE) file for details.

