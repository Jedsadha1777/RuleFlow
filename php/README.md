# RuleFlow PHP

A declarative rule engine for evaluating business logic from JSON configuration with nested logical conditions, multi-dimensional scoring, custom functions, and $ notation support.

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

## New Features

### Nested Logical Conditions

Create complex business logic with unlimited nesting of AND/OR conditions:

```php
$loanConfig = [
    'formulas' => [
        [
            'id' => 'loan_approval',
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

// Test the nested logic
$result = $engine->evaluate($loanConfig, [
    'application_type' => 'standard',
    'age' => 30,
    'income' => 25000,  // Low income
    'has_collateral' => true,  // But has collateral
    'status' => 'normal'
]);
// Result: approved (because has_collateral compensates for low income)
```

### Custom Functions System

Add domain-specific functions that are automatically discovered:

```php
// src/Functions/BookingFunctions.php
class BookingFunctions implements RuleFlowFunctionProvider
{
    public static function getFunctions(): array
    {
        return [
            'is_valid_booking_date' => [self::class, 'isValidBookingDate'],
            'calculate_weekend_fee' => [self::class, 'calculateWeekendFee'],
            'check_room_availability' => [self::class, 'checkRoomAvailability']
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
    
    public static function checkRoomAvailability($roomId, $date): bool
    {
        // Your availability checking logic here
        return true; // Simplified for example
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
                            ['op' => 'function', 'function' => 'check_room_availability', 'var' => 'room_id'],
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

$bookingResult = $engine->evaluate($bookingConfig, [
    'validate' => true,
    'booking_date' => '2025-08-15',
    'room_id' => 'R001',
    'guest_count' => 2,
    'base_price' => 100,
    'is_weekend' => true
]);
// Result: booking_validation => 'valid', booking_fee => 150.0
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

// Get available functions (including auto-loaded)
$functions = $engine->getAvailableFunctions();
```

### Custom Function Management

```php
// Register individual function
$engine->registerFunction('my_function', function($x) { return $x * 2; });

// Get function information
$info = $engine->getAvailableFunctions();
echo "Auto-loaded functions: " . count($info['auto_loaded']);
```

### Code Generation Methods

```php
// Generate PHP code as string
$code = $engine->generateFunctionAsString($config);
echo $code; // Shows complete optimized PHP function

// Create cached evaluator for repeated use
$evaluator = $engine->createCachedEvaluator($config);
$result = $evaluator($inputs);
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

### 3. Custom Function Calls
```json
{
  "id": "booking_fee",
  "function_call": "calculate_weekend_fee",
  "params": ["$base_price", "is_weekend"]
}
```

### 4. Switch/Case Logic with $ Variables
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

### 5. Accumulative Scoring System
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

### 6. Multi-Dimensional Scoring with $ Variables
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

### Nested Logic Features

#### Unlimited Nesting Depth
```json
{
  "if": {
    "and": [
      {"op": ">", "var": "level1", "value": 10},
      {
        "or": [
          {"op": "==", "var": "level2a", "value": "yes"},
          {
            "and": [
              {"op": "<", "var": "level2b", "value": 5},
              {
                "or": [
                  {"op": "!=", "var": "level3a", "value": "no"},
                  {"op": ">=", "var": "level3b", "value": 100}
                ]
              }
            ]
          }
        ]
      }
    ]
  }
}
```

#### Variable References in Conditions
```json
{
  "if": {
    "and": [
      {"op": ">", "var": "current_income", "value": "$income_threshold"},
      {"op": ">=", "var": "credit_score", "value": 650}
    ]
  }
}
```

## Supported Features

### Operators
- **Math**: `+`, `-`, `*`, `/`, `**` (power)
- **Comparison**: `<`, `<=`, `>`, `>=`, `==`, `!=`
- **Range**: `between` → `{"op": "between", "value": [10, 20]}`
- **Array**: `in` → `{"op": "in", "value": ["red", "blue"]}`
- **String**: `contains`, `starts_with`, `ends_with`
- **Function**: `function` → `{"op": "function", "function": "my_function"}`

### Functions
- **Math**: `abs(x)`, `min(a,b,c)`, `max(a,b,c)`, `sqrt(x)`, `round(x,n)`, `ceil(x)`, `floor(x)`
- **Custom**: Auto-loaded from `Functions/` directory
- **Domain-specific**: Booking, date validation, pricing calculations, etc.

### Logical Operators
- **AND**: All conditions must be true
- **OR**: Any condition can be true
- **Nesting**: Unlimited depth of logical combinations

### Variable Setting with $ Notation
```json
"set_vars": {
  "$status": "approved", 
  "$rate": 5.5,
  "$calculated_amount": "$base * $rate"
}
```

## Code Generation Features

RuleFlow can generate optimized PHP code from your JSON configurations, including nested logic:

### PHP Code as String
```php
// Get the generated code
$phpCode = $engine->generateFunctionAsString($config);
echo $phpCode;

// Example output for nested logic:
/*
function(array $inputs): array {
    $context = $inputs;
    
    // Formula: loan_decision
    $switchValue = $context['application_type'] ?? null;
    if ($switchValue === null) {
        throw new Exception('Switch value \'application_type\' not found');
    }
    if ((($context['age'] > 25) && 
         (($context['income'] > 30000) || ($context['has_collateral'] == true)) && 
         ($context['status'] != 'blacklist'))) {
        $context['loan_decision'] = 'approved';
    } else {
        $context['loan_decision'] = 'rejected';
    }
    
    return $context;
}
*/
```

## Template System

RuleFlow includes 50+ pre-built templates across multiple domains:

### Using Templates

```php
// List all templates
$templates = $engine->getTemplates();

// Get specific template
$loanTemplate = $engine->getTemplate('loan_application');
$result = $engine->evaluate($loanTemplate['config'], $loanInputs);

// Get templates by category
$financialTemplates = $engine->getTemplates('financial');

// Search templates
$creditTemplates = $engine->searchTemplates('credit');
```

### Available Categories
- **Financial**: Loan applications, credit scoring, payment processing
- **Healthcare**: BMI assessment, health risk evaluation
- **HR**: Performance reviews, candidate scoring, salary calculations
- **E-commerce**: Dynamic pricing, customer LTV, recommendation engines
- **Education**: Student grading, assessment scoring
- **Insurance**: Risk assessment, premium calculations
- **Real Estate**: Property valuation, market analysis

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

#### demo15-nested-logic-demonstration.php
Comprehensive nested logic showcase:
- **Bank loan approval** with complex multi-criteria decision making
- **Insurance premium calculation** with risk assessment matrices
- **E-commerce dynamic pricing** with customer tiers and market conditions
- **Access control systems** with role-based permissions
- **Performance benchmarking** of nested logic vs simple conditions
- **Code generation examples** for production deployment
- Real-world business scenarios demonstrating AND/OR logic power

## Custom Functions Development

### Creating Function Providers

1. **Create a new PHP file** in `src/Functions/` directory
2. **Implement the interface** `RuleFlowFunctionProvider`
3. **Functions are auto-discovered** on RuleFlow initialization

```php
// src/Functions/DateFunctions.php
class DateFunctions implements RuleFlowFunctionProvider
{
    public static function getFunctions(): array
    {
        return [
            'is_business_day' => [self::class, 'isBusinessDay'],
            'days_until' => [self::class, 'daysUntil'],
            'is_holiday' => [self::class, 'isHoliday'],
            'format_thai_date' => [self::class, 'formatThaiDate']
        ];
    }
    
    public static function getInfo(): array
    {
        return [
            'name' => 'Date Functions',
            'version' => '1.0.0',
            'description' => 'Thai business date and holiday functions'
        ];
    }
    
    public static function isBusinessDay($date): bool
    {
        $dt = new DateTime($date);
        $dayOfWeek = (int)$dt->format('w');
        
        // Monday=1, Sunday=0
        return $dayOfWeek >= 1 && $dayOfWeek <= 5 && !self::isHoliday($date);
    }
    
    public static function daysUntil($targetDate): int
    {
        $now = new DateTime();
        $target = new DateTime($targetDate);
        return $now->diff($target)->days;
    }
    
    public static function isHoliday($date): bool
    {
        // Thai holidays logic
        $holidays = ['2025-01-01', '2025-04-13', '2025-04-14', '2025-04-15'];
        return in_array($date, $holidays);
    }
    
    public static function formatThaiDate($date): string
    {
        $dt = new DateTime($date);
        $thaiMonths = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
            4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
            7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
            10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
        ];
        
        $day = $dt->format('j');
        $month = $thaiMonths[(int)$dt->format('n')];
        $year = $dt->format('Y') + 543; // Buddhist year
        
        return "$day $month $year";
    }
}
```

### Using Custom Functions in Business Rules

```php
$workflowConfig = [
    'formulas' => [
        [
            'id' => 'can_process_today',
            'switch' => 'check_date',
            'when' => [
                [
                    'if' => [
                        'and' => [
                            ['op' => 'function', 'function' => 'is_business_day', 'var' => 'today'],
                            ['op' => '!=', 'function' => 'is_holiday', 'var' => 'today', 'value' => true],
                            ['op' => '<', 'function' => 'days_until', 'var' => 'deadline', 'value' => 30]
                        ]
                    ],
                    'result' => 'can_process'
                ]
            ],
            'default' => 'cannot_process'
        ],
        [
            'id' => 'thai_formatted_date',
            'function_call' => 'format_thai_date',
            'params' => ['process_date']
        ]
    ]
];

$result = $engine->evaluate($workflowConfig, [
    'check_date' => 'validate',
    'today' => '2025-07-10',
    'deadline' => '2025-08-15',
    'process_date' => '2025-07-10'
]);

// Result: 
// can_process_today => 'can_process'
// thai_formatted_date => '10 กรกฎาคม 2568'
```

## Testing

### Running Tests

```bash
# Run all available tests (includes nested logic tests)
php tests/RunAllTests.php

# Run specific test suite
php tests/RunAllTests.php --test=nested     # Nested logic tests
php tests/RunAllTests.php --test=template   # Template tests
php tests/RunAllTests.php --test=expression # Expression tests
php tests/RunAllTests.php --test=functions  # Function registry tests

# Check test environment
php tests/RunAllTests.php --check

# Quick functionality test (includes nested logic)
php tests/RunAllTests.php --quick

# List available tests
php tests/RunAllTests.php --list
```

### Test Coverage Overview

The enhanced test suite includes:

- **Basic Functionality Tests** — Expression evaluation, operators, functions
- **Nested Logic Tests** — Complex AND/OR conditions, deep nesting
- **$ Notation Tests** — Variable storage, referencing, complex expressions
- **Multi-dimensional Scoring** — Nested conditions, range evaluations
- **Switch/Case Logic** — Conditional branching, default handling
- **Custom Functions** — Auto-discovery, function call testing
- **Error Handling** — Input validation, configuration errors
- **Code Generation** — Function optimization, safety checks, nested logic support

### Nested Logic Test Results

```bash
# Expected output when running nested logic tests
🧪 Nested Logic Test Suite
===========================

Test 1: Basic AND condition
   ✅ AND condition should pass
   ✅ AND condition should fail when age < 18
   ✅ AND condition should fail when income < 25000

Test 2: Basic OR condition
   ✅ OR condition should pass with high income
   ✅ OR condition should pass with guarantor
   ✅ OR condition should fail when both conditions fail

Test 3: Nested AND/OR combination
   ✅ Should approve qualified applicant
   ✅ Should approve with collateral
   ✅ Should reject if age too low
   ✅ Should reject if blacklisted
   ✅ Should reject low income without collateral

📊 Test Results: 20/20 tests passed
🎉 All tests PASSED! Nested logic is working correctly.
```

### Test Limitations

**Current test coverage: ~85%** (improved from 80%)
- Some edge cases in very deep nesting (>10 levels)
- Limited performance testing under extreme load
- Incomplete testing of all operator combinations with nested logic
- Basic validation of generated code optimization for nested conditions

### Known Issues in Testing

- **Expression precedence**: `-2 ** 2` evaluation tests may fail
- **Memory testing**: Large dataset performance not thoroughly tested
- **Circular dependency**: Detection may not catch all cases in complex nesting

### Debugging Failed Tests

If tests fail:
1. Check PHP version (requires 8.0+)
2. Verify all source files are present
3. Run `--check` to validate test environment
4. Review specific test output for error details
5. **For nested logic issues**: Run `php nested_logic_demo.php`

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
// Validate configuration (now includes nested logic validation)
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
- **Nested logic errors**: Malformed AND/OR structures, missing conditions
- **Function errors**: Missing custom functions, invalid function calls

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

2. **Deep Nested Logic**
   - Very deep nesting (>10 levels) may impact performance
   - Complex logical trees with many branches slow down evaluation
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

3. **Function Discovery**
   - Custom functions must follow naming conventions
   - Auto-discovery depends on proper interface implementation
   - **Workaround**: Use manual registration for edge cases

## Best Practices

### Configuration Design
- Use descriptive formula IDs
- Group related calculations
- Utilize $ notation for internal variables
- **Structure nested logic clearly** with proper indentation
- Set clear default values
- Include comprehensive validation

### Variable Naming
- Use `# RuleFlow PHP

A declarative rule engine for evaluating business logic from JSON configuration with nested logical conditions, multi-dimensional scoring, custom functions, and $ notation support.

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

## New Features

### Nested Logical Conditions

Create complex business logic with unlimited nesting of AND/OR conditions:

```php
$loanConfig = [
    'formulas' => [
        [
            'id' => 'loan_approval',
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

// Test the nested logic
$result = $engine->evaluate($loanConfig, [
    'application_type' => 'standard',
    'age' => 30,
    'income' => 25000,  // Low income
    'has_collateral' => true,  // But has collateral
    'status' => 'normal'
]);
// Result: approved (because has_collateral compensates for low income)
```

### Custom Functions System

Add domain-specific functions that are automatically discovered:

```php
// src/Functions/BookingFunctions.php
class BookingFunctions implements RuleFlowFunctionProvider
{
    public static function getFunctions(): array
    {
        return [
            'is_valid_booking_date' => [self::class, 'isValidBookingDate'],
            'calculate_weekend_fee' => [self::class, 'calculateWeekendFee'],
            'check_room_availability' => [self::class, 'checkRoomAvailability']
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
    
    public static function checkRoomAvailability($roomId, $date): bool
    {
        // Your availability checking logic here
        return true; // Simplified for example
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
                            ['op' => 'function', 'function' => 'check_room_availability', 'var' => 'room_id'],
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

$bookingResult = $engine->evaluate($bookingConfig, [
    'validate' => true,
    'booking_date' => '2025-08-15',
    'room_id' => 'R001',
    'guest_count' => 2,
    'base_price' => 100,
    'is_weekend' => true
]);
// Result: booking_validation => 'valid', booking_fee => 150.0
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

// Get available functions (including auto-loaded)
$functions = $engine->getAvailableFunctions();
```

### Custom Function Management

```php
// Register individual function
$engine->registerFunction('my_function', function($x) { return $x * 2; });

// Get function information
$info = $engine->getAvailableFunctions();
echo "Auto-loaded functions: " . count($info['auto_loaded']);
```

### Code Generation Methods

```php
// Generate PHP code as string
$code = $engine->generateFunctionAsString($config);
echo $code; // Shows complete optimized PHP function

// Create cached evaluator for repeated use
$evaluator = $engine->createCachedEvaluator($config);
$result = $evaluator($inputs);
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

### 3. Custom Function Calls
```json
{
  "id": "booking_fee",
  "function_call": "calculate_weekend_fee",
  "params": ["$base_price", "is_weekend"]
}
```

### 4. Switch/Case Logic with $ Variables
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

### 5. Accumulative Scoring System
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

### 6. Multi-Dimensional Scoring with $ Variables
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

### Nested Logic Features

#### Unlimited Nesting Depth
```json
{
  "if": {
    "and": [
      {"op": ">", "var": "level1", "value": 10},
      {
        "or": [
          {"op": "==", "var": "level2a", "value": "yes"},
          {
            "and": [
              {"op": "<", "var": "level2b", "value": 5},
              {
                "or": [
                  {"op": "!=", "var": "level3a", "value": "no"},
                  {"op": ">=", "var": "level3b", "value": 100}
                ]
              }
            ]
          }
        ]
      }
    ]
  }
}
```

#### Variable References in Conditions
```json
{
  "if": {
    "and": [
      {"op": ">", "var": "current_income", "value": "$income_threshold"},
      {"op": ">=", "var": "credit_score", "value": 650}
    ]
  }
}
```

## Supported Features

### Operators
- **Math**: `+`, `-`, `*`, `/`, `**` (power)
- **Comparison**: `<`, `<=`, `>`, `>=`, `==`, `!=`
- **Range**: `between` → `{"op": "between", "value": [10, 20]}`
- **Array**: `in` → `{"op": "in", "value": ["red", "blue"]}`
- **String**: `contains`, `starts_with`, `ends_with`
- **Function**: `function` → `{"op": "function", "function": "my_function"}`

### Functions
- **Math**: `abs(x)`, `min(a,b,c)`, `max(a,b,c)`, `sqrt(x)`, `round(x,n)`, `ceil(x)`, `floor(x)`
- **Custom**: Auto-loaded from `Functions/` directory
- **Domain-specific**: Booking, date validation, pricing calculations, etc.

### Logical Operators
- **AND**: All conditions must be true
- **OR**: Any condition can be true
- **Nesting**: Unlimited depth of logical combinations

### Variable Setting with $ Notation
```json
"set_vars": {
  "$status": "approved", 
  "$rate": 5.5,
  "$calculated_amount": "$base * $rate"
}
```

## Code Generation Features

RuleFlow can generate optimized PHP code from your JSON configurations, including nested logic:

### PHP Code as String
```php
// Get the generated code
$phpCode = $engine->generateFunctionAsString($config);
echo $phpCode;

// Example output for nested logic:
/*
function(array $inputs): array {
    $context = $inputs;
    
    // Formula: loan_decision
    $switchValue = $context['application_type'] ?? null;
    if ($switchValue === null) {
        throw new Exception('Switch value \'application_type\' not found');
    }
    if ((($context['age'] > 25) && 
         (($context['income'] > 30000) || ($context['has_collateral'] == true)) && 
         ($context['status'] != 'blacklist'))) {
        $context['loan_decision'] = 'approved';
    } else {
        $context['loan_decision'] = 'rejected';
    }
    
    return $context;
}
*/
```

## Template System

RuleFlow includes 50+ pre-built templates across multiple domains:

### Using Templates

```php
// List all templates
$templates = $engine->getTemplates();

// Get specific template
$loanTemplate = $engine->getTemplate('loan_application');
$result = $engine->evaluate($loanTemplate['config'], $loanInputs);

// Get templates by category
$financialTemplates = $engine->getTemplates('financial');

// Search templates
$creditTemplates = $engine->searchTemplates('credit');
```

### Available Categories
- **Financial**: Loan applications, credit scoring, payment processing
- **Healthcare**: BMI assessment, health risk evaluation
- **HR**: Performance reviews, candidate scoring, salary calculations
- **E-commerce**: Dynamic pricing, customer LTV, recommendation engines
- **Education**: Student grading, assessment scoring
- **Insurance**: Risk assessment, premium calculations
- **Real Estate**: Property valuation, market analysis

## Examples & Demos

### Basic Usage

#### demo1-basic.php
- Math expressions with price calculations
- Switch/case logic for discounts  
- Built-in functions for grade calculation
- Error handling for missing inputs

 prefix for calculated/internal variables
- Keep input variable names simple and clear
- Use consistent naming conventions
- Document complex expressions

### Nested Logic Best Practices
- **Keep nesting reasonable** (max 5-6 levels for readability)
- **Use meaningful variable names** in conditions
- **Group related conditions** with AND/OR appropriately
- **Test thoroughly** with edge cases
- **Document complex business logic** in comments

### Custom Functions Guidelines
- **Follow naming conventions** (camelCase or snake_case)
- **Keep functions pure** (no side effects)
- **Handle edge cases** gracefully
- **Document function parameters** and return types
- **Test functions independently** before integration

### Performance Optimization
- Order formulas by dependency
- Use code generation for production
- **Optimize nested conditions** by placing most selective conditions first
- **Cache function results** where appropriate
- Minimize complex nested scoring
- Cache frequently used configurations

### Error Prevention
- Validate configurations during development
- Test with representative data
- Use parentheses for complex expressions
- **Validate nested logic structure** before deployment
- Implement proper input validation

## Known Limitations & Solutions

### 🚨 Configuration Limitations

#### 1. **Nested Switch Not Supported**
```php
// ❌ Cannot nest switch inside switch
$config = [
    'formulas' => [
        [
            'id' => 'complex_decision',
            'switch' => 'category',
            'when' => [
                [
                    'if' => ['op' => '==', 'value' => 'premium'],
                    'switch' => 'subcategory',  // ← Error!
                    'when' => [
                        ['if' => ['op' => '==', 'value' => 'gold'], 'result' => 'high_tier']
                    ]
                ]
            ]
        ]
    ]
];

// ✅ Solution: Use separate formulas with intermediate variables
$config = [
    'formulas' => [
        [
            'id' => 'category_check',
            'switch' => 'category',
            'when' => [
                ['if' => ['op' => '==', 'value' => 'premium'], 'result' => 'needs_subcategory_check']
            ],
            'default' => 'basic_tier',
            'as' => '$category_result'
        ],
        [
            'id' => 'final_decision',
            'switch' => '$category_result',
            'when' => [
                [
                    'if' => ['op' => '==', 'value' => 'needs_subcategory_check'],
                    'switch' => 'subcategory',
                    'when' => [
                        ['if' => ['op' => '==', 'value' => 'gold'], 'result' => 'high_tier'],
                        ['if' => ['op' => '==', 'value' => 'silver'], 'result' => 'mid_tier']
                    ],
                    'default' => 'basic_tier'
                ]
            ]
        ]
    ]
];
```

#### 2. **Expression String Restrictions**
```php
// ❌ Special characters in strings cause evaluation errors
$config = [
    'formulas' => [
        [
            'id' => 'rate_calculation',
            'switch' => 'loan_status',
            'when' => [
                ['if' => ['op' => '==', 'value' => 'approved'], 'result' => 4.5]
            ],
            'default' => 'N/A',  // ← Expression error!
            'set_vars' => ['$interest_rate' => 'N/A']  // ← Also error!
        ]
    ]
];

// ✅ Solution: Use valid identifiers or escape properly
$config = [
    'formulas' => [
        [
            'id' => 'rate_calculation',
            'switch' => 'loan_status',
            'when' => [
                ['if' => ['op' => '==', 'value' => 'approved'], 'result' => 4.5]
            ],
            'default' => 0.0,  // Use numeric default
            'set_vars' => ['$interest_rate' => 'not_applicable']  // Valid string
        ]
    ]
];
```

#### 3. **Deep Nesting Performance Impact**
```php
// ⚠️ Very deep nesting (>6 levels) can impact performance
$deepConfig = [
    'formulas' => [
        [
            'id' => 'complex_approval',
            'switch' => 'application_type',
            'when' => [
                [
                    'if' => [
                        'and' => [
                            ['op' => '>', 'var' => 'level1', 'value' => 10],
                            [
                                'or' => [
                                    [
                                        'and' => [
                                            ['op' => '>', 'var' => 'level2a', 'value' => 5],
                                            [
                                                'or' => [
                                                    [
                                                        'and' => [
                                                            ['op' => '>', 'var' => 'level3a', 'value' => 2],
                                                            ['op' => '<', 'var' => 'level3b', 'value' => 100]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'result' => 'approved'
                ]
            ]
        ]
    ]
];

// ✅ Solution: Break into multiple simpler formulas
$optimizedConfig = [
    'formulas' => [
        [
            'id' => 'level1_check',
            'switch' => 'dummy',
            'when' => [
                ['if' => ['op' => '>', 'var' => 'level1', 'value' => 10], 'result' => 'pass']
            ],
            'default' => 'fail',
            'as' => '$level1_result'
        ],
        [
            'id' => 'level2_check',
            'switch' => '$level1_result',
            'when' => [
                [
                    'if' => ['op' => '==', 'value' => 'pass'],
                    'switch' => 'dummy',
                    'when' => [
                        [
                            'if' => [
                                'and' => [
                                    ['op' => '>', 'var' => 'level2a', 'value' => 5],
                                    ['op' => '>', 'var' => 'level3a', 'value' => 2],
                                    ['op' => '<', 'var' => 'level3b', 'value' => 100]
                                ]
                            ],
                            'result' => 'approved'
                        ]
                    ],
                    'default' => 'rejected'
                ]
            ],
            'default' => 'rejected'
        ]
    ]
];
```

#### 4. **Variable Scoping Conflicts**
```php
// ⚠️ Global $ variables can overwrite each other
$config = [
    'formulas' => [
        ['id' => 'calc1', 'formula' => 'a * 2', 'inputs' => ['a'], 'as' => '$temp'],
        ['id' => 'calc2', 'formula' => 'b * 3', 'inputs' => ['b'], 'as' => '$temp'],  // Overwrites!
        ['id' => 'final', 'formula' => '$temp + 10', 'inputs' => ['temp']]  // Unpredictable
    ]
];

// ✅ Solution: Use unique, descriptive variable names
$config = [
    'formulas' => [
        ['id' => 'calc1', 'formula' => 'a * 2', 'inputs' => ['a'], 'as' => '$calc1_result'],
        ['id' => 'calc2', 'formula' => 'b * 3', 'inputs' => ['b'], 'as' => '$calc2_result'],
        ['id' => 'final', 'formula' => '$calc1_result + $calc2_result + 10', 'inputs' => ['calc1_result', 'calc2_result']]
    ]
];
```

### 🔧 Runtime Limitations

#### 5. **Expression Evaluation Edge Cases**
```php
// ❌ Operator precedence issues
$config = [
    'formulas' => [
        ['id' => 'power_calc', 'formula' => '-2 ** 2', 'inputs' => []]  // Returns -4, not 4
    ]
];

// ✅ Solution: Use explicit parentheses
$config = [
    'formulas' => [
        ['id' => 'power_calc', 'formula' => '(-2) ** 2', 'inputs' => []],  // Returns 4
        ['id' => 'negative_power', 'formula' => '-(2 ** 2)', 'inputs' => []]  // Returns -4 (if intended)
    ]
];
```

#### 6. **Custom Function Discovery Issues**
```php
// ❌ Functions not found due to naming or interface issues
// Functions/BadExample.php
class BadExample  // Missing interface!
{
    public function myFunction() { return 42; }  // Wrong signature!
}

// ✅ Solution: Follow interface and naming conventions
// Functions/GoodExample.php
class GoodExample implements RuleFlowFunctionProvider
{
    public static function getFunctions(): array
    {
        return [
            'my_function' => [self::class, 'myFunction']
        ];
    }
    
    public static function getInfo(): array
    {
        return [
            'name' => 'Good Example Functions',
            'version' => '1.0.0',
            'description' => 'Example of proper function provider'
        ];
    }
    
    public static function myFunction($input): int  // Correct static method
    {
        return (int)$input * 2;
    }
}
```

### 📊 Performance Optimization

#### 7. **Large Dataset Processing**
```php
// ❌ Inefficient: Processing many records individually
$results = [];
foreach ($largeDataset as $record) {
    $results[] = $ruleFlow->evaluate($config, $record);  // Slow, repeated validation
}

// ✅ Solution: Use cached evaluator for batch processing
$evaluator = $ruleFlow->createCachedEvaluator($config);  // Validate once
$results = [];
foreach ($largeDataset as $record) {
    $results[] = $evaluator($record);  // 2-5x faster
}

// ✅ Even better: Use array_map for functional approach
$evaluator = $ruleFlow->createCachedEvaluator($config);
$results = array_map($evaluator, $largeDataset);
```

#### 8. **Condition Ordering for Performance**
```php
// ⚠️ Expensive operations first (inefficient)
$config = [
    'formulas' => [
        [
            'id' => 'complex_check',
            'switch' => 'evaluate',
            'when' => [
                [
                    'if' => [
                        'and' => [
                            ['op' => 'function', 'function' => 'expensive_calculation', 'var' => 'data'],  // Slow
                            ['op' => '>', 'var' => 'simple_number', 'value' => 1000],  // Fast but checked last
                            ['op' => '==', 'var' => 'status', 'value' => 'active']  // Fast but checked last
                        ]
                    ],
                    'result' => 'approved'
                ]
            ]
        ]
    ]
];

// ✅ Solution: Order conditions by computational cost (fast → slow)
$config = [
    'formulas' => [
        [
            'id' => 'optimized_check',
            'switch' => 'evaluate',
            'when' => [
                [
                    'if' => [
                        'and' => [
                            ['op' => '==', 'var' => 'status', 'value' => 'active'],  // Fastest first
                            ['op' => '>', 'var' => 'simple_number', 'value' => 1000],  // Medium speed
                            ['op' => 'function', 'function' => 'expensive_calculation', 'var' => 'data']  // Slowest last
                        ]
                    ],
                    'result' => 'approved'
                ]
            ]
        ]
    ]
];
```

### 🛠️ Best Practices Summary

#### Configuration Design
- **Avoid nested switches** - use separate formulas instead
- **Use valid string identifiers** - avoid special characters in expressions
- **Keep nesting depth ≤ 5 levels** for optimal performance
- **Use descriptive, unique variable names** to prevent conflicts

#### Performance Optimization
- **Order conditions by selectivity** (most restrictive/fastest first)
- **Use cached evaluators** for repeated evaluation
- **Break complex logic** into multiple simple formulas
- **Test with representative data** before production deployment

## Migration Guide

### Upgrading to v1.5.0 (Nested Logic + Custom Functions)

All existing configurations remain **100% backward compatible**. New features are additive.

#### Adding Nested Logic to Existing Rules

**Before** (Multiple separate formulas):
```php
$oldConfig = [
    'formulas' => [
        ['id' => 'age_check', 'switch' => 'age', 'when' => [['if' => ['op' => '>', 'value' => 25], 'result' => true]], 'default' => false],
        ['id' => 'income_check', 'switch' => 'income', 'when' => [['if' => ['op' => '>', 'value' => 30000], 'result' => true]], 'default' => false],
        ['id' => 'final_decision', 'formula' => '$age_check && $income_check', 'inputs' => ['age_check', 'income_check']]
    ]
];
```

**After** (Single nested formula):
```php
$newConfig = [
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

#### Adding Custom Functions

1. **Create function provider file** in `src/Functions/`
2. **Implement RuleFlowFunctionProvider interface**
3. **Functions are automatically available** in all configurations

## Performance Benchmarks

### Nested Logic Performance
- **Simple AND/OR (2-3 conditions)**: ~8,000 evaluations/second
- **Complex nesting (5-6 levels)**: ~3,000 evaluations/second
- **Very deep nesting (>8 levels)**: ~1,000 evaluations/second
- **Code generation improvement**: 2-3x faster than runtime

### Custom Functions Performance
- **Built-in functions**: ~10,000 calls/second
- **Simple custom functions**: ~8,000 calls/second
- **Complex custom functions**: Depends on implementation
- **Auto-discovery overhead**: Negligible (cached after first load)

## License

MIT License - see [LICENSE](../LICENSE) file for details.

---

**RuleFlow PHP v1.5.0** - Now with nested logic and custom functions! Transform the most complex business rules into clean, maintainable JSON configurations. 🚀