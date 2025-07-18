// ไฟล์: ts/tests/integration-new-features.test.ts

import { describe, it, expect } from 'vitest';
import { RuleFlow, InputValidator, SchemaGenerator } from '../src/index.js';
import type { RuleFlowConfig } from '../src/types.js';

describe('Integration Tests - New Features', () => {
  const ruleFlow = new RuleFlow();
  const validator = new InputValidator();
  const generator = new SchemaGenerator();

  // ========================================
  // Real-world BMI Calculator Scenario
  // ========================================

  describe('BMI Calculator End-to-End', () => {
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
            { 
              if: { op: '<', value: 18.5 }, 
              result: 'Underweight',
              set_vars: { '$risk_level': 'moderate', '$recommendation': 'gain_weight' }
            },
            { 
              if: { op: '<', value: 25 }, 
              result: 'Normal',
              set_vars: { '$risk_level': 'low', '$recommendation': 'maintain' }
            },
            { 
              if: { op: '<', value: 30 }, 
              result: 'Overweight',
              set_vars: { '$risk_level': 'moderate', '$recommendation': 'lose_weight' }
            }
          ],
          default: 'Obese',
          set_vars: { '$risk_level': 'high', '$recommendation': 'consult_doctor' }
        }
      ]
    };

    it('should complete full BMI workflow', async () => {
      // 1. Generate schema first
      const schema = generator.generateInputSchema(bmiConfig);
      expect(schema.required).toContain('weight');
      expect(schema.required).toContain('height');

      // 2. Test configuration
      const testResult = await ruleFlow.testConfig(bmiConfig, { weight: 70, height: 1.75 });
      expect(testResult.valid).toBe(true);
      expect(testResult.test_result?.category).toBe('Normal');

      // 3. Validate user input with security
      const userInput = { weight: '75', height: '1.80' };
      const securityCheck = validator.validateInputSecurity(userInput);
      expect(securityCheck.safe).toBe(true);

      // 4. Sanitize input
      const cleanInput = validator.sanitizeInputsAdvanced(userInput);
      expect(cleanInput.weight).toBe('75');

      // 5. Validate against schema
      const schemaValidation = generator.validateAgainstSchema({
        weight: 75,
        height: 1.80,
        bmi: 23.15
      }, schema);
      expect(schemaValidation.valid).toBe(true);

      // 6. Final evaluation
      const result = await ruleFlow.evaluate(bmiConfig, { weight: 75, height: 1.80 });
      expect(result.bmi).toBeCloseTo(23.15, 2);
      expect(result.category).toBe('Normal');
      expect(result.risk_level).toBe('low');
      expect(result.recommendation).toBe('maintain');
    });

    it('should handle progressive form validation', () => {
      // Simulate user filling form step by step
      const steps = [
        {},
        { weight: '70' },
        { weight: '70', height: '1.75' }
      ];

      steps.forEach((stepData, index) => {
        const status = validator.getValidationStatus(stepData, bmiConfig);
        
        if (index === 0) {
          expect(status.ready_to_submit).toBe(false);
          expect(status.validation_score).toBe(0);
        } else if (index === 1) {
          expect(status.ready_to_submit).toBe(false);
          expect(status.validation_score).toBe(50);
        } else {
          expect(status.ready_to_submit).toBe(true);
          expect(status.validation_score).toBe(100);
        }
      });
    });

    it('should generate complete documentation', () => {
      const docs = generator.generateDocumentation(bmiConfig);
      
      expect(docs.summary.totalFormulas).toBe(2);
      expect(docs.summary.requiredInputs).toBe(3); // weight, height, bmi
      expect(docs.typeScriptInterface).toContain('interface RuleFlowInputs');
      expect(docs.typeScriptInterface).toContain('weight:');
      expect(docs.typeScriptInterface).toContain('height:');
    });
  });

  // ========================================
  // Loan Application Scenario
  // ========================================

  describe('Loan Application End-to-End', () => {
    const loanConfig: RuleFlowConfig = {
      formulas: [
        {
          id: 'debt_to_income',
          formula: '(monthly_debt / monthly_income) * 100',
          inputs: ['monthly_debt', 'monthly_income']
        },
        {
          id: 'approval_decision',
          switch: 'application_type',
          when: [
            {
              if: {
                and: [
                  { op: '>=', var: 'credit_score', value: 650 },
                  { op: '<=', var: 'debt_to_income', value: 40 },
                  { op: '>=', var: 'income', value: 30000 }
                ]
              },
              result: 'approved',
              set_vars: { 
                '$approval_reason': 'meets_criteria',
                '$interest_rate': 5.5 
              }
            }
          ],
          default: 'rejected',
          set_vars: { 
            '$approval_reason': 'insufficient_criteria',
            '$interest_rate': null 
          }
        }
      ]
    };

    const loanInputs = {
      monthly_debt: 1500,
      monthly_income: 5000,
      credit_score: 720,
      income: 60000,
      application_type: 'personal'
    };

    it('should process loan application workflow', async () => {
      // 1. Test configuration with sample data
      const testResult = await ruleFlow.testConfig(loanConfig, loanInputs);
      expect(testResult.valid).toBe(true);
      expect(testResult.test_result?.approval_decision).toBe('approved');

      // 2. Validate field by field
      const fieldResults = validator.validateFields(loanInputs, loanConfig);
      expect(fieldResults.credit_score.valid).toBe(true);
      expect(fieldResults.monthly_income.valid).toBe(true);

      // 3. Get validation status
      const status = validator.getValidationStatus(loanInputs, loanConfig);
      expect(status.ready_to_submit).toBe(true);
      expect(status.validation_score).toBe(100);

      // 4. Full evaluation
      const result = await ruleFlow.evaluate(loanConfig, loanInputs);
      expect(result.debt_to_income).toBe(30); // (1500/5000)*100
      expect(result.approval_decision).toBe('approved');
      expect(result.approval_reason).toBe('meets_criteria');
      expect(result.interest_rate).toBe(5.5);
    });

    it('should handle rejection scenarios', async () => {
      const poorCreditInputs = {
        ...loanInputs,
        credit_score: 500 // Below threshold
      };

      const result = await ruleFlow.evaluate(loanConfig, poorCreditInputs);
      expect(result.approval_decision).toBe('rejected');
      expect(result.approval_reason).toBe('insufficient_criteria');
      expect(result.interest_rate).toBe(null);
    });

    it('should detect malicious input attempts', () => {
      const maliciousInputs = {
        monthly_debt: 'DROP TABLE loans;',
        monthly_income: '<script>alert("hack")</script>5000',
        credit_score: 720,
        income: 60000,
        application_type: 'personal'
      };

      const securityCheck = validator.validateInputSecurity(maliciousInputs);
      expect(securityCheck.safe).toBe(false);
      expect(securityCheck.threats.length).toBeGreaterThan(0);
      expect(securityCheck.threats.some(t => t.threat_type === 'SQL_INJECTION')).toBe(true);
      expect(securityCheck.threats.some(t => t.threat_type === 'XSS')).toBe(true);

      // Sanitize the inputs
      const clean = validator.sanitizeInputsAdvanced(maliciousInputs, {
        removeHtml: true
      });
      expect(clean.monthly_income).toBe('5000');
      expect(clean.monthly_debt).not.toContain('DROP');
    });
  });

  // ========================================
  // Batch Processing Scenario
  // ========================================

  describe('Batch Processing', () => {
    const simpleConfigs = [
      {
        formulas: [
          { id: 'double', formula: 'input * 2', inputs: ['input'] }
        ]
      },
      {
        formulas: [
          { id: 'square', formula: 'input ** 2', inputs: ['input'] }
        ]
      },
      {
        formulas: [
          { id: 'invalid' } // Missing execution logic
        ]
      }
    ];

    it('should validate multiple configurations', () => {
      const results = ruleFlow.validateBatch(simpleConfigs);
      
      expect(results).toHaveLength(3);
      expect(results[0].valid).toBe(true);
      expect(results[1].valid).toBe(true);
      expect(results[2].valid).toBe(false);
      
      expect(results[2].errors.length).toBeGreaterThan(0);
    });

    it('should process multiple test configurations', async () => {
      const testPromises = simpleConfigs.slice(0, 2).map((config, index) => 
        ruleFlow.testConfig(config, { input: 5 })
      );

      const results = await Promise.all(testPromises);
      
      expect(results[0].valid).toBe(true);
      expect(results[0].test_result?.double).toBe(10);
      
      expect(results[1].valid).toBe(true);
      expect(results[1].test_result?.square).toBe(25);
    });
  });

  // ========================================
  // Performance and Edge Cases
  // ========================================

  describe('Performance and Edge Cases', () => {
    it('should handle large configurations efficiently', async () => {
      // Generate a large configuration
      const largeConfig: RuleFlowConfig = {
        formulas: Array.from({ length: 50 }, (_, i) => ({
          id: `calc_${i}`,
          formula: `input + ${i}`,
          inputs: ['input']
        }))
      };

      const startTime = Date.now();
      const testResult = await ruleFlow.testConfig(largeConfig, { input: 10 });
      const duration = Date.now() - startTime;

      expect(testResult.valid).toBe(true);
      expect(duration).toBeLessThan(1000); // Should complete within 1 second
      expect(Object.keys(testResult.test_result || {})).toHaveLength(50);
    });

    it('should handle edge cases in sanitization', () => {
      const edgeCases = {
        empty: '',
        whitespace: '   ',
        unicode: '🎉 Hello 世界',
        numbers: '123.456',
        booleans: 'true',
        null_string: 'null',
        undefined_string: 'undefined'
      };

      const sanitized = validator.sanitizeInputsAdvanced(edgeCases);
      
      expect(sanitized.empty).toBe('');
      expect(sanitized.whitespace).toBe('');
      expect(sanitized.unicode).toBe('🎉 Hello 世界');
      expect(sanitized.numbers).toBe('123.456');
    });

    it('should handle circular references gracefully', () => {
      const circularConfig: RuleFlowConfig = {
        formulas: [
          {
            id: 'a',
            formula: '$b + 1',
            inputs: []
          },
          {
            id: 'b', 
            formula: '$a + 1',
            inputs: []
          }
        ]
      };

      const validation = ruleFlow.validateBatch([circularConfig]);
      // Should detect circular dependency or handle gracefully
      expect(validation[0].valid).toBe(false);
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
      
      // Run multiple validation operations
      for (let i = 0; i < 100; i++) {
        validator.sanitizeInputsAdvanced(complexInputs);
        validator.validateInputSecurity(complexInputs);
      }
      
      const duration = Date.now() - startTime;
      expect(duration).toBeLessThan(500); // Should be reasonably fast
    });
  });

  // ========================================
  // Error Recovery and Resilience
  // ========================================

  describe('Error Recovery', () => {
    it('should recover from validation errors gracefully', async () => {
      const problematicInputs = {
        weight: 'not_a_number',
        height: null,
        extra: undefined
      };

      const bmiConfig: RuleFlowConfig = {
        formulas: [
          {
            id: 'bmi',
            formula: 'weight / (height ** 2)',
            inputs: ['weight', 'height']
          }
        ]
      };

      // Should not crash, but return meaningful error information
      const fieldResults = validator.validateFields(problematicInputs, bmiConfig);
      
      expect(fieldResults.weight.valid).toBe(false);
      expect(fieldResults.height.valid).toBe(false);
      
      // Status should reflect the problems
      const status = validator.getValidationStatus(problematicInputs, bmiConfig);
      expect(status.ready_to_submit).toBe(false);
      expect(status.summary.invalid_fields).toBeGreaterThan(0);
    });

    it('should handle malformed configurations gracefully', async () => {
      const malformedConfig = {
        formulas: [
          {
            // Missing id
            formula: 'a + b'
          }
        ]
      } as any;

      const testResult = await ruleFlow.testConfig(malformedConfig, { a: 1, b: 2 });
      expect(testResult.valid).toBe(false);
      expect(testResult.errors.length).toBeGreaterThan(0);
    });
  });

  // ========================================
  // Integration with Existing Features
  // ========================================

  describe('Integration with Existing Features', () => {
    it('should work with function registry', () => {
      // Register a custom function
      ruleFlow.registerFunction('customDouble', (x: number) => x * 2, {
        category: 'Custom',
        description: 'Double the input value'
      });

      const configWithFunction: RuleFlowConfig = {
        formulas: [
          {
            id: 'result',
            formula: 'customDouble(input)',
            inputs: ['input']
          }
        ]
      };

      // Should include custom function fields in schema
      const schema = generator.generateInputSchema(configWithFunction);
      expect(schema.required).toContain('input');
    });

    it('should maintain backward compatibility', async () => {
      // Old-style usage should still work
      const simpleConfig: RuleFlowConfig = {
        formulas: [
          {
            id: 'calc',
            formula: 'a + b',
            inputs: ['a', 'b']
          }
        ]
      };

      const result = await ruleFlow.evaluate(simpleConfig, { a: 1, b: 2 });
      expect(result.calc).toBe(3);

      // Old validation methods should work
      expect(() => {
        validator.validate({ a: 1, b: 2 }, ['a', 'b']);
      }).not.toThrow();
    });
  });
});