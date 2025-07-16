# RuleFlow TypeScript

> **🚧 Work in Progress** - TypeScript port of RuleFlow rule engine

A declarative business logic engine for TypeScript/JavaScript that processes complex business rules from JSON configuration.

## ✅ Features Implemented

### Core Engine
- ✅ **Expression Evaluation** - Math expressions with variable substitution
- ✅ **Switch/Case Logic** - Conditional branching with when/default
- ✅ **Nested Logic** - Complex AND/OR conditions with unlimited nesting
- ✅ **Variable Setting** - Dynamic variable assignment with `set_vars`
- ✅ **Type Conversion** - Auto-convert strings to numbers/booleans

### Function System  
- ✅ **Function Registry** - 25+ built-in functions across 4 categories
- ✅ **Custom Functions** - Register your own functions
- ✅ **Function Categories** - Math, Statistics, Business, Utility
- ✅ **Expression Integration** - Use functions in formulas and set_vars

## 🚧 Coming Soon

- 🔄 **Code Generation** - Convert rules to optimized TypeScript functions
- 📋 **Template System** - Pre-built configurations for common scenarios  
- 🏆 **Advanced Scoring** - Multi-dimensional and accumulative scoring
- 📝 **Schema Generator** - Auto-generate JSON schemas
- ✅ **Enhanced Validation** - Extended configuration validation

## Quick Start

```bash
npm install
npm run test    # Run all tests
npm run build   # Build for production
```

### Dev
```bash
npm install
npm run dev     # → http://localhost:5173
Development

Debug Console: /debug.html
Tests: npm run test
Build: npm run build

Usage
```typescript
import { RuleFlow } from '@ruleflow/core'

const engine = new RuleFlow()
const result = await engine.evaluate(config, inputs)
```

## Basic Usage

```typescript
import { RuleFlow } from './src/RuleFlow.js';

const ruleFlow = new RuleFlow();

// Simple calculation
const config = {
  formulas: [
    {
      id: 'total',
      formula: 'price * quantity',
      inputs: ['price', 'quantity']
    }
  ]
};

const result = await ruleFlow.evaluate(config, { price: 100, quantity: 2 });
console.log(result.total); // 200
```

## Advanced Examples

### Nested Logic
```typescript
const config = {
  formulas: [
    {
      id: 'loan_decision',
      switch: 'application_type',
      when: [
        {
          if: {
            and: [
              { op: '>', var: 'age', value: 25 },
              {
                or: [
                  { op: '>', var: 'income', value: 30000 },
                  { op: '==', var: 'has_collateral', value: true }
                ]
              }
            ]
          },
          result: 'approved'
        }
      ],
      default: 'rejected'
    }
  ]
};
```

### Functions in Expressions
```typescript
const config = {
  formulas: [
    {
      id: 'bmi_calculation',
      formula: 'round(bmi(weight, height), 1)',
      inputs: ['weight', 'height']
    },
    {
      id: 'category',
      switch: 'bmi_calculation',
      when: [
        { if: { op: '<', value: 18.5 }, result: 'Underweight' },
        { if: { op: '<=', value: 24.9 }, result: 'Normal' },
        { if: { op: '>', value: 24.9 }, result: 'Overweight' }
      ]
    }
  ]
};
```

### Custom Functions
```typescript
// Register custom function
ruleFlow.registerFunction('tax_calc', (income: number, rate: number) => {
  return income * rate;
}, {
  category: 'Business',
  description: 'Calculate tax amount'
});

// Use in formulas
const config = {
  formulas: [
    {
      id: 'net_income',
      formula: 'gross_income - tax_calc(gross_income, 0.2)',
      inputs: ['gross_income']
    }
  ]
};
```

## Built-in Functions

### Math Functions
`abs`, `min`, `max`, `sqrt`, `round`, `ceil`, `floor`, `pow`, `log`, `exp`

### Statistics Functions  
`avg`, `sum`, `count`, `median`, `variance`, `stddev`

### Business Functions
`percentage`, `compound_interest`, `simple_interest`, `discount`, `markup`

### Utility Functions
`clamp`, `normalize`, `coalesce`, `if_null`, `bmi`, `age`

## Function Registry

```typescript
// Get available functions
const functions = ruleFlow.getAvailableFunctions();
console.log(functions.functions); // ['abs', 'min', 'max', ...]
console.log(functions.categories); // { Math: [...], Statistics: [...] }

// Get function info
const registry = ruleFlow.getFunctionRegistry();
const info = registry.getFunctionInfo('bmi');
console.log(info); // { name: 'bmi', category: 'Utility', ... }
```

## Testing

```bash
npm run test                # Run all tests
npm run test:watch         # Watch mode
npm run test:ui            # UI mode
```

**Test Coverage:**
- ✅ Core engine (12 tests)
- ✅ Nested logic (6 tests) 
- ✅ Function registry (24 tests)
- ✅ Function integration (18 tests)

**Total: 60+ tests passing**

## File Structure

```
ts/src/
├── index.ts                     # Main exports
├── RuleFlow.ts                  # Main RuleFlow class
├── types.ts                     # Type definitions
├── exceptions/
│   └── RuleFlowException.ts     # Custom exceptions
├── core/
│   ├── ExpressionEvaluator.ts   # Expression evaluation with functions
│   └── FormulaProcessor.ts      # Formula processing logic
├── functions/
│   ├── types.ts                 # Function-related types
│   └── FunctionRegistry.ts      # Function registry system
└── validators/
    ├── ConfigValidator.ts       # Configuration validation
    └── InputValidator.ts        # Input validation
```

## Contributing

This is a port of the PHP RuleFlow engine. Current focus:

1. ✅ **Core Features** - Expression evaluation, nested logic, functions
2. 🔄 **Advanced Features** - Code generation, templates, scoring
3. 📝 **Documentation** - Complete API docs and examples

## License

MIT License

---

**Note:** This TypeScript version is under active development. For production use, consider the mature [PHP version](../php/README.md) until TypeScript version reaches feature parity.