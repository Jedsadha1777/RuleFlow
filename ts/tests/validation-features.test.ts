// ไฟล์: ts/tests/validation-features.test.ts

import { describe, it, expect } from 'vitest';
import { RuleFlow } from '../src/RuleFlow.js';
import { InputValidator } from '../src/validators/InputValidator.js';
import { SchemaGenerator } from '../src/generators/SchemaGenerator.js';
import type { RuleFlowConfig } from '../src/types.js';

describe('Advanced Validation Features', () => {
  const ruleFlow = new RuleFlow();
  const validator = new InputValidator();
  const generator = new SchemaGenerator();

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
      }
    ]
  };

  const validInputs = { weight: 70, height: 1.75 };
  const partialInputs = { weight: 70 }; // missing height

  // ========================================
  // Test testConfig() method
  // ========================================

  describe('testConfig()', () => {
    it('should test valid configuration successfully', async () => {
      const result = await ruleFlow.testConfig(sampleConfig, validInputs);
      
      expect(result.valid).toBe(true);
      expect(result.errors).toHaveLength(0);
      expect(result.test_result).toBeDefined();
      expect(result.execution_time).toBeGreaterThanOrEqual(0);
      expect(result.test_result?.bmi).toBeCloseTo(22.86, 2);
      expect(result.test_result?.category).toBe('Normal');
    });

    it('should detect invalid configuration', async () => {
      const invalidConfig: RuleFlowConfig = {
        formulas: [
          {
            id: 'broken',
            formula: 'a +', // Invalid syntax
            inputs: ['a']
          }
        ]
      };

      const result = await ruleFlow.testConfig(invalidConfig, { a: 1 });
      expect(result.valid).toBe(false);
      expect(result.errors.length).toBeGreaterThan(0);
      expect(result.test_result).toBeUndefined();
    });

    it('should handle execution errors gracefully', async () => {
      const result = await ruleFlow.testConfig(sampleConfig, {}); // Missing inputs
      expect(result.valid).toBe(false);
      
      const hasExecutionError = result.errors.some(error => 
        error.includes('Test execution failed')
      );
      expect(hasExecutionError).toBe(true);
    });

    it('should include warnings in test result', async () => {
      const configWithWarnings: RuleFlowConfig = {
        formulas: [] // Empty formulas should generate warning
      };

      const result = await ruleFlow.testConfig(configWithWarnings, {});
      expect(result.warnings).toContain('No formulas defined in configuration');
    });
  });

  // ========================================
  // Test validateBatch() method
  // ========================================

  describe('validateBatch()', () => {
    it('should validate multiple configurations', () => {
      const configs = [
        sampleConfig, // Valid
        { formulas: [] }, // Empty (warning)
        { formulas: [{ id: 'invalid' }] } // Invalid (missing execution logic)
      ];

      const results = ruleFlow.validateBatch(configs);
      
      expect(results).toHaveLength(3);
      expect(results[0].valid).toBe(true);
      expect(results[1].valid).toBe(true); // Empty is valid, just has warnings
      expect(results[2].valid).toBe(false);
      
      expect(results[0].errors).toHaveLength(0);
      expect(results[1].warnings.length).toBeGreaterThan(0);
      expect(results[2].errors.length).toBeGreaterThan(0);
    });

    it('should include index in batch results', () => {
      const configs = [sampleConfig, sampleConfig];
      const results = ruleFlow.validateBatch(configs);
      
      expect(results[0].index).toBe(0);
      expect(results[1].index).toBe(1);
    });
  });

  // ========================================
  // Test Advanced Sanitization
  // ========================================

  describe('Advanced Sanitization', () => {
    const dirtyInputs = {
      weight: '  75.5  ',
      height: '<script>alert("hack")</script>1.80',
      extra: 'a'.repeat(100),
      malicious: 'SELECT * FROM users WHERE id = 1'
    };

    it('should perform basic sanitization', () => {
      const clean = validator.sanitizeInputs(dirtyInputs);
      
      expect(clean.weight).toBe('75.5');
      expect(clean.height).toContain('1.80');
    });

    it('should perform advanced sanitization', () => {
      const clean = validator.sanitizeInputsAdvanced(dirtyInputs, {
        removeHtml: true,
        maxStringLength: 50
      });
      
      expect(clean.weight).toBe('75.5');
      expect(clean.height).toBe('1.80'); // HTML removed
      expect(clean.extra.length).toBeLessThanOrEqual(50);
      expect(clean.malicious).not.toContain('SELECT');
    });

    it('should filter allowed keys', () => {
      const clean = validator.sanitizeInputsAdvanced(dirtyInputs, {
        allowedKeys: ['weight', 'height']
      });
      
      expect(clean.weight).toBeDefined();
      expect(clean.height).toBeDefined();
      expect(clean.extra).toBeUndefined();
      expect(clean.malicious).toBeUndefined();
    });

    it('should remove dangerous characters', () => {
      const dangerousInputs = {
        test: `'"; DROP TABLE users; --`
      };
      
      const clean = validator.sanitizeInputsAdvanced(dangerousInputs);
      expect(clean.test).not.toContain("'");
      expect(clean.test).not.toContain('"');
      expect(clean.test).not.toContain(';');
      expect(clean.test).not.toContain('DROP');
    });
  });

  // ========================================
  // Test Security Validation
  // ========================================

  describe('Security Validation', () => {
    it('should detect SQL injection attempts', () => {
      const sqlInjection = {
        query: 'SELECT * FROM users; DROP TABLE users;'
      };

      const result = validator.validateInputSecurity(sqlInjection);
      
      expect(result.safe).toBe(false);
      expect(result.threats).toHaveLength(1);
      expect(result.threats[0].threat_type).toBe('SQL_INJECTION');
      expect(result.threats[0].field).toBe('query');
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
        field: 'a'.repeat(15000) // Very long string
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
  // Test Field Validation
  // ========================================

  describe('Field Validation', () => {
    it('should validate individual fields', () => {
      const result = validator.validateField('weight', '75.5', sampleConfig);
      
      expect(result.valid).toBe(true);
      expect(result.converted_value).toBe(75.5);
      expect(result.type).toBe('number');
      expect(result.errors).toHaveLength(0);
    });

    it('should detect missing required fields', () => {
      const result = validator.validateField('height', '', sampleConfig);
      
      expect(result.valid).toBe(false);
      expect(result.errors).toContain("Field 'height' is required and cannot be empty");
    });

    it('should warn about non-required fields', () => {
      const result = validator.validateField('unknown', 'value', sampleConfig);
      
      expect(result.warnings).toContain("Field 'unknown' is not required by configuration");
    });

    it('should convert string values', () => {
      const numberResult = validator.validateField('weight', '75', sampleConfig);
      const boolResult = validator.validateField('active', 'true', sampleConfig);
      
      expect(numberResult.converted_value).toBe(75);
      expect(boolResult.converted_value).toBe(true);
    });

    it('should validate multiple fields', () => {
      const inputs = { weight: '70', height: '1.75', extra: 'test' };
      const results = validator.validateFields(inputs, sampleConfig);
      
      expect(results.weight.valid).toBe(true);
      expect(results.height.valid).toBe(true);
      expect(results.extra.warnings.length).toBeGreaterThan(0);
    });
  });

  // ========================================
  // Test Validation Status
  // ========================================

  describe('Validation Status', () => {
    it('should calculate validation status correctly', () => {
      const status = validator.getValidationStatus(validInputs, sampleConfig);
      
      expect(status.ready_to_submit).toBe(true);
      expect(status.validation_score).toBe(100); // 2 out of 2 base inputs
      expect(status.field_validation.valid).toBe(true);
      expect(status.field_validation.overall_progress).toBe(100);
      expect(status.summary.total_fields).toBe(3); // weight, height, bmi
      expect(status.summary.provided_fields).toBe(2); // weight, height
      expect(status.summary.missing_fields).toBe(0); // no missing base inputs
    });

    it('should handle partial inputs', () => {
      const status = validator.getValidationStatus(partialInputs, sampleConfig);
      
      expect(status.ready_to_submit).toBe(false);
      expect(status.validation_score).toBe(50); // 1 out of 2 base inputs
      expect(status.field_validation.valid).toBe(false);
      expect(status.field_validation.missing_required).toContain('height');
      expect(status.summary.missing_fields).toBe(1); // height missing
    });

    it('should handle invalid fields', () => {
      const invalidInputs = { weight: '', height: '1.75' };
      const status = validator.getValidationStatus(invalidInputs, sampleConfig);
      
      expect(status.ready_to_submit).toBe(false);
      expect(status.summary.invalid_fields).toBe(1);
    });
  });

  // ========================================
  // Test Partial Validation
  // ========================================

  describe('Partial Validation', () => {
    it('should validate complete inputs', () => {
      const result = validator.validatePartial(validInputs, sampleConfig);
      
      expect(result.valid).toBe(true);
      expect(result.missing_required).toHaveLength(0);
      expect(result.overall_progress).toBe(100); // 2 out of 3 total fields
    });

    it('should validate incomplete inputs', () => {
      const result = validator.validatePartial(partialInputs, sampleConfig);
      
      expect(result.valid).toBe(false);
      expect(result.missing_required).toContain('height');
      expect(result.overall_progress).toBe(50); // 1 out of 3 total fields
    });

    it('should check completion status', () => {
      expect(validator.isComplete(validInputs, sampleConfig)).toBe(true);
      expect(validator.isComplete(partialInputs, sampleConfig)).toBe(false);
    });

    it('should calculate completion percentage', () => {
      expect(validator.getCompletionPercentage(validInputs, sampleConfig)).toBe(100); // 2 out of 3 total fields
      expect(validator.getCompletionPercentage(partialInputs, sampleConfig)).toBe(50); // 1 out of 3 total fields
      expect(validator.getCompletionPercentage({}, sampleConfig)).toBe(0);
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
});