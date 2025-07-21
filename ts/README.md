# RuleFlow TypeScript

A powerful, flexible rule engine for TypeScript/JavaScript applications with advanced code generation capabilities.

##  Features

### Core Engine
- **Expression Evaluation**: Mathematical formulas with variables and built-in functions
- **Switch Logic**: Conditional branching with AND/OR operations
- **Variable Management**: `$notation` for intermediate calculations and references
- **Code Generation**: Convert rule configurations to optimized TypeScript functions
- **Performance**: Generated code runs 10-100x faster than interpreted rules

### Built-in Functions
- **Math**: `sqrt()`, `pow()`, `abs()`, `min()`, `max()`, `round()`, `ceil()`, `floor()`
- **Statistics**: `avg()`, `sum()`, `count()`, `median()`, `variance()`, `stddev()`
- **Business**: `percentage()`, `compound_interest()`, `discount()`, `bmi()`, `age()`

### Advanced Features
- **Type Safety**: Full TypeScript support with automatic type inference
- **Error Handling**: Comprehensive validation and error reporting
- **Testing**: 27+ comprehensive tests covering all functionality
- **Integration**: Seamless integration with existing TypeScript projects

## Installation

```bash
git clone https://github.com/Jedsadha1777/RuleFlow.git
cd RuleFlow/ts
npm install
npm test
```

## Quick Start

### Basic Usage

```typescript
import { RuleFlow } from 'ruleflow-ts';

const ruleFlow = new RuleFlow();

// Simple calculation
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

### Advanced Example with Variables

```typescript
const advancedConfig = {
  formulas: [
    {
      id: 'subtotal',
      formula: 'price * quantity',
      inputs: ['price', 'quantity'],
      as: '$subtotal'  // Store as variable
    },
    {
      id: 'tax_amount',
      formula: '$subtotal * tax_rate',
      inputs: ['$subtotal', 'tax_rate'],
      as: '$tax'
    },
    {
      id: 'total',
      formula: '$subtotal + $tax',
      inputs: ['$subtotal', '$tax']
    }
  ]
};
```

### Switch Logic

```typescript
const gradeConfig = {
  formulas: [
    {
      id: 'letter_grade',
      switch: 'score',
      when: [
        { if: { op: '>=', value: 90 }, result: 'A' },
        { if: { op: '>=', value: 80 }, result: 'B' },
        { if: { op: '>=', value: 70 }, result: 'C' },
        { if: { op: '>=', value: 60 }, result: 'D' }
      ],
      default: 'F'
    }
  ]
};
```

### Complex Conditions

```typescript
const loanConfig = {
  formulas: [
    {
      id: 'approval',
      switch: 'application_type',
      when: [
        {
          if: {
            and: [
              { op: '>=', var: 'credit_score', value: 700 },
              { op: '<=', var: 'debt_to_income', value: 0.4 },
              { op: '>=', var: 'annual_income', value: 50000 }
            ]
          },
          result: 'approved',
          set_vars: {
            '$interest_rate': 3.5,
            '$max_amount': 500000
          }
        }
      ],
      default: 'rejected'
    }
  ]
};
```

## Code Generation

Generate optimized TypeScript functions from your rule configurations:

```typescript
import { CodeGenerator } from 'ruleflow-ts';

const generator = new CodeGenerator();

const config = {
  formulas: [
    {
      id: 'bmi',
      formula: 'weight / (height ** 2)',
      inputs: ['weight', 'height']
    },
    {
      id: 'category',
      switch: 'bmi',
      when: [
        { if: { op: '>=', value: 30 }, result: 'obese' },
        { if: { op: '>=', value: 25 }, result: 'overweight' },
        { if: { op: '>=', value: 18.5 }, result: 'normal' }
      ],
      default: 'underweight'
    }
  ]
};

const generated = generator.generate(config, {
  functionName: 'calculateBMI',
  includeComments: true,
  includeExamples: true
});

console.log(generated.code);
```

Generated output:
```typescript
interface calculateBMIInputs {
  weight: number;
  height: number;
}

interface calculateBMIOutput {
  bmi: any;
  category: any;
}

export function calculateBMI(inputs: calculateBMIInputs): calculateBMIOutput {
  const result: any = { ...inputs };

  // Formula: bmi
  result.bmi = inputs.weight / (Math.pow(inputs.height, 2));

  // Switch: category
  if (result.bmi >= 30) {
    result.category = "obese";
  }
  else if (result.bmi >= 25) {
    result.category = "overweight";
  }
  else if (result.bmi >= 18.5) {
    result.category = "normal";
  }
  else {
    result.category = "underweight";
  }

  return result;
}
```

## Supported Operations

### Mathematical Operators
| Operator | Description | Example |
|----------|-------------|---------|
| `+` | Addition | `a + b` |
| `-` | Subtraction | `a - b` |
| `*` | Multiplication | `a * b` |
| `/` | Division | `a / b` |
| `**` | Power | `a ** 2` |
| `%` | Modulo | `a % b` |
| `()` | Grouping | `(a + b) * c` |

### Comparison Operators
| Operator | Description | Example |
|----------|-------------|---------|
| `==` | Equal | `{"op": "==", "value": "premium"}` |
| `!=` | Not equal | `{"op": "!=", "value": 0}` |
| `>` | Greater than | `{"op": ">", "value": 1000}` |
| `>=` | Greater or equal | `{"op": ">=", "value": 18}` |
| `<` | Less than | `{"op": "<", "value": 100}` |
| `<=` | Less or equal | `{"op": "<=", "value": 65}` |
| `between` | Range check | `{"op": "between", "value": [18, 65]}` |
| `in` | Value in list | `{"op": "in", "value": ["A", "B", "C"]}` |

### Built-in Functions
```typescript
// Math functions
sqrt(16)        // 4
pow(2, 3)       // 8
abs(-5)         // 5
min(1, 2, 3)    // 1
max(1, 2, 3)    // 3
round(3.14159, 2) // 3.14

// Business functions
percentage(50, 200)  // 25
discount(100, 0.1)   // 90
bmi(70, 1.75)       // 22.86
```

## Real-World Examples

### E-commerce Pricing
```typescript
const pricingConfig = {
  formulas: [
    {
      id: 'base_price',
      formula: 'unit_price * quantity',
      inputs: ['unit_price', 'quantity'],
      as: '$base'
    },
    {
      id: 'discount_amount',
      switch: 'customer_tier',
      when: [
        { if: { op: '==', value: 'vip' }, result: { formula: '$base * 0.2', inputs: ['$base'] }},
        { if: { op: '==', value: 'premium' }, result: { formula: '$base * 0.1', inputs: ['$base'] }},
        { if: { op: '==', value: 'regular' }, result: { formula: '$base * 0.05', inputs: ['$base'] }}
      ],
      default: 0,
      as: '$discount'
    },
    {
      id: 'final_price',
      formula: '$base - $discount + ($base * tax_rate)',
      inputs: ['$base', '$discount', 'tax_rate']
    }
  ]
};
```

### Insurance Risk Assessment
```typescript
const riskConfig = {
  formulas: [
    {
      id: 'age_factor',
      switch: 'age',
      when: [
        { if: { op: 'between', value: [18, 25] }, result: 1.5 },
        { if: { op: 'between', value: [26, 40] }, result: 1.0 },
        { if: { op: 'between', value: [41, 60] }, result: 1.2 }
      ],
      default: 1.8,
      as: '$age_multiplier'
    },
    {
      id: 'risk_score',
      formula: 'base_risk * $age_multiplier * driving_record_factor',
      inputs: ['base_risk', '$age_multiplier', 'driving_record_factor']
    },
    {
      id: 'premium',
      formula: 'base_premium * (1 + risk_score / 100)',
      inputs: ['base_premium', 'risk_score']
    }
  ]
};
```

##  Testing

Run the comprehensive test suite:

```bash
npm test
```

Test coverage includes:
- Basic expression evaluation
- Power operator handling
- Built-in math functions
- Switch logic generation
- Variable references ($notation)
- Real-world scenarios
- Code generation
- Performance optimization
- Error handling
- Edge cases

## Performance

RuleFlow TypeScript offers excellent performance:

- **Rule Engine**: ~1-5ms per evaluation
- **Generated Code**: ~0.01-0.1ms per execution
- **Performance Gain**: 10-100x faster with code generation
- **Memory Usage**: Minimal overhead
- **Scalability**: Handles complex configurations efficiently

## API Reference

### RuleFlow Class

```typescript
class RuleFlow {
  constructor()
  
  // Evaluate rule configuration
  async evaluate(config: any, inputs: any): Promise<any>
  
  // Validate configuration
  validateConfig(config: any): ValidationResult
  
  // Test configuration with sample data
  testConfig(config: any, testCases: any[]): TestResult[]
  
  // Generate TypeScript code
  generateCode(config: any, options?: CodeGenerationOptions): string
  
  // Get generation metadata
  getGenerationMetadata(config: any): GenerationMetadata
}
```

### CodeGenerator Class

```typescript
class CodeGenerator {
  constructor()
  
  // Generate code from configuration
  generate(config: any, options?: CodeGenerationOptions): GeneratedCode
}

interface CodeGenerationOptions {
  functionName?: string
  includeComments?: boolean
  includeExamples?: boolean
  optimizationLevel?: 'basic' | 'aggressive'
  targetFormat?: 'typescript' | 'javascript' | 'module'
}
```

##  License

MIT License - see [LICENSE](../LICENSE) file for details.

## Acknowledgments

- Inspired by business rule engines and domain-specific languages
- Built with TypeScript for type safety and developer experience
- Optimized for both flexibility and performance