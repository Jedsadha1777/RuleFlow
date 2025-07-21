# RuleFlow

A declarative business logic engine available for both **PHP** and **TypeScript/JavaScript** that transforms complex business rules into maintainable JSON configurations with support for nested logical conditions, multi-dimensional scoring, and custom functions.

## Multi-Language Support

RuleFlow is now available in two implementations:

### PHP Version (Stable)
Full-featured implementation with templates, custom functions, and comprehensive documentation.

### TypeScript Version (New!)
Modern TypeScript implementation with advanced code generation capabilities and type safety.

```bash
# PHP Installation
git clone https://github.com/Jedsadha1777/RuleFlow.git

# TypeScript Installation  
npm install ruleflow-ts
```

## Key Features

### Core Engine (Both Versions)
- **Expression Evaluation**: Mathematical expressions with $ notation variable support
- **Nested Logic**: Complex AND/OR conditions with unlimited nesting depth
- **Switch/Case Logic**: Dynamic branching with variable setting capabilities
- **Multi-dimensional Scoring**: Complex scoring trees with weighted calculations
- **Accumulative Scoring**: Progressive rule evaluation with context preservation
- **Code Generation**: Converts JSON rules to optimized functions (10-100x performance boost in TypeScript)

### TypeScript-Specific Features 
- **Type Safety**: Full TypeScript support with automatic type inference
- **Advanced Code Generation**: Generate optimized TypeScript functions from rule configurations
- **Modern Performance**: 10-100x faster execution with generated code
- **Zero Dependencies**: Lightweight implementation for modern applications

### PHP-Specific Features
- **Template System**: Pre-built configurations for common business scenarios
- **Custom Functions System**: Auto-discovery and plugin architecture
- **50+ Templates**: Ready-to-use configurations across multiple domains

## Quick Start

### TypeScript Version
```typescript
import { RuleFlow } from 'ruleflow-ts';

const ruleFlow = new RuleFlow();

const config = {
  formulas: [
    {
      id: 'total',
      formula: 'price * quantity * (1 + tax_rate)',
      inputs: ['price', 'quantity', 'tax_rate']
    }
  ]
};

const result = await ruleFlow.evaluate(config, {
  price: 100,
  quantity: 2,
  tax_rate: 0.1
});

console.log(result.total); // 220
```

### PHP Version
```php
<?php
require_once 'src/RuleFlow.php';

$ruleFlow = new RuleFlow();

$config = [
    'formulas' => [
        ['id' => 'total', 'formula' => 'price * quantity * (1 + tax_rate)', 'inputs' => ['price', 'quantity', 'tax_rate']]
    ]
];

$inputs = ['price' => 100, 'quantity' => 2, 'tax_rate' => 0.1];
$result = $ruleFlow->evaluate($config, $inputs);
echo "Total: {$result['total']}"; // Output: Total: 220
```

## TypeScript Code Generation 

The TypeScript version includes powerful code generation capabilities:

```typescript
import { CodeGenerator } from 'ruleflow-ts';

const generator = new CodeGenerator();
const generated = generator.generate(config, {
  functionName: 'calculateTotal',
  includeComments: true
});

// Generates optimized TypeScript function
console.log(generated.code);
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

## Custom Functions System (PHP)

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

### PHP Documentation (Complete)
- [Introduction & Overview](https://github.com/Jedsadha1777/RuleFlow/wiki/)
- [Basic Rule Types](https://github.com/Jedsadha1777/RuleFlow/wiki/part2-basic-rule-types)
- [Advanced Features](https://github.com/Jedsadha1777/RuleFlow/wiki/part3-advanced-features)
- [Real-World Applications](https://github.com/Jedsadha1777/RuleFlow/wiki/part4-real-world-applications)
- [Production Implementation](https://github.com/Jedsadha1777/RuleFlow/wiki/part5-production-implementation)
- [Reference & Troubleshooting](https://github.com/Jedsadha1777/RuleFlow/wiki/part6-reference-troubleshooting)

### TypeScript Documentation 
> **Note**: TypeScript-specific documentation is coming soon! For now, please refer to the PHP documentation above as the core concepts and JSON configuration formats are identical between both versions. The main differences are in installation, imports, and the enhanced code generation capabilities available in TypeScript.

**TypeScript-specific features:**
- Type-safe interfaces and function generation
- Advanced performance optimizations
- Modern ES6+ syntax support
- Zero-dependency implementation

### API Reference
**PHP**: `evaluate()`, `validateConfig()`, `testConfig()`, `generateFunctionAsString()`, `getTemplates()`  
**TypeScript**: `evaluate()`, `validateConfig()`, `generate()` (CodeGenerator), type-safe interfaces

## Template System (PHP Only)

RuleFlow PHP includes 50+ pre-built templates across multiple domains:

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
├── ts/                         # TypeScript Implementation (New!)
│   ├── src/                    # TypeScript source code
│   ├── tests/                  # Comprehensive test suite
│   ├── package.json            # NPM package configuration
│   └── README.md               # TypeScript-specific docs
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

### TypeScript
```bash
# Clone repository
git clone https://github.com/Jedsadha1777/RuleFlow.git
cd RuleFlow/ts
npm install
npm test
```

### PHP
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

### Modern Web Applications (TypeScript)
- **Real-time Pricing**: High-performance calculation engines
- **User Personalization**: Dynamic content and feature rules
- **API Business Logic**: Type-safe server-side rule processing

## Performance

### PHP Version
- **Runtime Processing**: Handles hundreds of records per second
- **Code Generation**: 2-5x performance improvement
- **Memory Efficient**: Minimal overhead for rule processing

### TypeScript Version 
- **High Performance**: 5-10x faster with code generation
- **Runtime Processing**: Thousands of evaluations per second
- **Modern Optimization**: Leverages V8 engine optimizations
- **Type Safety**: Compile-time error detection

## Version History

### v1.0.0 (TypeScript) Release
- ** TypeScript Implementation**: Complete TypeScript/JavaScript support
- ** Advanced Code Generation**: 5-10x performance with generated functions
- ** Type Safety**: Full TypeScript support with automatic inference

### v1.5.0 (PHP)
- **Nested Logic Support**: Complex AND/OR conditions with unlimited nesting
- **Custom Functions System**: Auto-discovery from Functions/ folder
- **Enhanced Testing**: Comprehensive nested logic test suite
- **Backward Compatibility**: All existing configurations work unchanged

### v1.4.0 (PHP)
- Added comprehensive template system with 7 categories
- Enhanced $ notation support for variable referencing
- Improved code generation with optimization
- Advanced business logic examples across domains

## Roadmap 

### TypeScript Version
- **Template System**: Port PHP template system to TypeScript
- **Custom Functions**: Plugin architecture for TypeScript
- **Dedicated Documentation**: TypeScript-specific wiki and examples

## Known Limitations

### Configuration Limitations (Both Versions)
- Cannot nest switch inside switch (use separate formulas instead)
- Special characters in strings cause expression errors
- Very deep nesting (>6 levels) impacts performance

### TypeScript-Specific
- Template system not yet available (use PHP version for templates)
- Custom function system coming soon

### Solutions
- **Large Datasets**: Use batching or code generation
- **Complex Logic**: Break into multiple simpler formulas
- **Production Use**: Generate optimized functions (especially powerful in TypeScript)

## Support

- **Documentation**: [GitHub Wiki](https://github.com/Jedsadha1777/RuleFlow/wiki) (PHP-focused, applies to TypeScript)
- **Issues**: [GitHub Issues](https://github.com/Jedsadha1777/RuleFlow/issues)
- **PHP Documentation**: [php/README.md](./php/README.md)
- **TypeScript Documentation**: [typescript/README.md](./ts/README.md)
- **Examples**: 
  - PHP: Demo files in `/php/demos/` directory
  - TypeScript: Examples coming soon!

## Author

Created by [Jedsadha Rojanaphan](https://github.com/Jedsadha1777)

## License

MIT License - see [LICENSE](LICENSE) file for details.