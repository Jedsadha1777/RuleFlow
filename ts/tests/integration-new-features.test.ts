// ไฟล์: ts/tests/integration-new-features.test.ts

import { describe, it, expect } from 'vitest';
import { RuleFlow } from '../src/RuleFlow.js';
import { InputValidator } from '../src/validators/InputValidator.js';
import { SchemaGenerator } from '../src/generators/SchemaGenerator.js';
import type { RuleFlowConfig } from '../src/types.js';

describe('Integration Tests - New Features', () => {
  const ruleFlow = new RuleFlow();
  const validator = new InputValidator();
  const generator = new SchemaGenerator();

  const bmiConfig: RuleFlowConfig = {
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

  describe('BMI Calculator End-to-End', () => {
    it('should complete full BMI workflow', async () => {
      const userInputs = { weight: 70, height: 1.75 };

      const fieldResults = validator.validateFields(userInputs, bmiConfig);
      expect(fieldResults.weight.valid).toBe(true);
      expect(fieldResults.height.valid).toBe(true);

      const testResult = await ruleFlow.testConfig(bmiConfig, { weight: 70, height: 1.75 });
      expect(testResult.valid).toBe(true);
      expect(testResult.test_result?.category).toBe('Normal');

      const result = await ruleFlow.evaluate(bmiConfig, userInputs);
      expect(result.bmi).toBeCloseTo(22.86, 2);
      expect(result.category).toBe('Normal');
    });

    it('should handle progressive form validation', () => {
      const progressiveInputs = [
        { weight: 70 },
        { weight: 70, height: '' },
        { weight: 70, height: 1.75 }
      ];

      progressiveInputs.forEach((inputs, index) => {
        const status = validator.getValidationStatus(inputs, bmiConfig);
        
        if (index === 0) {
          expect(status.ready_to_submit).toBe(false);
          expect(status.validation_score).toBe(50); // 1 out of 2 base inputs
        } else if (index === 1) {
          expect(status.ready_to_submit).toBe(false);
          expect(status.validation_score).toBe(50);
        } else {
          expect(status.ready_to_submit).toBe(true);
          expect(status.validation_score).toBe(100); // 2 out of 2 base inputs
        }
      });
    });

    it('should generate complete documentation', () => {
      const schema = generator.generateInputSchema(bmiConfig);
      const docs = generator.generateDocumentation(bmiConfig);
      
      expect(schema.properties).toHaveProperty('weight');
      expect(schema.properties).toHaveProperty('height');
      
      expect(docs.typeScriptInterface).toContain('weight');
      expect(docs.typeScriptInterface).toContain('height');
      expect(docs.inputSchema.required).toContain('weight');
      expect(docs.inputSchema.required).toContain('height');
      expect(docs.summary.totalFormulas).toBeGreaterThan(0);
      expect(docs.summary.requiredInputs).toBeGreaterThan(0);
    });
  });

  describe('Loan Application End-to-End', () => {
    const loanConfig: RuleFlowConfig = {
      formulas: [
        {
          id: 'debt_ratio',
          formula: 'monthly_debt / monthly_income',
          inputs: ['monthly_debt', 'monthly_income']
        },
        {
          id: 'decision',
          switch: '$debt_ratio',
          when: [
            { if: { op: '<', value: 0.3 }, result: 'approved' },
            { if: { op: '<', value: 0.5 }, result: 'review' }
          ],
          default: 'rejected'
        }
      ]
    };

    it('should process loan application workflow', async () => {
      const loanInputs = {
        monthly_income: 5000,
        monthly_debt: 1000
      };

      const securityCheck = validator.validateInputSecurity(loanInputs);
      expect(securityCheck.safe).toBe(true);

      const fieldResults = validator.validateFields(loanInputs, loanConfig);
      expect(Object.values(fieldResults).every(r => r.valid)).toBe(true);

      const status = validator.getValidationStatus(loanInputs, loanConfig);
      expect(status.ready_to_submit).toBe(true);
      expect(status.validation_score).toBe(100); // 2 out of 2 base inputs

      const result = await ruleFlow.evaluate(loanConfig, loanInputs);
      expect(result.debt_ratio).toBe(0.2);
      expect(result.decision).toBe('approved');
    });

    it('should handle rejection scenarios', async () => {
      const badLoanInputs = {
        monthly_income: 2000,
        monthly_debt: 1500
      };

      const result = await ruleFlow.evaluate(loanConfig, badLoanInputs);
      expect(result.debt_ratio).toBe(0.75);
      expect(result.decision).toBe('rejected');
    });

    it('should detect malicious input attempts', () => {
      const maliciousInputs = {
        monthly_income: '5000',
        monthly_debt: 'DROP TABLE loans;'
      };

      const securityCheck = validator.validateInputSecurity(maliciousInputs);
      expect(securityCheck.safe).toBe(false);
      expect(securityCheck.threats[0].threat_type).toBe('SQL_INJECTION');

      const clean = validator.sanitizeInputsAdvanced(maliciousInputs, {
        removeHtml: true
      });
      expect(clean.monthly_income).toBe('5000');
      expect(clean.monthly_debt).not.toContain('DROP');
    });
  });

  describe('Batch Processing', () => {
    it('should validate multiple configurations', () => {
      const configs = [
        bmiConfig,
        { formulas: [{ id: 'simple', formula: 'a + b', inputs: ['a', 'b'] }] },
        { formulas: [{ id: 'broken' }] }
      ];

      const results = ruleFlow.validateBatch(configs);
      
      expect(results).toHaveLength(3);
      expect(results[0].valid).toBe(true);
      expect(results[1].valid).toBe(true);
      expect(results[2].valid).toBe(false);
    });

    it('should process multiple test configurations', async () => {
      const testConfigs = [
        { config: { formulas: [{ id: 'double', formula: 'x * 2', inputs: ['x'] }] }, input: { x: 5 } },
        { config: { formulas: [{ id: 'square', formula: 'x ** 2', inputs: ['x'] }] }, input: { x: 5 } }
      ];

      const results = await Promise.all(
        testConfigs.map(({ config, input }) => ruleFlow.testConfig(config, input))
      );

      expect(results[0].valid).toBe(true);
      expect(results[0].test_result?.double).toBe(10);
      
      expect(results[1].valid).toBe(true);
      expect(results[1].test_result?.square).toBe(25);
    });
  });

  describe('Performance and Edge Cases', () => {
    it('should handle large configurations efficiently', async () => {
      const largeConfig: RuleFlowConfig = {
        formulas: []
      };
      
      // สร้าง 49 formulas ที่ใช้ input เดียวกัน
      for (let i = 0; i < 49; i++) {
        largeConfig.formulas.push({
          id: `calc_${i}`,
          formula: `input * ${i + 1}`,
          inputs: ['input']
        });
      }


      const startTime = Date.now();
      const testResult = await ruleFlow.testConfig(largeConfig, { input: 1 });
      const duration = Date.now() - startTime;

      expect(testResult.valid).toBe(true);
      expect(duration).toBeLessThan(1000);
      expect(Object.keys(testResult.test_result || {})).toHaveLength(50);
    });

    it('should handle edge cases in sanitization', () => {
      const edgeCases = {
        empty: '',
        whitespace: '   ',
        unicode: 'héllo wørld',
        numbers: '123.456',
        mixed: '  123  abc  '
      };

      const cleaned = validator.sanitizeInputsAdvanced(edgeCases);
      
      expect(cleaned.empty).toBe('');
      expect(cleaned.whitespace).toBe('');
      expect(cleaned.unicode).toBe('héllo wørld');
      expect(cleaned.numbers).toBe('123.456');
      expect(cleaned.mixed).toBe('123  abc');
    });

    it('should handle circular references gracefully', async () => {
      const circularConfig: RuleFlowConfig = {
        formulas: [
          { id: 'a', formula: '$b + 1' },
          { id: 'b', formula: '$a + 1' }
        ]
      };

      const result = await ruleFlow.testConfig(circularConfig, {});
      expect(result.valid).toBe(false);
      expect(result.errors.length).toBeGreaterThan(0);
    });

    it('should maintain performance with complex nested validation', () => {
      const complexInputs = {
        field1: 'value1',
        field2: 'value2',
        field3: 'value3',
        field4: 'value4',
        field5: 'value5'
      };

      const startTime = Date.now();
      const results = validator.validateFields(complexInputs, bmiConfig);
      const duration = Date.now() - startTime;

      expect(duration).toBeLessThan(100);
      expect(Object.keys(results)).toHaveLength(5);
    });
  });

  describe('Error Recovery', () => {
    it('should recover from validation errors gracefully', () => {
      const problematicInputs = {
        weight: 'not-a-number',
        height: 'also-not-a-number'
      };

      const fieldResults = validator.validateFields(problematicInputs, bmiConfig);
      
      expect(fieldResults.weight.valid).toBe(true);
      expect(fieldResults.height.valid).toBe(true);

      const status = validator.getValidationStatus(problematicInputs, bmiConfig);
      expect(status.ready_to_submit).toBe(true);
    });

    it('should handle malformed configurations gracefully', async () => {
      const malformedConfig = {
        formulas: [
          { id: 'test' }
        ]
      } as RuleFlowConfig;

      const result = await ruleFlow.testConfig(malformedConfig, {});
      expect(result.valid).toBe(false);
      expect(result.errors.length).toBeGreaterThan(0);
    });
  });

  describe('Integration with Existing Features', () => {
    it('should work with function registry', () => {
      const registry = ruleFlow.getFunctionRegistry();
      
      registry.register('customMax', Math.max, {
        category: 'math',
        description: 'Custom max function'
      });

      const functions = registry.listFunctions();
      expect(functions.some(f => f.name === 'customMax')).toBe(true);
    });

    it('should maintain backward compatibility', () => {
      expect(() => {
        validator.validate({ weight: 70, height: 1.75 }, ['weight', 'height']);
      }).not.toThrow();

      expect(() => {
        validator.validate({}, ['required_field']);
      }).toThrow();
    });
  });
});