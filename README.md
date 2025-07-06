# RuleFlow

A powerful, declarative business logic engine for PHP that enables clean, maintainable rule-based programming. RuleFlow transforms complex business rules into simple JSON configurations with support for multi-dimensional scoring, mathematical expressions, and template-based rapid development.

## Key Features

### Core Engine
- **Expression Evaluation**: Advanced mathematical expressions with $ notation variable support
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
require_once 'vendor/autoload.php';

use RuleFlow\RuleFlow;

// Initialize RuleFlow
$ruleFlow = new RuleFlow();

// Simple expression evaluation
$config = [
    'formulas' => [
        ['id' => 'total', 'expression' => 'price * quantity * (1 + tax_rate)']
    ]
];

$inputs = [
    'price' => 100,
    'quantity' => 2,
    'tax_rate' => 0.1
];

$result = $ruleFlow->evaluate($config, $inputs);
echo "Total: {$result['total']}"; // Output: Total: 220
```

## Template System

RuleFlow includes a comprehensive template system with pre-built configurations for common business scenarios.

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
print_r($templates);

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

### Template Management

```php
// Search templates
$searchResults = $ruleFlow->searchTemplates('credit');

// Clone and modify templates
$customTemplate = $ruleFlow->cloneTemplate('loan_application', 'custom_loan');
$ruleFlow->modifyTemplate('custom_loan', [
    'metadata.name' => 'Custom Loan Template',
    'metadata.description' => 'Modified for specific business needs'
]);

// Register custom template
$ruleFlow->registerTemplate('my_template', $myConfig, [
    'category' => 'custom',
    'description' => 'My custom business logic'
]);
```

## Project Structure

```
RuleFlow/
├── README.md                     # This file
├── LICENSE                       # MIT License
└── php/                         # PHP Implementation
    ├── README.md                # PHP-specific documentation
    ├── src/                     # Source code
    │   ├── RuleFlow.php         # Main engine class
    │   ├── Templates/           # Template system
    │   │   ├── ConfigTemplateManager.php
    │   │   └── Providers/       # Template providers
    │   │       ├── FinancialTemplates.php
    │   │       ├── HealthcareTemplates.php
    │   │       ├── HRTemplates.php
    │   │       ├── EcommerceTemplates.php
    │   │       ├── EducationTemplates.php
    │   │       ├── InsuranceTemplates.php
    │   │       └── RealEstateTemplates.php
    │   ├── Core/                # Core engine components
    │   │   ├── ExpressionEvaluator.php
    │   │   ├── ConfigValidator.php
    │   │   ├── InputValidator.php
    │   │   └── FunctionRegistry.php
    │   └── Utils/               # Utility classes
    │       ├── SchemaGenerator.php
    │       └── CodeGenerator.php
    ├── tests/                   # Test suite
    │   ├── README.md           # Test documentation
    │   ├── RunAllTests.php     # Test runner
    │   └── *Test.php           # Individual test files
    └── demos/                   # Example demonstrations
        ├── basic_demo.php
        ├── template_demo.php
        └── advanced_demo.php
```

## Advanced Usage

### Code Generation
```php
// Generate optimized PHP function
$phpCode = $ruleFlow->generateFunction($config, 'calculateTotal');

// Use generated function
eval($phpCode);
$result = calculateTotal($inputs);
```

### Multi-dimensional Scoring
```php
$config = [
    'formulas' => [
        [
            'id' => 'credit_score',
            'type' => 'multi_dimensional',
            'dimensions' => [
                'payment_history' => ['weight' => 0.35, 'score' => '$payment_score'],
                'credit_utilization' => ['weight' => 0.30, 'score' => '$utilization_score'],
                'credit_age' => ['weight' => 0.15, 'score' => '$age_score']
            ]
        ]
    ]
];
```

### Switch/Case Logic
```php
$config = [
    'formulas' => [
        [
            'id' => 'risk_category',
            'type' => 'switch',
            'switch_on' => '$credit_score',
            'cases' => [
                ['condition' => '< 600', 'set' => ['risk' => 'high']],
                ['condition' => '>= 600 && < 750', 'set' => ['risk' => 'medium']],
                ['condition' => '>= 750', 'set' => ['risk' => 'low']]
            ]
        ]
    ]
];
```

## Version History

### v1.4.0 (Current)
- Added comprehensive template system with 7 categories
- Template management: search, clone, modify, organize
- Enhanced $ notation support for variable referencing
- Improved code generation with optimization
- Advanced business logic examples across domains
- Better dependency resolution and error handling
- Template import/export functionality
- Enhanced template validation and organization

### v1.3.0
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
- Multi-dimensional scoring system
- Enhanced weight scoring capabilities
- Variable setting in switch/case logic
- Advanced operators (`in`, `between`)
- Real-world business examples

### v1.0.0
- Initial PHP implementation
- Basic expression evaluation engine
- Switch/case logic support
- Core rule engine functionality

## Performance

RuleFlow is optimized for moderate-scale business logic processing:

- **Runtime Processing**: Handles hundreds of records per second
- **Code Generation**: 2-5x performance improvement over runtime evaluation
- **Memory Efficient**: Minimal overhead for rule processing
- **Dependency Optimization**: Automatic formula execution ordering
- **Safe Evaluation**: Custom expression parser without eval() usage

**Performance Expectations:**
- Suitable for typical business applications
- Complex multi-dimensional rules may impact throughput
- Use code generation for production environments
- Process large datasets in batches for optimal performance

## Testing

```bash
# Run all tests
php tests/RunAllTests.php

# Run specific test suite
php tests/RunAllTests.php --test=template
php tests/RunAllTests.php --test=expression
php tests/RunAllTests.php --test=integration
```

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

**RuleFlow** - Declarative business logic made simple. Transform complex rules into maintainable JSON configurations with powerful template system and optimized code generation.