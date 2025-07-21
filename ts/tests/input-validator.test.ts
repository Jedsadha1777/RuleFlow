// ไฟล์: ts/tests/input-validator.test.ts

import { describe, it, expect } from 'vitest';
import { InputValidator } from '../src/validators/InputValidator.js';
import type { RuleFlowConfig } from '../src/types.js';

describe('InputValidator', () => {
  const validator = new InputValidator();

  const sampleConfig: RuleFlowConfig = {
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
      },
     
    ]
  };

  const validInputs = { weight: 70, height: 1.75 };
  const partialInputs = { weight: 70 }; // missing height

  // ========================================
  // Test Required Inputs Extraction
  // ========================================

  describe('extractRequiredInputs()', () => {
    it('should extract inputs from formula inputs array', () => {
      const inputs = validator.extractRequiredInputs(sampleConfig);
      
      expect(inputs).toContain('weight');
      expect(inputs).toContain('height');
    });

    it('should extract variables from formula expressions', () => {
      const config: RuleFlowConfig = {
        formulas: [
          {
            id: 'test',
            formula: 'x + y * z',
            inputs: []
          }
        ]
      };
      
      const inputs = validator.extractRequiredInputs(config);
      expect(inputs).toContain('x');
      expect(inputs).toContain('y');
      expect(inputs).toContain('z');
    });

    it('should extract switch variables', () => {
      const inputs = validator.extractRequiredInputs(sampleConfig);
      expect(inputs).toContain('bmi');
    });

    it('should extract from scoring rules', () => {
        const configWithRules: RuleFlowConfig = {
        formulas: [
          {
            id: 'score',
            rules: [
              { var: 'experience', score: 10 }
            ]
          }
        ]
      };
      
      const inputs = validator.extractRequiredInputs(configWithRules);
      expect(inputs).toContain('experience');
    });

    it('should remove duplicates', () => {
      const inputs = validator.extractRequiredInputs(sampleConfig);
      const uniqueInputs = [...new Set(inputs)];
      expect(inputs.length).toBe(uniqueInputs.length);
    });
  });

  // ========================================
  // Test Field Validation
  // ========================================

  describe('validateField()', () => {
    it('should validate valid field', () => {
      const result = validator.validateField('weight', '75.5', sampleConfig);
      
      expect(result.valid).toBe(true);
      expect(result.converted_value).toBe(75.5);
      expect(result.type).toBe('number');
      expect(result.errors).toHaveLength(0);
    });

    it('should detect empty required field', () => {
      const result = validator.validateField('height', '', sampleConfig);
      
      expect(result.valid).toBe(false);
      expect(result.errors).toContain("Field 'height' is required and cannot be empty");
    });

    it('should warn about non-required fields', () => {
      const result = validator.validateField('unknown', 'value', sampleConfig);
      
      expect(result.warnings).toContain("Field 'unknown' is not required by configuration");
    });

    it('should convert values correctly', () => {
      const numberResult = validator.validateField('weight', '75', sampleConfig);
      const boolResult = validator.validateField('active', 'true', sampleConfig);
      
      expect(numberResult.converted_value).toBe(75);
      expect(boolResult.converted_value).toBe(true);
    });
  });

  // ========================================
  // Test Multiple Fields Validation
  // ========================================

  describe('validateFields()', () => {
    it('should validate multiple fields', () => {
      const inputs = { weight: '70', height: '1.75', extra: 'test' };
      const results = validator.validateFields(inputs, sampleConfig);
      
      expect(results.weight.valid).toBe(true);
      expect(results.height.valid).toBe(true);
      expect(results.extra.warnings.length).toBeGreaterThan(0);
    });

    it('should handle empty inputs', () => {
      const results = validator.validateFields({}, sampleConfig);
      expect(Object.keys(results)).toHaveLength(0);
    });
  });

  // ========================================
  // Test Partial Validation
  // ========================================

  describe('validatePartial()', () => {
    it('should validate complete inputs', () => {
      const result = validator.validatePartial(validInputs, sampleConfig);
      
      expect(result.valid).toBe(true);
      expect(result.missing_required).toHaveLength(0);
      expect(result.overall_progress).toBe(100);
    });

    it('should validate incomplete inputs', () => {
      const result = validator.validatePartial(partialInputs, sampleConfig);
      
      expect(result.valid).toBe(false);
      expect(result.missing_required).toContain('height');
      expect(result.overall_progress).toBe(50);
    });
  });

  // ========================================
  // Test Completion Status
  // ========================================

  describe('isComplete() and getCompletionPercentage()', () => {
    it('should check completion status', () => {
      expect(validator.isComplete(validInputs, sampleConfig)).toBe(true);
      expect(validator.isComplete(partialInputs, sampleConfig)).toBe(false);
    });

    it('should calculate completion percentage', () => {
      expect(validator.getCompletionPercentage(validInputs, sampleConfig)).toBe(100);
      expect(validator.getCompletionPercentage(partialInputs, sampleConfig)).toBe(50);
      expect(validator.getCompletionPercentage({}, sampleConfig)).toBe(0);
    });
  });

  // ========================================
  // Test Validation Status
  // ========================================

  describe('getValidationStatus()', () => {
    it('should calculate validation status correctly', () => {
      const status = validator.getValidationStatus(validInputs, sampleConfig);
      
      expect(status.ready_to_submit).toBe(true);
      expect(status.validation_score).toBe(100);
      expect(status.field_validation.valid).toBe(true);
      expect(status.field_validation.overall_progress).toBe(100);
      expect(status.summary.total_fields).toBeGreaterThan(0);
      expect(status.summary.provided_fields).toBe(2);
    });

    it('should handle partial inputs', () => {
      const status = validator.getValidationStatus(partialInputs, sampleConfig);
      
      expect(status.ready_to_submit).toBe(false);
      expect(status.validation_score).toBe(50);
      expect(status.field_validation.valid).toBe(false);
      expect(status.field_validation.missing_required).toContain('height');
    });
  });

  // ========================================
  // Test Security Validation
  // ========================================

  describe('validateInputSecurity()', () => {
    it('should detect SQL injection attempts', () => {
      const sqlInputs = {
        username: "admin'; DROP TABLE users; --"
      };

      const result = validator.validateInputSecurity(sqlInputs);
      
      expect(result.safe).toBe(false);
      expect(result.threats).toHaveLength(1);
      expect(result.threats[0].threat_type).toBe('SQL_INJECTION');
    });

    it('should detect XSS attempts', () => {
      const xssInputs = {
        comment: '<script>alert("xss")</script>'
      };

      const result = validator.validateInputSecurity(xssInputs);
      
      expect(result.safe).toBe(false);
      expect(result.threats).toHaveLength(1);
      expect(result.threats[0].threat_type).toBe('XSS');
    });

    it('should detect DoS attempts (long input)', () => {
      const dosInputs = {
        field: 'a'.repeat(15000)
      };

      const result = validator.validateInputSecurity(dosInputs);
      
      expect(result.safe).toBe(false);
      expect(result.threats).toHaveLength(1);
      expect(result.threats[0].threat_type).toBe('DOS');
    });

    it('should pass clean inputs', () => {
      const cleanInputs = {
        name: 'John Doe',
        age: '30',
        email: 'john@example.com'
      };

      const result = validator.validateInputSecurity(cleanInputs);
      
      expect(result.safe).toBe(true);
      expect(result.threats).toHaveLength(0);
    });
  });

  // ========================================
  // Test Sanitization
  // ========================================

  describe('sanitizeInputs()', () => {
    it('should sanitize basic inputs', () => {
      const inputs = {
        name: '  John Doe  ',
        age: 30
      };

      const result = validator.sanitizeInputs(inputs);
      
      expect(result.name).toBe('John Doe');
      expect(result.age).toBe(30);
    });
  });

  describe('sanitizeInputsAdvanced()', () => {
    it('should remove HTML tags', () => {
      const inputs = {
        comment: '<script>alert("test")</script>Hello <b>World</b>'
      };

      const result = validator.sanitizeInputsAdvanced(inputs, {
        removeHtml: true
      });
      
      expect(result.comment).not.toContain('<script>');
      expect(result.comment).not.toContain('<b>');
      expect(result.comment).toContain('Hello');
      expect(result.comment).toContain('World');
    });

    it('should limit string length', () => {
      const inputs = {
        text: 'a'.repeat(2000)
      };

      const result = validator.sanitizeInputsAdvanced(inputs, {
        maxStringLength: 100
      });
      
      expect(result.text.length).toBe(100);
    });

    it('should filter allowed keys', () => {
      const inputs = {
        allowed: 'value1',
        blocked: 'value2'
      };

      const result = validator.sanitizeInputsAdvanced(inputs, {
        allowedKeys: ['allowed']
      });
      
      expect(result.allowed).toBe('value1');
      expect(result.blocked).toBeUndefined();
    });
  });

  // ========================================
  // Test Backward Compatibility
  // ========================================

  describe('Backward Compatibility', () => {
    it('should maintain original validate method', () => {
      expect(() => {
        validator.validate(validInputs, ['weight', 'height']);
      }).not.toThrow();

      expect(() => {
        validator.validate({}, ['weight']);
      }).toThrow('Required input \'weight\' is missing');
    });

    it('should maintain validateBeforeEvaluate method', () => {
      expect(() => {
        validator.validateBeforeEvaluate(validInputs, sampleConfig);
      }).not.toThrow();

      expect(() => {
        validator.validateBeforeEvaluate(partialInputs, sampleConfig);
      }).toThrow();
    });
  });

  // ========================================
  // Test Missing Inputs Detection
  // ========================================

  describe('getMissingInputs()', () => {
    it('should detect missing inputs', () => {
      const missing = validator.getMissingInputs(partialInputs, sampleConfig);
      expect(missing).toContain('height');
    });

    it('should return empty array for complete inputs', () => {
       const completeInputs = { weight: 70, height: 1.75, bmi: 22.86 };
      const missing = validator.getMissingInputs(completeInputs, sampleConfig);
      expect(missing).toHaveLength(0);;
    });
  });

  // ========================================
  // Test Value Conversion
  // ========================================

  describe('convertValue()', () => {
    it('should convert string numbers', () => {
      expect(validator.convertValue('42')).toBe(42);
      expect(validator.convertValue('3.14')).toBe(3.14);
      expect(validator.convertValue('-10')).toBe(-10);
    });

    it('should convert boolean strings', () => {
      expect(validator.convertValue('true')).toBe(true);
      expect(validator.convertValue('false')).toBe(false);
      expect(validator.convertValue('TRUE')).toBe(true);
      expect(validator.convertValue('FALSE')).toBe(false);
    });

    it('should preserve non-convertible values', () => {
      expect(validator.convertValue('hello')).toBe('hello');
      expect(validator.convertValue(null)).toBe(null);
      expect(validator.convertValue(undefined)).toBe(undefined);
    });

    it('should trim string values', () => {
      expect(validator.convertValue('  test  ')).toBe('test');
    });
  });
});