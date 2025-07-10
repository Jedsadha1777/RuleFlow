# 🚀 Nested Logic Feature - AND/OR Conditions

## Overview

RuleFlow now supports **nested logical conditions** with `and` and `or` operators, allowing you to create complex business logic with unlimited nesting depth.

## Basic Syntax

### AND Conditions
All conditions must be true:
```json
{
  "if": {
    "and": [
      {"op": ">", "var": "age", "value": 25},
      {"op": ">", "var": "income", "value": 30000}
    ]
  },
  "result": "approved"
}
```

### OR Conditions  
Any condition can be true:
```json
{
  "if": {
    "or": [
      {"op": ">", "var": "income", "value": 50000},
      {"op": "==", "var": "has_guarantor", "value": true}
    ]
  },
  "result": "approved"
}
```

## Advanced Nested Logic

### Complex Loan Approval Example
```json
{
  "id": "loan_decision",
  "switch": "application_type",
  "when": [
    {
      "if": {
        "and": [
          {"op": ">", "var": "age", "value": 25},
          {
            "or": [
              {"op": ">", "var": "income", "value": 30000},
              {"op": "==", "var": "has_collateral", "value": true}
            ]
          },
          {"op": "!=", "var": "status", "value": "blacklist"}
        ]
      },
      "result": "approved"
    }
  ],
  "default": "rejected"
}
```

**Logic Explanation:**
- Age must be > 25 **AND**
- (Income > 30,000 **OR** has collateral) **AND**  
- Status is not blacklist

### Insurance Risk Assessment
```json
{
  "if": {
    "or": [
      {
        "and": [
          {"op": ">", "var": "age", "value": 65},
          {"op": "==", "var": "smoker", "value": true}
        ]
      },
      {
        "and": [
          {"op": "<", "var": "age", "value": 25},
          {"op": "==", "var": "has_accidents", "value": true}
        ]
      },
      {"op": ">", "var": "claims_count", "value": 3}
    ]
  },
  "result": "high_risk"
}
```

**Logic Explanation:**
- (Age > 65 **AND** smoker) **OR**
- (Age < 25 **AND** has accidents) **OR**
- Claims > 3

## Supported Features

### ✅ Unlimited Nesting
```json
{
  "and": [
    {"op": ">", "var": "x", "value": 10},
    {
      "or": [
        {"op": "==", "var": "y", "value": "A"},
        {
          "and": [
            {"op": "<", "var": "z", "value": 5},
            {"op": "!=", "var": "status", "value": "inactive"}
          ]
        }
      ]
    }
  ]
}
```

### ✅ Variable References
Use `var` to reference different variables:
```json
{
  "and": [
    {"op": ">", "var": "current_income", "value": "$threshold"},
    {"op": ">=", "var": "credit_score", "value": 650}
  ]
}
```

### ✅ All Operators Supported
- **Comparison**: `>`, `>=`, `<`, `<=`, `==`, `!=`
- **Range**: `between`, `in`, `not_in`
- **String**: `contains`, `starts_with`, `ends_with`
- **Custom**: `function`

### ✅ Code Generation
Nested conditions are automatically converted to optimized PHP code:
```php
if (($context['age'] > 25) && 
    (($context['income'] > 30000) || ($context['has_collateral'] == true)) && 
    ($context['status'] != 'blacklist')) {
    $context['loan_decision'] = 'approved';
}
```

## Migration Guide

### Before (Multiple Formulas)
```json
{
  "formulas": [
    {
      "id": "age_check",
      "switch": "age",
      "when": [{"if": {"op": ">", "value": 25}, "result": true}],
      "default": false
    },
    {
      "id": "income_or_collateral",
      "switch": "dummy",
      "when": [
        {
          "if": {"op": "==", "value": "check"},
          "result": "$income > 30000 || $has_collateral"
        }
      ]
    },
    {
      "id": "final_decision",
      "formula": "$age_check && $income_or_collateral && $status != 'blacklist'"
    }
  ]
}
```

### After (Single Nested Formula)
```json
{
  "formulas": [
    {
      "id": "loan_decision",
      "switch": "trigger",
      "when": [
        {
          "if": {
            "and": [
              {"op": ">", "var": "age", "value": 25},
              {
                "or": [
                  {"op": ">", "var": "income", "value": 30000},
                  {"op": "==", "var": "has_collateral", "value": true}
                ]
              },
              {"op": "!=", "var": "status", "value": "blacklist"}
            ]
          },
          "result": "approved"
        }
      ],
      "default": "rejected"
    }
  ]
}
```

## Benefits

### 🎯 **Readability**
Complex business logic is now expressed in clear, hierarchical JSON structure.

### 🚀 **Performance** 
Single evaluation instead of multiple formula dependencies.

### 🔧 **Maintainability**
Business rules are self-contained and easier to modify.

### 📈 **Scalability**
Unlimited nesting supports the most complex business scenarios.

### 🔄 **Backward Compatible**
All existing configurations continue to work unchanged.

## Use Cases

### Financial Services
- Loan approval with multiple criteria
- Credit scoring with risk factors
- Investment eligibility checks

### Insurance
- Premium calculation based on risk factors
- Claims processing automation
- Policy underwriting rules

### E-commerce
- Dynamic pricing based on customer segments
- Promotion eligibility rules
- Inventory management triggers

### HR & Recruiting
- Candidate screening automation
- Performance bonus calculations
- Access control systems

## Testing

Run the comprehensive test suite:
```bash
php NestedLogicTest.php
```

Test your own configurations:
```bash
php nested_logic_demo.php
```

## Performance Notes

- Nested conditions are evaluated left-to-right, short-circuit style
- Code generation produces optimized PHP with proper operator precedence
- Complex nesting (>5 levels) may impact readability more than performance

## Migration Tips

1. **Start Simple**: Begin with basic AND/OR combinations
2. **Test Thoroughly**: Use the provided test suite to verify logic
3. **Document Logic**: Add comments explaining complex business rules
4. **Performance Test**: Measure impact on your specific use cases

---

🎉 **The nested logic feature is now ready for production use!**