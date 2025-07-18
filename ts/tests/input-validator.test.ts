import { describe, it, expect } from 'vitest';
import { InputValidator, FieldValidationResult, ValidationStatus } from '../src/validators/InputValidator';
import { ConfigValidator, ConfigValidationResult } from '../src/validators/ConfigValidator';
import { RuleFlowException } from '../src/exceptions/RuleFlowException';
import { RuleFlowConfig } from '../src/types';

describe('InputValidator', () => {
  const validator = new InputValidator();

  describe('extractRequiredInputs', () => {
    it('should extract inputs from formula string', () => {
      const config: RuleFlowConfig = {
        formulas: [{
          id: 'test',
          formula: 'a + b * c'
        }]
      };

      const inputs = validator.extractRequiredInputs(config);
      expect(inputs).toEqual(['a', 'b', 'c']);
    });

    it('should extract inputs from inputs array', () => {
      const config: RuleFlowConfig = {
        formulas: [{
          id: 'test',
          inputs: ['x', 'y', 'z']
        }]
      };

      const inputs = validator.extractRequiredInputs(config);
      expect(inputs).toEqual(['x', 'y', 'z']);
    });

    it('should extract switch variable', () => {
      const config: RuleFlowConfig = {
        formulas: [{
          id: 'test',
          switch: 'category',
          when: []
        }]
      };

      const inputs = validator.extractRequiredInputs(config);
      expect(inputs).toEqual(['category']);
    });

    it('should filter out reserved words', () => {
      const config: RuleFlowConfig = {
        formulas: [{
          id: 'test',
          formula: 'max(price, min(cost, 100))'
        }]
      };

      const inputs = validator.extractRequiredInputs(config);
      expect(inputs).toEqual(['cost', 'price']);
      expect(inputs).not.toContain('max');
      expect(inputs).not.toContain('min');
    });

    it('should handle complex config', () => {
      const config: RuleFlowConfig = {
        formulas: [
          {
            id: 'calc1',
            formula: 'price * quantity',
            inputs: ['tax_rate']
          },
          {
            id: 'calc2',
            switch: 'category'
          }
        ]
      };

      const inputs = validator.extractRequiredInputs(config);
      expect(inputs).toEqual(['category', 'price', 'quantity', 'tax_rate']);
    });

    it('should remove duplicates', () => {
      const config: RuleFlowConfig = {
        formulas: [
          {
            id: 'calc1',
            formula: 'price + tax',
            inputs: ['price']
          },
          {
            id: 'calc2',
            switch: 'price'
          }
        ]
      };

      const inputs = validator.extractRequiredInputs(config);
      const priceCount = inputs.filter(input => input === 'price').length;
      expect(priceCount).toBe(1);
    });
  });

  describe('getMissingInputs', () => {
    const config: RuleFlowConfig = {
      formulas: [{
        id: 'test',
        inputs: ['a', 'b', 'c']
      }]
    };

    it('should find missing inputs', () => {
      const userInputs = { a: 1, c: 3 };
      const missing = validator.getMissingInputs(userInputs, config);
      expect(missing).toEqual(['b']);
    });

    it('should return empty array when all inputs provided', () => {
      const userInputs = { a: 1, b: 2, c: 3 };
      const missing = validator.getMissingInputs(userInputs, config);
      expect(missing).toEqual([]);
    });

    it('should return all required when no inputs provided', () => {
      const missing = validator.getMissingInputs({}, config);
      expect(missing).toEqual(['a', 'b', 'c']);
    });
  });

  describe('convertValue', () => {
    it('should convert string numbers to numbers', () => {
      expect(validator.convertValue('25')).toBe(25);
      expect(validator.convertValue('25.5')).toBe(25.5);
      expect(validator.convertValue('-10')).toBe(-10);
    });

    it('should convert string booleans to booleans', () => {
      expect(validator.convertValue('true')).toBe(true);
      expect(validator.convertValue('false')).toBe(false);
      expect(validator.convertValue('TRUE')).toBe(true);
      expect(validator.convertValue('False')).toBe(false);
    });

    it('should trim and return strings', () => {
      expect(validator.convertValue('  hello world  ')).toBe('hello world');
      expect(validator.convertValue('test')).toBe('test');
    });

    it('should return null/undefined as-is', () => {
      expect(validator.convertValue(null)).toBe(null);
      expect(validator.convertValue(undefined)).toBe(undefined);
    });

    it('should return numbers and booleans as-is', () => {
      expect(validator.convertValue(42)).toBe(42);
      expect(validator.convertValue(true)).toBe(true);
      expect(validator.convertValue(false)).toBe(false);
    });
  });

  describe('validateField', () => {
    const config: RuleFlowConfig = {
      formulas: [{
        id: 'test',
        inputs: ['age', 'name']
      }]
    };

    it('should validate required field with valid value', () => {
      const result = validator.validateField('age', '25', config);
      expect(result.valid).toBe(true);
      expect(result.converted_value).toBe(25);
      expect(result.type).toBe('number');
      expect(result.errors).toHaveLength(0);
    });

    it('should detect empty required field', () => {
      const result = validator.validateField('age', '', config);
      expect(result.valid).toBe(false);
      expect(result.errors).toContain("Field 'age' is required and cannot be empty");
    });

    it('should warn about non-required field', () => {
      const result = validator.validateField('unknown', 'value', config);
      expect(result.warnings).toContain("Field 'unknown' is not required by configuration");
    });

    it('should handle null values for required fields', () => {
      const result1 = validator.validateField('age', null, config);
      expect(result1.valid).toBe(false);
      
      const result2 = validator.validateField('age', undefined, config);
      expect(result2.valid).toBe(false);
    });
  });

  describe('validateFields', () => {
    const config: RuleFlowConfig = {
      formulas: [{
        id: 'test',
        inputs: ['field1', 'field2']
      }]
    };

    it('should validate multiple fields', () => {
      const userInputs = { field1: 'value1', field2: '42' };
      const results = validator.validateFields(userInputs, config);
      
      expect(results.field1.valid).toBe(true);
      expect(results.field1.converted_value).toBe('value1');
      expect(results.field2.valid).toBe(true);
      expect(results.field2.converted_value).toBe(42);
    });

    it('should detect invalid fields', () => {
      const userInputs = { field1: '', field2: 'valid' };
      const results = validator.validateFields(userInputs, config);
      
      expect(results.field1.valid).toBe(false);
      expect(results.field2.valid).toBe(true);
    });
  });

  describe('getValidationStatus', () => {
    const config: RuleFlowConfig = {
      formulas: [{
        id: 'test',
        inputs: ['field1', 'field2', 'field3']
      }]
    };

    it('should provide complete validation status', () => {
      const userInputs = { field1: 'value1', field2: 'value2' };
      const status = validator.getValidationStatus(userInputs, config);
      
      expect(status.ready_to_submit).toBe(false);
      expect(status.validation_score).toBe(67); // 2/3 fields
      expect(status.field_validation.overall_progress).toBe(67);
      expect(status.summary.total_fields).toBe(3);
      expect(status.summary.provided_fields).toBe(2);
      expect(status.summary.missing_fields).toBe(1);
      expect(status.summary.invalid_fields).toBe(0);
    });

    it('should mark as ready when complete and valid', () => {
      const userInputs = { field1: 'value1', field2: 'value2', field3: 'value3' };
      const status = validator.getValidationStatus(userInputs, config);
      
      expect(status.ready_to_submit).toBe(true);
      expect(status.validation_score).toBe(100);
    });

    it('should handle invalid fields in score calculation', () => {
      const userInputs = { field1: '', field2: 'value2', field3: 'value3' };
      const status = validator.getValidationStatus(userInputs, config);
      
      expect(status.ready_to_submit).toBe(false);
      expect(status.summary.invalid_fields).toBe(1);
      expect(status.validation_score).toBe(67); // (3-1)/3 = 67%
    });
  });

  describe('sanitizeInputs', () => {
    it('should trim string values', () => {
      const inputs = {
        name: '  John Doe  ',
        age: 25,
        active: true
      };
      
      const sanitized = validator.sanitizeInputs(inputs);
      expect(sanitized.name).toBe('John Doe');
      expect(sanitized.age).toBe(25);
      expect(sanitized.active).toBe(true);
    });
  });

  describe('isComplete', () => {
    const config: RuleFlowConfig = {
      formulas: [{
        id: 'test',
        inputs: ['x', 'y']
      }]
    };

    it('should return true when complete', () => {
      const result = validator.isComplete({ x: 1, y: 2 }, config);
      expect(result).toBe(true);
    });

    it('should return false when incomplete', () => {
      const result = validator.isComplete({ x: 1 }, config);
      expect(result).toBe(false);
    });
  });

  describe('getCompletionPercentage', () => {
    const config: RuleFlowConfig = {
      formulas: [{
        id: 'test',
        inputs: ['a', 'b', 'c', 'd']
      }]
    };

    it('should calculate percentage correctly', () => {
      const result1 = validator.getCompletionPercentage({ a: 1, b: 2 }, config);
      expect(result1).toBe(50); // 2 out of 4

      const result2 = validator.getCompletionPercentage({ a: 1, b: 2, c: 3 }, config);
      expect(result2).toBe(75); // 3 out of 4

      const result3 = validator.getCompletionPercentage({ a: 1, b: 2, c: 3, d: 4 }, config);
      expect(result3).toBe(100); // 4 out of 4
    });

    it('should return 100 for empty config', () => {
      const emptyConfig: RuleFlowConfig = { formulas: [] };
      const result = validator.getCompletionPercentage({ any: 'value' }, emptyConfig);
      expect(result).toBe(100);
    });
  });

  describe('validatePartial', () => {
    const config: RuleFlowConfig = {
      formulas: [{
        id: 'test',
        inputs: ['field1', 'field2']
      }]
    };

    it('should validate complete inputs', () => {
      const result = validator.validatePartial({ field1: 'a', field2: 'b' }, config);
      expect(result.valid).toBe(true);
      expect(result.missing_required).toEqual([]);
      expect(result.overall_progress).toBe(100);
      expect(result.ready_to_submit).toBe(true);
    });

    it('should validate incomplete inputs', () => {
      const result = validator.validatePartial({ field1: 'a' }, config);
      expect(result.valid).toBe(false);
      expect(result.missing_required).toEqual(['field2']);
      expect(result.overall_progress).toBe(50);
      expect(result.ready_to_submit).toBe(false);
    });
  });

  describe('validateBeforeEvaluate', () => {
    const config: RuleFlowConfig = {
      formulas: [{
        id: 'test',
        inputs: ['required_field']
      }]
    };

    it('should pass with complete inputs', () => {
      expect(() => {
        validator.validateBeforeEvaluate({ required_field: 'value' }, config);
      }).not.toThrow();
    });

    it('should throw exception for missing inputs', () => {
      expect(() => {
        validator.validateBeforeEvaluate({}, config);
      }).toThrow(RuleFlowException);

      expect(() => {
        validator.validateBeforeEvaluate({}, config);
      }).toThrow('Missing input: required_field');
    });
  });

  describe('real-world examples', () => {
    it('should work with BMI calculator config', () => {
      const bmiConfig: RuleFlowConfig = {
        formulas: [
          {
            id: 'bmi',
            formula: 'weight / ((height / 100) ** 2)',
            inputs: ['weight', 'height']
          },
          {
            id: 'category',
            switch: 'bmi',
            when: [
              { if: { op: '<', value: 18.5 }, result: 'Underweight' },
              { if: { op: '<', value: 25 }, result: 'Normal' }
            ],
            default: 'Overweight'
          }
        ]
      };

      const requiredInputs = validator.extractRequiredInputs(bmiConfig);
      expect(requiredInputs).toContain('weight');
      expect(requiredInputs).toContain('height');
      expect(requiredInputs).toContain('bmi');

      const partialInputs = { weight: 70 };
      const status = validator.validatePartial(partialInputs, bmiConfig);
      expect(status.valid).toBe(false);
      expect(status.missing_required).toContain('height');
      expect(status.missing_required).toContain('bmi');
    });

    it('should work with loan application config', () => {
      const loanConfig: RuleFlowConfig = {
        formulas: [
          {
            id: 'monthly_payment',
            formula: 'loan_amount * rate / (1 - (1 + rate) ** (-term))',
            inputs: ['loan_amount', 'rate', 'term']
          },
          {
            id: 'approval',
            switch: 'credit_score',
            when: [
              { if: { op: '>', value: 700 }, result: 'approved' }
            ],
            default: 'rejected'
          }
        ]
      };

      const requiredInputs = validator.extractRequiredInputs(loanConfig);
      expect(requiredInputs).toEqual(['credit_score', 'loan_amount', 'rate', 'term']);

      const applicationData = {
        loan_amount: 300000,
        rate: 0.04,
        credit_score: 750
      };

      const status = validator.validatePartial(applicationData, loanConfig);
      expect(status.overall_progress).toBe(75); // 3 out of 4 fields
      expect(status.missing_required).toEqual(['term']);
    });
  });
});

describe('ConfigValidator', () => {
  const validator = new ConfigValidator();

  describe('validateConfig', () => {
    it('should validate correct configuration', () => {
      const config: RuleFlowConfig = {
        formulas: [
          {
            id: 'calculation',
            formula: 'a + b',
            inputs: ['a', 'b']
          }
        ]
      };

      const result = validator.validateConfig(config);
      expect(result.valid).toBe(true);
      expect(result.errors).toHaveLength(0);
      expect(result.warnings).toHaveLength(0);
    });

    it('should detect missing formulas', () => {
      const config = {} as RuleFlowConfig;
      
      const result = validator.validateConfig(config);
      expect(result.valid).toBe(false);
      expect(result.errors).toContain('Configuration must have formulas array');
    });

    it('should detect missing formula id', () => {
      const config: RuleFlowConfig = {
        formulas: [
          {
            formula: 'a + b'
          } as any
        ]
      };

      const result = validator.validateConfig(config);
      expect(result.valid).toBe(false);
      expect(result.errors.some(e => e.includes("Missing required 'id' field"))).toBe(true);
    });

    it('should detect formula without logic', () => {
      const config: RuleFlowConfig = {
        formulas: [
          {
            id: 'empty_formula'
          }
        ]
      };

      const result = validator.validateConfig(config);
      expect(result.valid).toBe(false);
      expect(result.errors.some(e => e.includes("Must have 'formula', 'switch', 'when', or 'set_vars'"))).toBe(true);
    });

    it('should detect unmatched parentheses', () => {
      const config: RuleFlowConfig = {
        formulas: [
          {
            id: 'bad_formula',
            formula: 'a + (b * c'
          }
        ]
      };

      const result = validator.validateConfig(config);
      expect(result.valid).toBe(false);
      expect(result.errors.some(e => e.includes('Unmatched parentheses'))).toBe(true);
    });

    it('should detect invalid operator sequences', () => {
      const config: RuleFlowConfig = {
        formulas: [
          {
            id: 'bad_operators',
            formula: 'a ++ b'
          }
        ]
      };

      const result = validator.validateConfig(config);
      expect(result.valid).toBe(false);
      expect(result.errors.some(e => e.includes('Invalid operator sequence'))).toBe(true);
    });

    it('should detect duplicate formula IDs', () => {
      const config: RuleFlowConfig = {
        formulas: [
          {
            id: 'duplicate',
            formula: 'a + b'
          },
          {
            id: 'duplicate',
            formula: 'c + d'
          }
        ]
      };

      const result = validator.validateConfig(config);
      expect(result.valid).toBe(false);
      expect(result.errors.some(e => e.includes('Duplicate formula IDs'))).toBe(true);
    });

    it('should validate switch formulas', () => {
      const validSwitch: RuleFlowConfig = {
        formulas: [
          {
            id: 'test_switch',
            switch: 'category',
            when: [
              { if: { op: '==', value: 'A' }, result: 100 }
            ]
          }
        ]
      };

      const result1 = validator.validateConfig(validSwitch);
      expect(result1.valid).toBe(true);

      const invalidSwitch: RuleFlowConfig = {
        formulas: [
          {
            id: 'bad_switch',
            switch: 'category',
            when: []
          }
        ]
      };

      const result2 = validator.validateConfig(invalidSwitch);
      expect(result2.valid).toBe(false);
      expect(result2.errors.some(e => e.includes("'when' array cannot be empty"))).toBe(true);
    });

    it('should detect circular dependencies', () => {
      const config: RuleFlowConfig = {
        formulas: [
          {
            id: 'a',
            formula: '$b + 1'
          },
          {
            id: 'b',
            formula: '$a + 1'
          }
        ]
      };

      const result = validator.validateConfig(config);
      expect(result.valid).toBe(false);
      expect(result.errors.some(e => e.includes('Circular dependency'))).toBe(true);
    });

    it('should warn about empty formulas array', () => {
      const config: RuleFlowConfig = {
        formulas: []
      };

      const result = validator.validateConfig(config);
      expect(result.warnings).toContain('No formulas defined in configuration');
    });
  });
});import { describe, it, expect } from 'vitest';
import { InputValidator } from '../src/validators/InputValidator';
import { RuleFlowException } from '../src/exceptions/RuleFlowException';
import { RuleFlowConfig } from '../src/types';

describe('InputValidator', () => {
  const validator = new InputValidator();

  describe('extractRequiredInputs', () => {
    it('should extract inputs from formula string', () => {
      const config: RuleFlowConfig = {
        formulas: [{
          id: 'test',
          formula: 'a + b * c'
        }]
      };

      const inputs = validator.extractRequiredInputs(config);
      expect(inputs).toEqual(['a', 'b', 'c']);
    });

    it('should extract inputs from inputs array', () => {
      const config: RuleFlowConfig = {
        formulas: [{
          id: 'test',
          inputs: ['x', 'y', 'z']
        }]
      };

      const inputs = validator.extractRequiredInputs(config);
      expect(inputs).toEqual(['x', 'y', 'z']);
    });

    it('should extract switch variable', () => {
      const config: RuleFlowConfig = {
        formulas: [{
          id: 'test',
          switch: 'category',
          when: []
        }]
      };

      const inputs = validator.extractRequiredInputs(config);
      expect(inputs).toEqual(['category']);
    });

    it('should filter out reserved words', () => {
      const config: RuleFlowConfig = {
        formulas: [{
          id: 'test',
          formula: 'max(price, min(cost, 100))'
        }]
      };

      const inputs = validator.extractRequiredInputs(config);
      expect(inputs).toEqual(['cost', 'price']);
      expect(inputs).not.toContain('max');
      expect(inputs).not.toContain('min');
    });

    it('should handle complex config', () => {
      const config: RuleFlowConfig = {
        formulas: [
          {
            id: 'calc1',
            formula: 'price * quantity',
            inputs: ['tax_rate']
          },
          {
            id: 'calc2',
            switch: 'category'
          }
        ]
      };

      const inputs = validator.extractRequiredInputs(config);
      expect(inputs).toEqual(['category', 'price', 'quantity', 'tax_rate']);
    });

    it('should remove duplicates', () => {
      const config: RuleFlowConfig = {
        formulas: [
          {
            id: 'calc1',
            formula: 'price + tax',
            inputs: ['price']
          },
          {
            id: 'calc2',
            switch: 'price'
          }
        ]
      };

      const inputs = validator.extractRequiredInputs(config);
      const priceCount = inputs.filter(input => input === 'price').length;
      expect(priceCount).toBe(1);
    });
  });

  describe('getMissingInputs', () => {
    const config: RuleFlowConfig = {
      formulas: [{
        id: 'test',
        inputs: ['a', 'b', 'c']
      }]
    };

    it('should find missing inputs', () => {
      const userInputs = { a: 1, c: 3 };
      const missing = validator.getMissingInputs(userInputs, config);
      expect(missing).toEqual(['b']);
    });

    it('should return empty array when all inputs provided', () => {
      const userInputs = { a: 1, b: 2, c: 3 };
      const missing = validator.getMissingInputs(userInputs, config);
      expect(missing).toEqual([]);
    });

    it('should return all required when no inputs provided', () => {
      const missing = validator.getMissingInputs({}, config);
      expect(missing).toEqual(['a', 'b', 'c']);
    });
  });

  describe('isComplete', () => {
    const config: RuleFlowConfig = {
      formulas: [{
        id: 'test',
        inputs: ['x', 'y']
      }]
    };

    it('should return true when complete', () => {
      const result = validator.isComplete({ x: 1, y: 2 }, config);
      expect(result).toBe(true);
    });

    it('should return false when incomplete', () => {
      const result = validator.isComplete({ x: 1 }, config);
      expect(result).toBe(false);
    });
  });

  describe('getCompletionPercentage', () => {
    const config: RuleFlowConfig = {
      formulas: [{
        id: 'test',
        inputs: ['a', 'b', 'c', 'd']
      }]
    };

    it('should calculate percentage correctly', () => {
      const result1 = validator.getCompletionPercentage({ a: 1, b: 2 }, config);
      expect(result1).toBe(50); // 2 out of 4

      const result2 = validator.getCompletionPercentage({ a: 1, b: 2, c: 3 }, config);
      expect(result2).toBe(75); // 3 out of 4

      const result3 = validator.getCompletionPercentage({ a: 1, b: 2, c: 3, d: 4 }, config);
      expect(result3).toBe(100); // 4 out of 4
    });

    it('should return 100 for empty config', () => {
      const emptyConfig: RuleFlowConfig = { formulas: [] };
      const result = validator.getCompletionPercentage({ any: 'value' }, emptyConfig);
      expect(result).toBe(100);
    });
  });

  describe('validatePartial', () => {
    const config: RuleFlowConfig = {
      formulas: [{
        id: 'test',
        inputs: ['field1', 'field2']
      }]
    };

    it('should validate complete inputs', () => {
      const result = validator.validatePartial({ field1: 'a', field2: 'b' }, config);
      expect(result.valid).toBe(true);
      expect(result.missing_required).toEqual([]);
      expect(result.overall_progress).toBe(100);
      expect(result.ready_to_submit).toBe(true);
    });

    it('should validate incomplete inputs', () => {
      const result = validator.validatePartial({ field1: 'a' }, config);
      expect(result.valid).toBe(false);
      expect(result.missing_required).toEqual(['field2']);
      expect(result.overall_progress).toBe(50);
      expect(result.ready_to_submit).toBe(false);
    });
  });

  describe('validateBeforeEvaluate', () => {
    const config: RuleFlowConfig = {
      formulas: [{
        id: 'test',
        inputs: ['required_field']
      }]
    };

    it('should pass with complete inputs', () => {
      expect(() => {
        validator.validateBeforeEvaluate({ required_field: 'value' }, config);
      }).not.toThrow();
    });

    it('should throw exception for missing inputs', () => {
      expect(() => {
        validator.validateBeforeEvaluate({}, config);
      }).toThrow(RuleFlowException);

      expect(() => {
        validator.validateBeforeEvaluate({}, config);
      }).toThrow('Missing input: required_field');
    });
  });

  describe('real-world examples', () => {
    it('should work with BMI calculator config', () => {
      const bmiConfig: RuleFlowConfig = {
        formulas: [
          {
            id: 'bmi',
            formula: 'weight / ((height / 100) ** 2)',
            inputs: ['weight', 'height']
          },
          {
            id: 'category',
            switch: 'bmi',
            when: [
              { if: { op: '<', value: 18.5 }, result: 'Underweight' },
              { if: { op: '<', value: 25 }, result: 'Normal' }
            ],
            default: 'Overweight'
          }
        ]
      };

      const requiredInputs = validator.extractRequiredInputs(bmiConfig);
      expect(requiredInputs).toContain('weight');
      expect(requiredInputs).toContain('height');
      expect(requiredInputs).toContain('bmi');

      const partialInputs = { weight: 70 };
      const status = validator.validatePartial(partialInputs, bmiConfig);
      expect(status.valid).toBe(false);
      expect(status.missing_required).toContain('height');
      expect(status.missing_required).toContain('bmi');
    });

    it('should work with loan application config', () => {
      const loanConfig: RuleFlowConfig = {
        formulas: [
          {
            id: 'monthly_payment',
            formula: 'loan_amount * rate / (1 - (1 + rate) ** (-term))',
            inputs: ['loan_amount', 'rate', 'term']
          },
          {
            id: 'approval',
            switch: 'credit_score',
            when: [
              { if: { op: '>', value: 700 }, result: 'approved' }
            ],
            default: 'rejected'
          }
        ]
      };

      const requiredInputs = validator.extractRequiredInputs(loanConfig);
      expect(requiredInputs).toEqual(['credit_score', 'loan_amount', 'rate', 'term']);

      const applicationData = {
        loan_amount: 300000,
        rate: 0.04,
        credit_score: 750
      };

      const status = validator.validatePartial(applicationData, loanConfig);
      expect(status.overall_progress).toBe(75); // 3 out of 4 fields
      expect(status.missing_required).toEqual(['term']);
    });
  });
});