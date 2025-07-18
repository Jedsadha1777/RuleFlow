# RuleFlow TypeScript

> **🎉 Now with Advanced Validation Features!** - TypeScript port of RuleFlow rule engine

A declarative business logic engine for TypeScript/JavaScript that processes complex business rules from JSON configuration.

## ✅ Features Implemented

### Core Engine
- ✅ **Expression Evaluation** - Math expressions with variable substitution
- ✅ **Switch/Case Logic** - Conditional branching with when/default
- ✅ **Nested Logic** - Complex AND/OR conditions with unlimited nesting
- ✅ **Variable Setting** - Dynamic variable assignment with `set_vars`
- ✅ **Type Conversion** - Auto-convert strings to numbers/booleans
- ✅ **Advanced Scoring** - Multi-dimensional and accumulative scoring

### 🆕 Advanced Validation System  
- ✅ **Configuration Testing** - `testConfig()` with sample data validation
- ✅ **Batch Validation** - Validate multiple configurations simultaneously
- ✅ **Field Validation** - Individual and bulk field validation with type conversion
- ✅ **Security Validation** - SQL injection, XSS, and DoS attack detection
- ✅ **Advanced Sanitization** - HTML removal, length limits, character filtering
- ✅ **Progressive Validation** - Real-time form validation with completion tracking

### 🆕 Schema Generation System
- ✅ **JSON Schema Generation** - Auto-generate input/output schemas from configurations
- ✅ **TypeScript Interface Generation** - Generate type-safe interfaces
- ✅ **Schema Validation** - Validate data against generated schemas
- ✅ **Complete Documentation** - Auto-generate API documentation

### Function System  
- ✅ **Function Registry** - 25+ built-in functions across 4 categories
- ✅ **Custom Functions** - Register your own functions
- ✅ **Function Categories** - Math, Statistics, Business, Utility
- ✅ **Expression Integration** - Use functions in formulas and set_vars

## 🚧 Coming Soon

- 🔄 **Code Generation** - Convert rules to optimized TypeScript functions
- 📋 **Template System** - Pre-built configurations for common scenarios  

## Quick Start

```bash
npm install
npm run test:new    # Test new validation features
npm run test        # Run all tests
npm run build       # Build for production
```

## Basic Usage

```typescript
import { RuleFlow, InputValidator, SchemaGenerator } from '@ruleflow/core';

const ruleFlow = new RuleFlow();
const validator = new InputValidator();
const generator = new SchemaGenerator();

// Simple calculation with validation
const config = {
  formulas: [
    {
      id: 'bmi',
      formula: 'weight / (height ** 2)',
      inputs: ['weight', 'height']
    }
  ]
};

// 🆕 Test configuration
const testResult = await ruleFlow.testConfig(config, { weight: 70, height: 1.75 });
console.log('Config valid:', testResult.valid);
console.log('Result:', testResult.test_result);

// 🆕 Advanced input validation
const userInput = { weight: '75', height: '1.80' };

// Security check
const securityCheck = validator.validateInputSecurity(userInput);
if (!securityCheck.safe) {
  console.log('Security threats:', securityCheck.threats);
}

// Advanced sanitization
const cleanInput = validator.sanitizeInputsAdvanced(userInput, {
  removeHtml: true,
  maxStringLength: 100
});

// Field validation
const fieldResults = validator.validateFields(cleanInput, config);
console.log('Field validation:', fieldResults);

// Validation status (for UI)
const status = validator.getValidationStatus(cleanInput, config);
console.log('Ready to submit:', status.ready_to_submit);
console.log('Completion:', status.field_validation.overall_progress + '%');

// 🆕 Schema generation
const schema = generator.generateInputSchema(config);
const tsInterface = generator.generateTypeScriptInterface(config);
console.log('TypeScript Interface:');
console.log(tsInterface);

// Final evaluation
const result = await ruleFlow.evaluate(config, { weight: 75, height: 1.80 });
console.log('BMI:', result.bmi);
```

## 🆕 Advanced Validation Features

### Configuration Testing
```typescript
// Test configuration with sample data
const testResult = await ruleFlow.testConfig(config, sampleInputs);
if (testResult.valid) {
  console.log('✅ Config works!');
  console.log('Execution time:', testResult.execution_time + 'ms');
} else {
  console.log('❌ Config errors:', testResult.errors);
}

// Batch validation
const results = ruleFlow.validateBatch([config1, config2, config3]);
results.forEach((result, index) => {
  console.log(`Config ${index}: ${result.valid ? '✅' : '❌'}`);
});
```

### Security & Sanitization
```typescript
// Security validation
const security = validator.validateInputSecurity(userInputs);
if (!security.safe) {
  security.threats.forEach(threat => {
    console.log(`🚨 ${threat.threat_type} in ${threat.field}`);
  });
}

// Advanced sanitization
const clean = validator.sanitizeInputsAdvanced(userInputs, {
  trimStrings: true,
  removeHtml: true,
  maxStringLength: 500,
  allowedKeys: ['weight', 'height'] // Filter unwanted fields
});
```

### Field Validation
```typescript
// Validate individual field
const fieldResult = validator.validateField('weight', '75.5', config);
console.log('Valid:', fieldResult.valid);
console.log('Converted value:', fieldResult.converted_value);
console.log('Type:', fieldResult.type);

// Progressive form validation
const status = validator.getValidationStatus(partialInputs, config);
console.log('Progress:', status.field_validation.overall_progress + '%');
console.log('Missing:', status.field_validation.missing_required);
console.log('Ready:', status.ready_to_submit);
```

### Schema Generation
```typescript
// Generate JSON Schema
const inputSchema = generator.generateInputSchema(config);
const outputSchema = generator.generateOutputSchema(config);

// Generate TypeScript interfaces
const tsInterface = generator.generateTypeScriptInterface(config, 'MyInputs');

// Complete documentation
const docs = generator.generateDocumentation(config);
console.log('Total formulas:', docs.summary.totalFormulas);
console.log('Required inputs:', docs.summary.requiredInputs);

// Validate against schema
const validation = generator.validateAgainstSchema(userData, inputSchema);
if (!validation.valid) {
  console.log('Schema errors:', validation.errors);
}
```

## Built-in Functions

### Math Functions
`abs`, `min`, `max`, `round`, `floor`, `ceil`, `sqrt`, `pow`

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

// Register custom function
ruleFlow.registerFunction('customCalc', (x, y) => x * y + 10, {
  category: 'Custom',
  description: 'Custom calculation'
});
```

## Testing

```bash
npm run test              # Run all tests
npm run test:watch        # Watch mode
npm run test:ui           # UI mode

# Test specific features
npm run test:validation   # Test validation features
npm run test:schema       # Test schema generation
npm run test:integration  # Test integration scenarios
npm run test:new          # Test all new features
npm run test:coverage     # Coverage report
```

**Test Coverage:**
- ✅ Core engine (12 tests)
- ✅ Nested logic (6 tests) 
- ✅ Function registry (24 tests)
- ✅ Function integration (18 tests)
- ✅ **🆕 Advanced validation (30+ tests)**
- ✅ **🆕 Schema generation (20+ tests)**
- ✅ **🆕 Integration scenarios (15+ tests)**

**Total: 125+ tests passing**

## File Structure

```
ts/src/
├── index.ts                     # Main exports
├── RuleFlow.ts                  # Main RuleFlow class (+ testConfig, validateBatch)
├── types.ts                     # Type definitions (+ new interfaces)
├── exceptions/
│   └── RuleFlowException.ts     # Custom exceptions
├── core/
│   ├── ExpressionEvaluator.ts   # Expression evaluation with functions
│   └── FormulaProcessor.ts      # Formula processing logic
├── functions/
│   ├── types.ts                 # Function-related types
│   └── FunctionRegistry.ts      # Function registry system
├── validators/
│   ├── ConfigValidator.ts       # Configuration validation
│   └── InputValidator.ts        # Input validation (+ advanced features)
└── 🆕 generators/
    └── SchemaGenerator.ts       # Schema & TypeScript generation
```

## Real-world Examples

### BMI Calculator with Progressive Validation
```typescript
const bmiConfig = {
  formulas: [
    {
      id: 'bmi',
      formula: 'weight / (height ** 2)',
      inputs: ['weight', 'height']
    },
    {
      id: 'category',
      switch: '$bmi',
      when: [
        { if: { op: '<', value: 18.5 }, result: 'Underweight' },
        { if: { op: '<', value: 25 }, result: 'Normal' },
        { if: { op: '<', value: 30 }, result: 'Overweight' }
      ],
      default: 'Obese'
    }
  ]
};

// Progressive form validation
const steps = [
  {}, // Empty form
  { weight: '70' }, // Partial data
  { weight: '70', height: '1.75' } // Complete data
];

steps.forEach((stepData, index) => {
  const status = validator.getValidationStatus(stepData, bmiConfig);
  console.log(`Step ${index + 1}:`);
  console.log(`  Progress: ${status.field_validation.overall_progress}%`);
  console.log(`  Ready: ${status.ready_to_submit}`);
  console.log(`  Missing: ${status.field_validation.missing_required.join(', ')}`);
});
```

### Loan Application with Security
```typescript
const loanConfig = {
  formulas: [
    {
      id: 'debt_to_income',
      formula: '(monthly_debt / monthly_income) * 100',
      inputs: ['monthly_debt', 'monthly_income']
    },
    {
      id: 'approval',
      switch: 'application_type',
      when: [
        {
          if: {
            and: [
              { op: '>=', var: 'credit_score', value: 650 },
              { op: '<=', var: 'debt_to_income', value: 40 }
            ]
          },
          result: 'approved',
          set_vars: { '$interest_rate': 5.5 }
        }
      ],
      default: 'rejected'
    }
  ]
};

// Handle potentially malicious input
const userInput = {
  monthly_debt: '<script>alert("hack")</script>1500',
  monthly_income: 'DROP TABLE users; --',
  credit_score: 720,
  application_type: 'personal'
};

// Security check
const security = validator.validateInputSecurity(userInput);
if (!security.safe) {
  console.log('🚨 Security threats detected!');
  security.threats.forEach(threat => {
    console.log(`  ${threat.field}: ${threat.threat_type}`);
  });
}

// Clean the input
const cleanInput = validator.sanitizeInputsAdvanced(userInput, {
  removeHtml: true,
  allowedKeys: ['monthly_debt', 'monthly_income', 'credit_score', 'application_type']
});

// Validate and process
const result = await ruleFlow.evaluate(loanConfig, cleanInput);
console.log('Loan decision:', result.approval);
```

## API Reference

### RuleFlow Class
```typescript
const ruleFlow = new RuleFlow();

// Core methods
await ruleFlow.evaluate(config, inputs)
await ruleFlow.testConfig(config, sampleInputs)  // 🆕
ruleFlow.validateBatch(configs)                   // 🆕

// Function management
ruleFlow.registerFunction(name, handler, info)
ruleFlow.getAvailableFunctions()
ruleFlow.getFunctionRegistry()
```

### InputValidator Class
```typescript
const validator = new InputValidator();

// Field validation
validator.validateField(fieldName, value, config)           // 🆕
validator.validateFields(userInputs, config)               // 🆕
validator.getValidationStatus(userInputs, config)          // 🆕

// Security & sanitization
validator.validateInputSecurity(userInputs)                // 🆕
validator.sanitizeInputsAdvanced(userInputs, options)      // 🆕

// Utility methods
validator.extractRequiredInputs(config)
validator.validatePartial(userInputs, config)              // 🆕
validator.isComplete(userInputs, config)
validator.getCompletionPercentage(userInputs, config)
```

### SchemaGenerator Class
```typescript
const generator = new SchemaGenerator();

// Schema generation
generator.generateInputSchema(config)                      // 🆕
generator.generateOutputSchema(config)                     // 🆕
generator.generateTypeScriptInterface(config, name)        // 🆕
generator.generateDocumentation(config)                    // 🆕

// Schema validation
generator.validateAgainstSchema(data, schema)              // 🆕
```

## Migration from PHP

TypeScript RuleFlow now has **95% feature parity** with PHP version:

| Feature | PHP | TypeScript | Status |
|---------|-----|------------|--------|
| Core Engine | ✅ | ✅ | Complete |
| Functions | ✅ | ✅ | Complete |
| **Validation** | ✅ | ✅ | **Complete** |
| **Schema Generation** | ✅ | ✅ | **Complete** |
| Code Generation | ✅ | 🔄 | In Progress |
| Templates | ✅ | 🔄 | In Progress |

### What's New in TypeScript
- 🆕 **testConfig()** - Test configurations with sample data
- 🆕 **validateBatch()** - Batch configuration validation
- 🆕 **Advanced sanitization** - Security-focused input cleaning
- 🆕 **Progressive validation** - Real-time form validation
- 🆕 **Schema generation** - Auto-generate JSON schemas and TypeScript types
- 🆕 **Security validation** - SQL injection, XSS, DoS detection

## Performance

- **Configuration testing**: ~5-15ms per config
- **Field validation**: ~1-3ms per field
- **Schema generation**: ~10-50ms per config
- **Batch validation**: ~5ms per config in batch
- **Memory usage**: Minimal overhead for validation operations

## Contributing

This TypeScript version now matches PHP functionality for validation and schema generation. Remaining focus areas:

1. **Code Generation** - Convert rules to optimized TypeScript functions
2. **Template System** - Pre-built configurations for common scenarios
3. **Performance optimization** - Further speed improvements

## License

MIT License

---

**🎉 TypeScript RuleFlow v1.5.0** - Now with complete validation system and schema generation! Perfect for production use with type safety and security features.