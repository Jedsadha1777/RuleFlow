# RuleFlow Tests

Comprehensive test suite for RuleFlow components.

## Test Structure

```
tests/
├── ConfigTemplateManagerTest.php   # Template management tests
├── ConfigValidatorTest.php         # Configuration validation tests  
├── ExpressionEvaluatorTest.php     # Math engine tests
├── FunctionRegistryTest.php        # Function tests
├── InputValidatorTest.php          # Input validation tests
├── SchemaGeneratorTest.php         # Schema generation tests
├── RuleFlowIntegrationTest.php     # End-to-end tests
├── RunAllTests.php                 # Test runner
└── README.md                       # This file
```

## Running Tests

### Run All Tests
```bash
php tests/RunAllTests.php
```

### Run Specific Test Suite
```bash
# Template manager only
php tests/RunAllTests.php --test=template

# Config validator only  
php tests/RunAllTests.php --test=validator

# Expression evaluator only
php tests/RunAllTests.php --test=expression

# Function registry only
php tests/RunAllTests.php --test=functions

# Input validator only
php tests/RunAllTests.php --test=input

# Schema generator only
php tests/RunAllTests.php --test=schema

# Integration tests only
php tests/RunAllTests.php --test=integration
```

### Run Individual Test Files
```bash
php tests/ConfigTemplateManagerTest.php
php tests/ConfigValidatorTest.php
php tests/ExpressionEvaluatorTest.php
php tests/FunctionRegistryTest.php
php tests/InputValidatorTest.php
php tests/SchemaGeneratorTest.php
php tests/RuleFlowIntegrationTest.php
```

### Other Options
```bash
php tests/RunAllTests.php --check    # Check environment
php tests/RunAllTests.php --quick    # Quick functionality test
php tests/RunAllTests.php --list     # List available tests
```

## Test Coverage

### ConfigTemplateManagerTest
- ✅ Get available templates
- ✅ Get specific template
- ✅ Template metadata
- ✅ Get templates by category
- ✅ Search templates
- ✅ Custom template registration
- ✅ Template with parameters
- ✅ Template validation
- ✅ Template cloning and modification
- ✅ Error handling
- ✅ Template organization
- ✅ Template import/export
- ✅ Real-world template usage scenarios

### ConfigValidatorTest  
- ✅ Valid configuration validation
- ✅ Missing formulas key detection
- ✅ Invalid formula structure detection
- ✅ $ notation validation
- ✅ Circular dependency detection
- ✅ Input validation and type conversion
- ✅ Missing required inputs detection
- ✅ Invalid input types detection
- ✅ Extract required inputs from config
- ✅ Warnings detection

### ExpressionEvaluatorTest
- ✅ Basic math operations (+, -, *, /, **)
- ✅ Operator precedence and parentheses
- ✅ Variable replacement
- ✅ Built-in functions (abs, min, max, sqrt, etc.)
- ✅ $ notation expressions
- ✅ Error handling (division by zero, invalid functions)
- ✅ Complex mathematical expressions

### FunctionRegistryTest
- ✅ Math functions (abs, min, max, sqrt, pow, log, sin)
- ✅ Statistics functions (sum, avg, median, count)
- ✅ Business functions (percentage, compound_interest, simple_interest, discount, markup, pmt)
- ✅ Utility functions (clamp, normalize, coalesce, if_null, age_from_year, bmi, percentile)
- ✅ Custom function registration
- ✅ Error handling for invalid inputs
- ✅ Function availability and categorization
- ✅ Edge cases and real-world scenarios

### InputValidatorTest
- ✅ Boundary validation (min/max constraints)
- ✅ String length validation
- ✅ Enum validation
- ✅ Pattern validation (regex)
- ✅ Advanced type conversion (integer, float, boolean, date, percentage, currency, email)
- ✅ Boolean conversion (true/false, yes/no, on/off, enabled/disabled)
- ✅ Auto type detection
- ✅ Apply default values
- ✅ Input sanitization (trim, lowercase, uppercase, strip_tags)
- ✅ Error handling for invalid conversions
- ✅ Email validation
- ✅ Phone number formatting
- ✅ Currency parsing
- ✅ Percentage parsing

### SchemaGeneratorTest
- ✅ Input schema generation
- ✅ JSON Schema generation
- ✅ TypeScript interface generation
- ✅ Validation rules generation (Laravel, Joi, Yup)
- ✅ HTML form generation
- ✅ React component generation
- ✅ Documentation generation
- ✅ Output schema generation
- ✅ OpenAPI schema generation
- ✅ Schema validation constraints
- ✅ Complex form generation
- ✅ Error handling in schema generation
- ✅ Performance with large schemas
- ✅ Custom type generation
- ✅ Real-world scenarios

### RuleFlowIntegrationTest
- ✅ BMI calculator workflow
- ✅ Credit scoring system
- ✅ Multi-dimensional scoring
- ✅ Complex business calculations with functions
- ✅ Code generation functionality
- ✅ Input validation and error handling
- ✅ Custom functions integration
- ✅ $ notation workflow

## Test Examples

### Basic Math Test
```php
$result = $evaluator->safeEval('2 + 3 * 4', []);
// Expected: 14 (not 20 due to precedence)
```

### Variable Test
```php
$vars = ['weight' => 70, 'height' => 1.75];
$result = $evaluator->safeEval('weight / (height ** 2)', $vars);
// Expected: BMI calculation
```

### Function Test
```php
$result = $registry->call('bmi', [70, 1.75]);
// Expected: 22.86
```

### Configuration Test
```php
$config = [
    'formulas' => [
        [
            'id' => 'bmi',
            'formula' => 'weight / ((height / 100) ** 2)',
            'inputs' => ['weight', 'height']
        ]
    ]
];
$result = $engine->evaluate($config, ['weight' => 70, 'height' => 175]);
```

## Expected Output

### Successful Test Run
```bash
RuleFlow Test Suite
==================================================

📋 Found 7 test suite(s)

📋 Running Config Template Manager...
✅ Get available templates passed
✅ Get specific template passed
✅ Template metadata passed
✅ Get templates by category passed
✅ Search templates passed
✅ Custom template registration passed
✅ Template with parameters passed
✅ Template validation passed
✅ Template cloning passed
✅ Error handling passed
✅ Template organization passed
✅ Template import/export passed
✅ Real-world scenarios passed

All ConfigTemplateManager tests passed!

📋 Running Configuration Validator...
✅ Valid configuration passed
✅ Missing formulas key validation passed
✅ Invalid formula structure validation passed
✅ Dollar notation validation passed
✅ Circular dependency detection passed
✅ Input validation passed
✅ Input type conversion passed
✅ Missing required inputs validation passed
✅ Invalid input types validation passed
✅ Extract required inputs passed
✅ Warnings detection passed

All ConfigValidator tests passed!

📋 Running Expression Evaluator...
✅ Basic math operations passed
✅ Operator precedence passed
✅ Variable replacement passed
✅ Built-in functions passed
✅ Dollar expressions passed
✅ Error handling passed
✅ Complex expressions passed

All ExpressionEvaluator tests passed!

📋 Running Function Registry...
✅ Math functions passed
✅ Statistics functions passed
✅ Business functions passed
✅ Utility functions passed
✅ Custom function registration passed
✅ Error handling passed
✅ Function availability passed
✅ Function categorization passed
✅ Edge cases passed
✅ Real-world scenarios passed

All FunctionRegistry tests passed!

📋 Running Input Validator...
✅ Boundary validation passed
✅ String length validation passed
✅ Enum validation passed
✅ Pattern validation passed
✅ Advanced type conversion passed
✅ Boolean conversion passed
✅ Auto type detection passed
✅ Apply defaults passed
✅ Input sanitization passed
✅ Invalid conversion error handling passed
✅ Email validation passed
✅ Phone formatting passed
✅ Currency parsing passed
✅ Percentage parsing passed

All InputValidator tests passed!

📋 Running Schema Generator...
✅ Input schema generation passed
✅ JSON Schema generation passed
✅ TypeScript interface generation passed
✅ Validation rules generation passed
✅ HTML form generation passed
✅ React component generation passed
✅ Documentation generation passed
✅ Output schema generation passed
✅ OpenAPI generation passed
✅ Schema validation constraints passed
✅ Complex form generation passed
⏭️ GraphQL schema generation skipped (method may not be fully implemented)
⏭️ Vue component generation skipped (not implemented)
⏭️ Angular component generation skipped (not implemented)
⏭️ SQL schema generation skipped (not implemented)
⏭️ MongoDB schema generation skipped (not implemented)
✅ Error handling passed
✅ Performance tests passed (Schema: 0.123s, Docs: 0.056s)
✅ Custom type generation passed
✅ Real-world scenarios passed

All SchemaGenerator tests passed!

📋 Running Integration Tests...
✅ BMI calculator workflow passed
✅ Credit scoring workflow passed
✅ Multi-dimensional scoring passed
✅ Complex business calculation passed
✅ Code generation passed
✅ Input validation and error handling passed
✅ Custom functions passed
✅ Dollar notation workflow passed

All Integration tests passed!

📊 TEST SUMMARY
==================================================
✅ Config Template Manager: PASSED
✅ Configuration Validator: PASSED  
✅ Expression Evaluator: PASSED
✅ Function Registry: PASSED
✅ Input Validator: PASSED
✅ Schema Generator: PASSED
✅ Integration Tests: PASSED

--------------------------------------------------
Total Test Suites: 7
Passed: 7
Failed: 0
Duration: 2.34s

ALL TESTS PASSED! RuleFlow is working correctly.
```

## Troubleshooting

### Common Issues
1. **PHP Version**: Requires PHP 8.0+
2. **File Permissions**: Ensure test files are readable
3. **Memory Limit**: Some tests may need more memory for large datasets

### Common Fixes
```bash
# Check PHP version
php --version

# Set memory limit
php -d memory_limit=256M tests/RunAllTests.php

# Check file permissions  
chmod +r tests/*.php
```

## Test Metrics

- **Total Test Methods**: 80+
- **Code Coverage**: Covers all major components
- **Test Types**: Unit tests, Integration tests, Performance tests
- **Assertions**: 300+ test assertions
- **Error Scenarios**: Comprehensive error handling tests