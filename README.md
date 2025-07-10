# RuleFlow

A powerful, declarative business logic engine for PHP that enables clean, maintainable rule-based programming. RuleFlow transforms complex business rules into simple JSON configurations with support for nested logical conditions, multi-dimensional scoring, mathematical expressions, and template-based rapid development.

## 🚀 Key Features

### Core Engine
- **Expression Evaluation**: Advanced mathematical expressions with $ notation variable support
- **🆕 Nested Logic**: Complex AND/OR conditions with unlimited nesting depth
- **Switch/Case Logic**: Dynamic branching with variable setting capabilities
- **Multi-dimensional Scoring**: Complex scoring trees with weighted calculations
- **Accumulative Scoring**: Progressive rule evaluation with context preservation
- **Comprehensive Validation**: Built-in configuration and input validation
- **Code Generation**: Converts JSON rules to optimized PHP functions (2-5x performance boost)

### Template System
- **Pre-built Templates**: Ready-to-use configurations for common business scenarios
- **Template Categories**: Organized by domain (Financial, Healthcare, HR, E-commerce, etc.)
- **Custom Templates**: Create and register your own reusable templates
- **Template Management**: Search, clone, modify, and organize templates
- **Parameter Substitution**: Dynamic template customization with variables

### 🆕 Custom Functions System
- **Auto-Discovery**: Automatically loads custom functions from `Functions/` folder
- **Extensible Logic**: Add domain-specific functions (booking validation, date calculations, etc.)
- **Plugin Architecture**: Clean interface for function providers
- **Built-in Functions**: Math, date, string, and validation functions included

### Advanced Features
- **Dependency Resolution**: Automatic formula execution ordering
- **Safe Evaluation**: Custom expression parser (no eval() usage)
- **Type Safety**: Input validation and type conversion
- **Error Handling**: Comprehensive error messages and debugging support
- **Performance Optimization**: Memory-efficient processing for moderate-scale operations

## Installation

```bash
git clone https://github.com/Jedsadha1777/RuleFlow.git
cd RuleFlow/php
```

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

## 🆕 Nested Logic Examples

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

// Logic: (age > 25) AND ((income > 30000) OR (has_collateral)) AND (status != 'blacklist')
$result = $ruleFlow->evaluate($config, [
    'application_type' => 'standard',
    'age' => 30,
    'income' => 35000,
    'has_collateral' => false,
    'status' => 'normal'
]);
// Result: approved
```

### Insurance Risk Assessment
```php
$riskConfig = [
    'formulas' => [
        [
            'id' => 'risk_level',
            'switch' => 'calculate',
            'when' => [
                [
                    'if' => [
                        'or' => [
                            [
                                'and' => [
                                    ['op' => '>', 'var' => 'age', 'value' => 65],
                                    ['op' => '==', 'var' => 'smoker', 'value' => true]
                                ]
                            ],
                            [
                                'and' => [
                                    ['op' => '<', 'var' => 'age', 'value' => 25],
                                    ['op' => '==', 'var' => 'has_accidents', 'value' => true]
                                ]
                            ],
                            ['op' => '>', 'var' => 'claims_count', 'value' => 3]
                        ]
                    ],
                    'result' => 'high_risk'
                ]
            ],
            'default' => 'low_risk'
        ]
    ]
];
```

## 🔧 Custom Functions System

### Auto-Discovery
Custom functions are automatically loaded from the `Functions/` folder:

```php
// Functions/BookingFunctions.php
class BookingFunctions implements RuleFlowFunctionProvider
{
    public static function getFunctions(): array
    {
        return [
            'is_valid_booking_date' => [self::class, 'isValidBookingDate'],
            'calculate_booking_fee' => [self::class, 'calculateBookingFee'],
            'check_availability' => [self::class, 'checkAvailability']
        ];
    }
    
    public static function getInfo(): array
    {
        return [
            'name' => 'Booking Functions',
            'version' => '1.0.0',
            'description' => 'Date and booking validation functions'
        ];
    }
    
    public static function isValidBookingDate($date): bool
    {
        $bookingDate = new DateTime($date);
        $now = new DateTime();
        $maxAdvance = new DateTime('+6 months');
        
        return $bookingDate > $now && $bookingDate <= $maxAdvance;
    }
    
    public static function calculateBookingFee($basePrice, $dayOfWeek): float
    {
        $weekendMultiplier = in_array($dayOfWeek, [0, 6]) ? 1.5 : 1.0;
        return $basePrice * $weekendMultiplier;
    }
}
```

### Using Custom Functions in Rules
```php
$bookingConfig = [
    'formulas' => [
        [
            'id' => 'booking_validation',
            'switch' => 'check_booking',
            'when' => [
                [
                    'if' => [
                        'and' => [
                            ['op' => 'function', 'function' => 'is_valid_booking_date', 'var' => 'booking_date'],
                            ['op' => 'function', 'function' => 'check_availability', 'var' => 'room_id'],
                            ['op' => '>', 'var' => 'guest_count', 'value' => 0]
                        ]
                    ],
                    'result' => 'valid'
                ]
            ],
            'default' => 'invalid'
        ],
        [
            'id' => 'total_fee',
            'function_call' => 'calculate_booking_fee',
            'params' => ['$base_price', '$day_of_week']
        ]
    ]
];
```

## Template System

### Available Template Categories
- **Financial**: Loan applications, credit scoring, payment processing
- **Healthcare**: BMI assessment, health risk evaluation
- **HR**: Performance reviews, candidate scoring, salary calculations
- **E-commerce**: Dynamic pricing, customer LTV, recommendation engines
- **Education**: Student grading, assessment scoring
- **Insurance**: Risk assessment, premium calculations
- **Real Estate**: Property valuation, market analysis

### Using Templates
```php
// List all available templates
$templates = $ruleFlow->getTemplates();

// Use a specific template
$loanConfig = $ruleFlow->getTemplate('loan_application');
$result = $ruleFlow->evaluate($loanConfig['config'], $loanInputs);

// Get templates by category
$healthTemplates = $ruleFlow->getTemplatesByCategory('healthcare');

// Use template with custom parameters
$customConfig = $ruleFlow->getTemplateWithParams('bmi_assessment', [
    'underweight_threshold' => 18.0,
    'overweight_threshold' => 25.0
]);
```

## Project Structure

```
RuleFlow/
├── README.md                    # This file
├── LICENSE                      # MIT License
└── php/                        # PHP Implementation
    ├── README.md               # PHP-specific documentation
    ├── src/                    # Source code
    │   ├── RuleFlow.php        # Main engine class
    │   ├── FormulaProcessor.php # Formula execution engine
    │   ├── ExpressionEvaluator.php # Math expression parser
    │   ├── CodeGenerator.php   # PHP code generation
    │   ├── Functions/          # 🆕 Custom function directory
    │   │   └── *.php          # Auto-loaded function providers
    │   └── Templates/          # Template system
    │       ├── ConfigTemplateManager.php
    │       └── Providers/      # Template providers
    ├── tests/                  # Comprehensive test suite
    │   ├── README.md          # Test documentation
    │   ├── RunAllTests.php    # Test runner
    │   ├── NestedLogicTest.php # 🆕 Nested logic tests
    │   └── *Test.php          # Individual test files
    └── demos/                  # Example demonstrations
        ├── demo15-nested-logic-demonstration.php # 🆕 Nested logic examples
        └── *.php              # Various demo files
```

## Version History

### v1.5.0 (Current) 🆕
- **Nested Logic Support**: Complex AND/OR conditions with unlimited nesting
- **Custom Functions System**: Auto-discovery from Functions/ folder
- **Enhanced Testing**: Comprehensive nested logic test suite
- **Improved Documentation**: Updated examples and migration guides
- **Backward Compatibility**: All existing configurations work unchanged

### v1.4.1
- Fixed type inconsistency bug
- Fixed logic bug in ConfigValidator

### v1.4.0 
- Added comprehensive template system with 7 categories
- Template management: search, clone, modify, organize
- Enhanced $ notation support for variable referencing
- Improved code generation with optimization
- Advanced business logic examples across domains
- Better dependency resolution and error handling

### v1.3.0
- Added $ notation support for enhanced variable referencing
- Improved code generation with optimization
- New unit converter examples
- Advanced trading system examples

### v1.2.0
- Updated parameter names for better clarity
- Shorter, more intuitive syntax
- Consistent naming conventions

### v1.1.0
- Multi-dimensional scoring system
- Enhanced weight scoring capabilities
- Variable setting in switch/case logic
- Advanced operators (`in`, `between`)

### v1.0.0
- Initial PHP implementation
- Basic expression evaluation engine
- Switch/case logic support

## Performance

RuleFlow is optimized for moderate-scale business logic processing:

- **Runtime Processing**: Handles hundreds of records per second
- **Nested Logic**: Minimal performance impact with smart short-circuiting
- **Code Generation**: 2-5x performance improvement over runtime evaluation
- **Memory Efficient**: Minimal overhead for rule processing
- **Dependency Optimization**: Automatic formula execution ordering
- **Safe Evaluation**: Custom expression parser without eval() usage

## Testing

```bash
# Run all tests (includes nested logic tests)
php tests/RunAllTests.php

# Run specific test suites
php tests/RunAllTests.php --test=nested    # Test nested logic
php tests/RunAllTests.php --test=template  # Test templates
php tests/RunAllTests.php --test=integration  # End-to-end tests

# Quick functionality test
php tests/RunAllTests.php --quick
```

## Migration Guide

### Adding Nested Logic to Existing Rules

**Before** (Multiple formulas):
```php
$config = [
    'formulas' => [
        ['id' => 'age_check', 'switch' => 'age', 'when' => [['if' => ['op' => '>', 'value' => 25], 'result' => true]], 'default' => false],
        ['id' => 'income_check', 'switch' => 'income', 'when' => [['if' => ['op' => '>', 'value' => 30000], 'result' => true]], 'default' => false],
        ['id' => 'final_decision', 'formula' => '$age_check && $income_check', 'inputs' => ['age_check', 'income_check']]
    ]
];
```

**After** (Single nested formula):
```php
$config = [
    'formulas' => [
        [
            'id' => 'final_decision',
            'switch' => 'evaluate',
            'when' => [
                [
                    'if' => [
                        'and' => [
                            ['op' => '>', 'var' => 'age', 'value' => 25],
                            ['op' => '>', 'var' => 'income', 'value' => 30000]
                        ]
                    ],
                    'result' => true
                ]
            ],
            'default' => false
        ]
    ]
];
```

## Known Limitations & Solutions

### 🚨 Configuration Limitations

#### 1. **Nested Switch Not Supported**
```json
// ❌ Cannot nest switch inside switch
{
  "switch": "var1",
  "when": [
    {
      "if": {"op": "==", "value": "condition"},
      "switch": "var2",  // ← Error!
      "when": [...]
    }
  ]
}

// ✅ Solution: Use separate formulas
{
  "formulas": [
    {"id": "step1", "switch": "var1", "when": [...], "as": "$intermediate"},
    {"id": "step2", "switch": "$intermediate", "when": [...]}
  ]
}
```

#### 2. **Expression String Restrictions**
```json
// ❌ Special characters in strings cause errors
{"set_vars": {"$rate": "N/A"}}  // ← Expression error

// ✅ Solution: Use valid identifiers
{"set_vars": {"$rate": "not_applicable"}}
{"set_vars": {"$rate": "null_value"}}
```

#### 3. **Deep Nesting Performance**
```json
// ⚠️ Very deep nesting (>6 levels) impacts performance
{
  "and": [
    {"and": [
      {"and": [
        {"and": [...]}  // ← Too deep
      ]}
    ]}
  ]
}

// ✅ Solution: Break into multiple formulas or simplify logic
{
  "formulas": [
    {"id": "check1", "if": {"and": [...]}, "as": "$result1"},
    {"id": "check2", "if": {"and": ["$result1", ...]}}
  ]
}
```

### 🔧 Runtime Limitations

#### 4. **Variable Scoping Issues**
```json
// ⚠️ Global $ variables can conflict
{"set_vars": {"$temp": "value1"}}  // Formula 1
{"set_vars": {"$temp": "value2"}}  // Formula 2 - overwrites!

// ✅ Solution: Use unique variable names
{"set_vars": {"$loan_temp": "value1", "$insurance_temp": "value2"}}
```

#### 5. **Expression Evaluation Edge Cases**
```json
// ❌ Operator precedence issues
{"formula": "-2 ** 2"}  // Returns -4 instead of 4

// ✅ Solution: Use explicit parentheses
{"formula": "(-2) ** 2"}  // Returns 4
{"formula": "-(2 ** 2)"}  // Returns -4 (if intended)
```

### 📊 Performance Guidelines

#### 6. **Large Dataset Processing**
```php
// ❌ Processing thousands of records at once
for ($i = 0; $i < 10000; $i++) {
    $result = $ruleFlow->evaluate($config, $data[$i]);  // Slow
}

// ✅ Solution: Use batching or code generation
$evaluator = $ruleFlow->createCachedEvaluator($config);
$results = array_map($evaluator, $dataBatches);  // 2-5x faster
```

#### 7. **Complex Logic Optimization**
```json
// ⚠️ Complex nested conditions
{
  "and": [
    {"op": "complex_function", "var": "data"},
    {"or": [
      {"and": [...]},
      {"and": [...]}
    ]}
  ]
}

// ✅ Solution: Order conditions by selectivity (most restrictive first)
{
  "and": [
    {"op": ">", "var": "simple_number", "value": 1000},  // Fast filter first
    {"op": "complex_function", "var": "data"}            // Expensive check last
  ]
}
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

## Author

Created by [Jedsadha Rojanaphan](https://github.com/Jedsadha1777)

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/Jedsadha1777/RuleFlow/issues)
- **PHP Documentation**: [php/README.md](./php/README.md)
- **Examples**: See demo files in `/php/demos/` directory
- **Tests**: Comprehensive test suite in `/php/tests/` directory

---

**RuleFlow v1.5.0** - Now with nested logic and custom functions! Transform complex business rules into maintainable JSON configurations with unlimited flexibility and power. 🚀