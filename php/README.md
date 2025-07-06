# RuleFlow PHP

A declarative rule engine for evaluating business logic from JSON configuration with multi-dimensional scoring and $ notation support.

## Requirements

- PHP 8.0 or higher
- No external dependencies

## Installation

```bash
git clone https://github.com/Jedsadha1777/RuleFlow.git
cd RuleFlow/php
```

## Quick Start

```php
<?php
require_once 'src/RuleFlow.php';

$engine = new RuleFlow();

$config = [
    "formulas" => [
        [
            "id" => "bmi",
            "formula" => "round((weight / ((height / 100) ** 2)), 2)",
            "inputs" => ["weight", "height"],
            "as" => "$bmi_value"
        ],
        [
            "id" => "category",
            "switch" => "$bmi_value",
            "when" => [
                ["if" => ["op" => "<", "value" => 18.5], "result" => "Underweight"],
                ["if" => ["op" => "between", "value" => [18.5, 24.9]], "result" => "Normal"],
                ["if" => ["op" => ">=", "value" => 25], "result" => "Overweight"]
            ]
        ]
    ]
];

$result = $engine->evaluate($config, ["weight" => 70, "height" => 175]);
// Result: ["weight" => 70, "height" => 175, "bmi_value" => 22.86, "category" => "Normal"]
```

## API Reference

### Main Methods

```php
// Evaluate configuration
$result = $engine->evaluate($config, $inputs);

// Validate configuration
$errors = $engine->validateConfig($config);

// Test with sample data
$testResult = $engine->testConfig($config, $sampleInputs);
```

### Code Generation Methods

```php
// Generate PHP code as string
$code = $engine->generateFunctionAsString($config);
echo $code; // Shows complete optimized PHP function
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

### 2. Switch/Case Logic with $ Variables
```json
{
  "id": "grade",
  "switch": "$score",
  "when": [
    {
      "if": {"op": ">=", "value": 80}, 
      "result": "A",
      "set_vars": {"$gpa": 4.0}
    },
    {
      "if": {"op": ">=", "value": 70}, 
      "result": "B",
      "set_vars": {"$gpa": 3.0}
    }
  ],
  "default": "F",
  "default_vars": {"$gpa": 0.0}
}
```

### 3. Accumulative Scoring System
```json
{
  "id": "credit_score",
  "rules": [
    {
      "var": "income",
      "ranges": [
        {"if": {"op": ">=", "value": 50000}, "score": 25},
        {"if": {"op": ">=", "value": 30000}, "score": 15}
      ]
    },
    {
      "var": "has_property",
      "if": {"op": "==", "value": 1},
      "score": 20
    }
  ]
}
```

### 4. Multi-Dimensional Scoring with $ Variables
```json
{
  "id": "complex_scoring",
  "scoring": {
    "ifs": {
      "vars": ["$risk_score", "$trend_ratio"],
      "tree": [
        {
          "if": {"op": ">=", "value": 50},
          "ranges": [
            {
              "if": {"op": ">=", "value": 2},
              "score": 0,
              "decision": "EMERGENCY_STOP",
              "set_vars": {
                "$alert_level": "critical",
                "$action": "close_all_positions"
              }
            }
          ]
        }
      ]
    }
  }
}
```

## Advanced Features

### $ Notation Support

RuleFlow supports enhanced variable referencing with $ notation:

#### Variable Storage
```json
{
  "id": "price_calculation",
  "formula": "cost * markup",
  "inputs": ["cost", "markup"],
  "as": "$base_price"
}
```

#### Variable References
```json
{
  "id": "final_price",
  "switch": "demand_level",
  "when": [
    {
      "if": {"op": "==", "value": "high"},
      "result": "surge_pricing",
      "set_vars": {
        "$multiplier": 1.5,
        "$final_amount": "$base_price * $multiplier"
      }
    }
  ]
}
```

#### Expression Variables
```json
{
  "set_vars": {
    "$discount": "$base_price * 0.1",
    "$tax": "($base_price - $discount) * 0.07",
    "$total": "$base_price - $discount + $tax"
  }
}
```

## Supported Features

### Operators
- **Math**: `+`, `-`, `*`, `/`, `**` (power)
- **Comparison**: `<`, `<=`, `>`, `>=`, `==`, `!=`
- **Range**: `between` → `{"op": "between", "value": [10, 20]}`
- **Array**: `in` → `{"op": "in", "value": ["red", "blue"]}`

### Functions
- `abs(x)`, `min(a,b,c)`, `max(a,b,c)`
- `sqrt(x)`, `round(x,n)`, `ceil(x)`, `floor(x)`

### Variable Setting with $ Notation
```json
"set_vars": {
  "$status": "approved", 
  "$rate": 5.5,
  "$calculated_amount": "$base * $rate"
}
```

## Code Generation Features

RuleFlow can generate optimized PHP code from your JSON configurations:

### PHP Code as String
```php
// Get the generated code
$phpCode = $engine->generateFunctionAsString($config);
echo $phpCode;
/*
function(array $inputs): array {
    $context = $inputs;
    
    // Formula: bmi_calculation
    if (!isset($context['weight'])) {
        throw new Exception('Missing input: weight');
    }
    if (!isset($context['height'])) {
        throw new Exception('Missing input: height');
    }
    $context['bmi_value'] = $context['weight'] / pow($context['height'], 2);
    
    // Formula: category
    $switchValue = $context['bmi_value'] ?? null;
    if ($switchValue === null) {
        throw new Exception('Switch value \'$bmi_value\' not found');
    }
    if ($switchValue < 18.5) {
        $context['category'] = 'Underweight';
    } elseif ($switchValue >= 18.5 && $switchValue <= 24.9) {
        $context['category'] = 'Normal';
    } elseif ($switchValue >= 25) {
        $context['category'] = 'Overweight';
    }
    
    return $context;
}
*/
```

### Function Generation Features
- **Dependency Optimization**: Automatically orders formula execution
- **Input Validation**: Checks for required inputs
- **Error Handling**: Comprehensive error messages
- **Type Safety**: Proper type checking and conversion
- **Performance**: Optimized code without runtime overhead

## Examples & Demos

## Template System

RuleFlow includes a template system for common business scenarios with automatic loading from provider files.

### How Templates Work

Templates are pre-built configurations loaded from PHP classes in the `/src/Providers/` directory:

```
php/src/Templates/Providers/
├── FinancialTemplates.php    — Loan applications, credit card approval
├── HealthcareTemplates.php   — BMI health assessment
├── HRTemplates.php           — Performance reviews, candidate scoring
├── EcommerceTemplates.php    — Dynamic pricing, customer LTV
├── EducationTemplates.php    — Student grading systems
├── InsuranceTemplates.php    — Auto insurance risk assessment
└── RealEstateTemplates.php   — Property valuation models
```

### Using Templates

```php
$ruleFlow = new RuleFlow();

// List available templates
$templates = $ruleFlow->getTemplates();
echo "Available: " . implode(', ', $templates['available_templates']);

// Get specific template
$bmiTemplate = $ruleFlow->getTemplate('bmi_health_assessment');
$result = $ruleFlow->evaluate($bmiTemplate['config'], $inputs);

// Get templates by category
$financialTemplates = $ruleFlow->getTemplates('financial');
```

### Available Templates

**Financial Category:**
- `loan_application` — Credit scoring with income, debt, employment evaluation
- `credit_card_approval` — Credit card limits based on credit score and utilization

**Education Category:**
- `student_grading` — Weighted scoring with letter grades and GPA calculation

**E-commerce Category:**
- `dynamic_pricing` — Market-based pricing with demand and inventory factors
- `customer_ltv` — Customer lifetime value calculation with retention modeling

### Template Structure

Each template contains:
- **config** — RuleFlow formula configuration
- **metadata** — Name, description, inputs, outputs, version info

### Creating Custom Templates

Templates are loaded automatically from any PHP class implementing `TemplateProviderInterface`:

```php
class CustomTemplates implements TemplateProviderInterface
{
    public function getCategory(): string { return 'custom'; }
    
    public function getTemplates(): array
    {
        return [
            'my_template' => [
                'config' => [ /* RuleFlow config */ ],
                'metadata' => [ /* Template info */ ]
            ]
        ];
    }
}
```

### Template Caching

Templates are automatically cached for performance:
- Cache location: `/src/Providers/cache/.template_cache.json`
- Auto-refresh when provider files change
- Can be disabled in constructor: `new RuleFlow(enableCache: false)`

## Examples & Demos

### Basic Usage

#### demo1-basic.php
- Math expressions with price calculations
- Switch/case logic for discounts  
- Built-in functions for grade calculation
- Error handling for missing inputs

#### demo2-templates.php  
- BMI health assessment template
- Loan application with credit scoring
- Employee performance reviews
- Template browsing by category

#### demo3-validation.php
- Configuration validation and error checking
- Individual field validation with type conversion
- Partial form validation with progress tracking
- Live preview with estimated values

### Real-World Applications

#### demo4-loan-application.php
- Three credit scenarios: excellent, marginal, poor
- Income, debt-to-income, employment scoring
- Batch processing of multiple applications
- Interest rates and payment calculations

#### demo5-bmi-calculator.php
- BMI calculation with health categories
- Personalized health recommendations
- HTML form generation from config
- JSON schema for API integration

#### demo6-employee-review.php
- Multi-factor employee performance evaluation
- Candidate assessment with education/experience
- Salary increase recommendations
- Rating scales from exceptional to unsatisfactory

#### demo7-dynamic-pricing.php
- Market conditions: demand, inventory, competition
- Customer tier discounts (VIP, Gold, Silver)
- Customer lifetime value analysis
- Multiple pricing scenario testing

### Advanced Features

#### demo8-dollar-notation.php
- Variable storage with $ notation
- Multi-step calculations using $ variables
- Customer segmentation with tier-based benefits
- Complex expression chaining

#### demo9-custom-functions.php
- Custom shipping cost calculator
- Customer LTV with churn rate modeling
- Credit risk scoring functions
- Inventory turnover and reorder calculations

#### demo10-multi-dimensional.php
- Employee bonus matrix (performance vs tenure)
- Credit card limits (income vs credit score)
- Dynamic pricing (demand vs competition)
- Real business decision matrices

#### demo11-code-generation.php
- Generate optimized PHP functions
- Performance comparison: runtime vs cached
- Production-ready code output
- Memory usage optimization

### Collections

#### demo12-multi_demo_collection.php
Six business scenarios in one file:
- BMI Calculator — Health assessment
- Credit Scoring — Financial evaluation  
- Blood Pressure — Medical risk evaluation
- E-commerce Discounts — Customer tier management
- Academic Grading — Student performance
- Dynamic Pricing — Advanced e-commerce pricing

#### demo13-converter.php
Unit conversion with performance testing:
- Length, weight, temperature, area, volume, time
- Error handling and input validation
- Performance benchmarks
- Production function generation

#### demo14-futures-analysis.php
Trading analysis tools:
- Stop loss portfolio protection
- Multi-factor risk assessment
- Dynamic position sizing
- Trading performance analysis

## Testing

### Running Tests

```bash
# Run all available tests
php RunAllTests.php

# Run specific test suite
php RunAllTests.php --test=basic

# Check test environment
php RunAllTests.php --check

# List available tests
php RunAllTests.php --list
```

### Test Coverage Overview

The test suite includes:

- **Basic Functionality Tests** — Expression evaluation, operators, functions
- **$ Notation Tests** — Variable storage, referencing, complex expressions
- **Multi-dimensional Scoring** — Nested conditions, range evaluations
- **Switch/Case Logic** — Conditional branching, default handling
- **Error Handling** — Input validation, configuration errors
- **Code Generation** — Function optimization, safety checks

### Test Limitations

**Current test coverage: ~80%**
- Some edge cases in complex multi-dimensional scoring
- Limited performance testing under high load
- Incomplete testing of all operator combinations
- Basic validation of generated code functionality

### Known Issues in Testing

- **Expression precedence**: `-2 ** 2` evaluation tests may fail
- **Memory testing**: Large dataset performance not thoroughly tested
- **Circular dependency**: Detection may not catch all cases

### Debugging Failed Tests

If tests fail:
1. Check PHP version (requires 8.0+)
2. Verify all source files are present
3. Run `--check` to validate test environment
4. Review specific test output for error details

## Error Handling

### Basic Error Handling
```php
try {
    $errors = $engine->validateConfig($config);
    if (!empty($errors)) {
        throw new Exception('Invalid config: ' . implode(', ', $errors));
    }
    
    $result = $engine->evaluate($config, $inputs);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Validation Features
```php
// Validate configuration
$errors = $engine->validateConfig($config);
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "Config Error: $error\n";
    }
}

// Test configuration with sample data
$testResult = $engine->testConfig($config, $sampleInputs);
if (!$testResult['valid']) {
    echo "Test Failed: " . implode(', ', $testResult['errors']);
}

// Check for warnings
if (!empty($testResult['warnings'])) {
    foreach ($testResult['warnings'] as $warning) {
        echo "Warning: $warning\n";
    }
}
```

### Common Error Types

- **Configuration errors**: Invalid JSON structure, missing required fields
- **Input validation**: Missing required inputs, type mismatches
- **Expression errors**: Invalid syntax, undefined variables
- **Dependency errors**: Circular references, missing dependencies

## Known Limitations

### Expression Parser Limitations

1. **Operator Precedence Issue**
   - Problem: `-2 ** 2` returns `-4` instead of `4`
   - Cause: Unary minus has higher precedence than exponentiation
   - **Workaround**: Use parentheses: `(-2) ** 2` or `-(2 ** 2)`

2. **Complex Expression Parsing**
   - Very long formulas may hit parsing limits
   - Deeply nested parentheses can cause performance issues
   - **Workaround**: Break complex expressions into smaller formulas

3. **Variable Scoping**
   - $ variables are global within formula execution
   - No local scope or variable shadowing
   - **Workaround**: Use unique variable names

### Performance Limitations

1. **Large Dataset Processing**
   - Not optimized for processing thousands of records simultaneously
   - Memory usage scales with dataset size
   - **Workaround**: Process in batches or use code generation

2. **Deep Multi-dimensional Scoring**
   - Very deep nesting can impact performance
   - Complex trees with many branches slow down evaluation
   - **Workaround**: Simplify logic or use alternative approaches

3. **Runtime vs Generated Code**
   - Runtime evaluation is 2-5x slower than generated functions
   - **Workaround**: Use code generation for production environments

### Configuration Limitations

1. **Runtime Error Detection**
   - Some invalid configurations only fail during execution
   - Limited static analysis of configuration validity
   - **Workaround**: Thorough testing with representative data

2. **Type System**
   - Limited type checking for input variables
   - No compile-time type validation
   - **Workaround**: Implement input validation in your application

3. **Circular Dependencies**
   - Detection may not catch all circular reference patterns
   - **Workaround**: Design formulas with clear dependency chains

## Performance Considerations

### Optimization Strategies

- **Code Generation**: Use `generateFunctionAsString()` for production environments
- **Validation**: Validate configurations during development, not runtime
- **Dependencies**: Automatic formula ordering for optimal execution
- **Memory**: Minimal overhead with efficient context management
- **Safety**: No eval() usage, custom secure expression parser

### Performance Expectations

- **Small datasets** (< 100 records): Excellent performance
- **Medium datasets** (100-1000 records): Good performance with code generation
- **Large datasets** (> 1000 records): Consider batch processing
- **Complex rules**: Performance decreases with nesting depth

### Benchmarking

Basic performance characteristics:
- Simple expressions: ~10,000 evaluations/second
- Complex multi-dimensional scoring: ~1,000 evaluations/second
- Code generation provides 2-5x improvement

## Best Practices

### Configuration Design
- Use descriptive formula IDs
- Group related calculations
- Utilize $ notation for internal variables
- Set clear default values
- Include comprehensive validation

### Variable Naming
- Use `$` prefix for calculated/internal variables
- Keep input variable names simple and clear
- Use consistent naming conventions
- Document complex expressions

### Performance Optimization
- Order formulas by dependency
- Use code generation for production
- Minimize complex nested scoring
- Cache frequently used configurations

### Error Prevention
- Validate configurations during development
- Test with representative data
- Use parentheses for complex expressions
- Implement proper input validation

## License

MIT License - see [LICENSE](../LICENSE) file for details.