// ts/tests/enhanced-features.test.ts - Fixed version

import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow } from '../src/RuleFlow';
import { FormulaProcessor } from '../src/core/FormulaProcessor';
import { FunctionRegistry } from '../src/functions/FunctionRegistry';

describe('Enhanced TypeScript Features (PHP Parity)', () => {
  let ruleFlow: RuleFlow;
  let processor: FormulaProcessor;
  let functionRegistry: FunctionRegistry;

  beforeEach(() => {
    functionRegistry = new FunctionRegistry();
    ruleFlow = new RuleFlow();
    processor = new FormulaProcessor(functionRegistry);
    
    // 🔧 FIX: Register custom validation functions BEFORE tests
    ruleFlow.registerFunction('isValidEmail', (email: string) => {
      return typeof email === 'string' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }, {
      category: 'Validation',
      description: 'Validate email format'
    });

    ruleFlow.registerFunction('isInRange', (value: number, min: number, max: number) => {
      return value >= min && value <= max;
    }, {
      category: 'Validation',
      description: 'Check if value is in range'
    });

    ruleFlow.registerFunction('hasValidAge', (age: number) => {
      return age >= 18 && age <= 120;
    }, {
      category: 'Validation',
      description: 'Check if age is valid for adults'
    });

    ruleFlow.registerFunction('customValidation', (input: any) => {
      return input !== null && input !== undefined && String(input).length > 0;
    }, {
      category: 'Validation',
      description: 'Custom validation logic'
    });

    // 🔧 FIX: Register business functions for integration tests
    ruleFlow.registerFunction('calculateDiscount', (customerTier: string, orderAmount: number) => {
      const discounts: Record<string, number> = {
        'platinum': 0.15,
        'gold': 0.10,
        'silver': 0.05
      };
      return (discounts[customerTier] || 0) * orderAmount;
    });

    ruleFlow.registerFunction('isEligibleForCredit', (creditScore: number, income: number) => {
      return creditScore >= 650 && income >= 30000;
    });
  });

  // ========================================
  // 🆕 FEATURE 1: Expression Evaluation in set_vars
  // ========================================
  describe('Enhanced set_vars with Expression Evaluation', () => {
    it('should evaluate simple arithmetic expressions in set_vars', async () => {
      const config = {
        formulas: [
          {
            id: 'calculation',
            formula: 'base_value * 2',
            inputs: ['base_value'],
            set_vars: {
              '$calculated': '$base_value * $multiplier + 100',
              '$double_calc': '$calculated * 2'
            }
          }
        ]
      };

      const inputs = {
        base_value: 50,
        multiplier: 3
      };

      const result = await ruleFlow.evaluate(config, inputs);

      // base_value * multiplier + 100 = 50 * 3 + 100 = 250
      expect(result.calculated).toBe(250);
      // calculated * 2 = 250 * 2 = 500
      expect(result.double_calc).toBe(500);
      expect(result.calculation).toBe(100); // base_value * 2
    });

    it('should handle complex expressions with multiple variables', async () => {
      const config = {
        formulas: [
          {
            id: 'complex_calc',
            switch: 'trigger',
            when: [
              {
                if: { op: '==', value: 'calculate' },
                result: 'processing',
                set_vars: {
                  '$total_score': '$math_score + $english_score + $science_score',
                  '$average': '$total_score / 3',
                  '$grade_points': '$average * 4 / 100'
                }
              }
            ],
            default: 'not_calculated'
          }
        ]
      };

      const inputs = {
        trigger: 'calculate',
        math_score: 85,
        english_score: 92,
        science_score: 78
      };

      const result = await ruleFlow.evaluate(config, inputs);

      expect(result.total_score).toBe(255); // 85 + 92 + 78
      expect(result.average).toBe(85); // 255 / 3
      expect(result.grade_points).toBe(3.4); // 85 * 4 / 100
      expect(result.complex_calc).toBe('processing');
    });

    it('should handle simple variable references in set_vars', async () => {
      const config = {
        formulas: [
          {
            id: 'reference_test',
            formula: 'input_value * 2',
            inputs: ['input_value'],
            set_vars: {
              '$copied_value': '$input_value',
              '$another_copy': '$copied_value'
            }
          }
        ]
      };

      const inputs = { input_value: 42 };
      const result = await ruleFlow.evaluate(config, inputs);

      expect(result.copied_value).toBe(42);
      expect(result.another_copy).toBe(42);
      expect(result.reference_test).toBe(84);
    });

    it('should throw error for undefined variables in expressions', async () => {
      const config = {
        formulas: [
          {
            id: 'error_test',
            switch: 'trigger',
            when: [
              {
                if: { op: '==', value: 'test' },
                result: 'ok',
                set_vars: {
                  '$invalid': '$undefined_variable * 2'
                }
              }
            ]
          }
        ]
      };

      const inputs = { trigger: 'test' };

      await expect(ruleFlow.evaluate(config, inputs)).rejects.toThrow(
        /Variable.*not found|undefined_variable/
      );
    });
  });

  // ========================================
  // 🆕 FEATURE 2: Variable Substitution in condition values
  // ========================================
  describe('Variable Substitution in Condition Values', () => {
    it('should resolve $ variables in condition values', async () => {
      const config = {
        formulas: [
          {
            id: 'dynamic_threshold',
            switch: 'user_score',
            when: [
              {
                if: { op: '>', value: '$passing_threshold' },
                result: 'passed',
                set_vars: { '$status': 'success' }
              },
              {
                if: { op: '>', value: '$minimum_threshold' },
                result: 'needs_improvement',
                set_vars: { '$status': 'warning' }
              }
            ],
            default: 'failed'
          }
        ]
      };

      const inputs = {
        user_score: 75,
        passing_threshold: 80,
        minimum_threshold: 60
      };

      const result = await ruleFlow.evaluate(config, inputs);

      // user_score (75) > minimum_threshold (60) but not > passing_threshold (80)
      expect(result.dynamic_threshold).toBe('needs_improvement');
      expect(result.status).toBe('warning');
    });

    it('should work with different condition operators and variable substitution', async () => {
      const config = {
        formulas: [
          {
            id: 'access_control',
            switch: 'user_level',
            when: [
              {
                if: { op: '>=', value: '$admin_level' },
                result: 'admin_access'
              },
              {
                if: { op: '>=', value: '$user_level_threshold' },
                result: 'user_access'
              },
              {
                if: { op: 'in', value: '$allowed_guest_levels' },
                result: 'guest_access'
              }
            ],
            default: 'no_access'
          }
        ]
      };

      const inputs = {
        user_level: 5,
        admin_level: 8,
        user_level_threshold: 3,
        allowed_guest_levels: [1, 2]
      };

      const result = await ruleFlow.evaluate(config, inputs);

      // user_level (5) >= user_level_threshold (3) but not >= admin_level (8)
      expect(result.access_control).toBe('user_access');
    });

    it('should work with nested conditions and variable substitution', async () => {
      const config = {
        formulas: [
          {
            id: 'complex_access',
            switch: 'trigger',
            when: [
              {
                if: {
                  and: [
                    { op: '>=', var: 'user_level', value: '$min_level' },
                    { op: '<=', var: 'user_level', value: '$max_level' },
                    { op: '==', var: 'department', value: '$allowed_dept' }
                  ]
                },
                result: 'approved'
              }
            ],
            default: 'denied'
          }
        ]
      };

      const inputs = {
        trigger: 'check_access',
        user_level: 5,
        department: 'engineering',
        min_level: 3,
        max_level: 7,
        allowed_dept: 'engineering'
      };

      const result = await ruleFlow.evaluate(config, inputs);

      expect(result.complex_access).toBe('approved');
    });

    it('should throw error for undefined condition variables', async () => {
      const config = {
        formulas: [
          {
            id: 'error_condition',
            switch: 'value',
            when: [
              {
                if: { op: '>', value: '$undefined_threshold' },
                result: 'pass'
              }
            ],
            default: 'fail'
          }
        ]
      };

      const inputs = { value: 50 };

      await expect(ruleFlow.evaluate(config, inputs)).rejects.toThrow(
        /Condition variable.*not found|undefined_threshold/
      );
    });
  });

  // ========================================
  // 🆕 FEATURE 3: Function operator in conditions
  // ========================================
  describe('Function Operator in Conditions', () => {
    it('should use function operator in simple conditions', async () => {
      const config = {
        formulas: [
          {
            id: 'email_validation',
            switch: 'user_email',
            when: [
              {
                if: { op: 'function', function: 'isValidEmail' },
                result: 'valid_email',
                set_vars: { '$email_status': 'approved' }
              }
            ],
            default: 'invalid_email'
          }
        ]
      };

      // Test with valid email
      const validInputs = { user_email: 'user@example.com' };
      const validResult = await ruleFlow.evaluate(config, validInputs);
      
      expect(validResult.email_validation).toBe('valid_email');
      expect(validResult.email_status).toBe('approved');

      // Test with invalid email
      const invalidInputs = { user_email: 'invalid-email' };
      const invalidResult = await ruleFlow.evaluate(config, invalidInputs);
      
      expect(invalidResult.email_validation).toBe('invalid_email');
      expect(invalidResult.email_status).toBeUndefined();
    });

    it('should use function operator with parameters', async () => {
      const config = {
        formulas: [
          {
            id: 'age_check',
            switch: 'user_age',
            when: [
              {
                if: { 
                  op: 'function', 
                  function: 'isInRange',
                  params: ['$user_age', 25, 65]
                },
                result: 'working_age',
                set_vars: { '$employment_eligible': true }
              },
              {
                if: { 
                  op: 'function', 
                  function: 'hasValidAge'
                },
                result: 'valid_adult',
                set_vars: { '$employment_eligible': false }
              }
            ],
            default: 'invalid_age'
          }
        ]
      };

      // Test working age
      const workingAgeInputs = { user_age: 35 };
      const workingAgeResult = await ruleFlow.evaluate(config, workingAgeInputs);
      
      expect(workingAgeResult.age_check).toBe('working_age');
      expect(workingAgeResult.employment_eligible).toBe(true);

      // Test valid adult but not working age
      const youngAdultInputs = { user_age: 20 };
      const youngAdultResult = await ruleFlow.evaluate(config, youngAdultInputs);
      
      expect(youngAdultResult.age_check).toBe('valid_adult');
      expect(youngAdultResult.employment_eligible).toBe(false);

      // Test invalid age
      const invalidAgeInputs = { user_age: 15 };
      const invalidAgeResult = await ruleFlow.evaluate(config, invalidAgeInputs);
      
      expect(invalidAgeResult.age_check).toBe('invalid_age');
    });

    it('should combine function operator with logical conditions', async () => {
      const config = {
        formulas: [
          {
            id: 'complex_validation',
            switch: 'trigger',
            when: [
              {
                if: {
                  and: [
                    { op: 'function', var: 'email', function: 'isValidEmail' },
                    { op: 'function', var: 'age', function: 'hasValidAge' },
                    { op: '>', var: 'score', value: 80 }
                  ]
                },
                result: 'fully_qualified',
                set_vars: { 
                  '$validation_status': 'all_checks_passed',
                  '$qualified_score': '$score + 10'
                }
              },
              {
                if: {
                  or: [
                    { op: 'function', var: 'email', function: 'isValidEmail' },
                    { op: 'function', var: 'backup_data', function: 'customValidation' }
                  ]
                },
                result: 'partially_qualified',
                set_vars: { '$validation_status': 'partial_pass' }
              }
            ],
            default: 'not_qualified'
          }
        ]
      };

      // Test fully qualified
      const fullyQualifiedInputs = {
        trigger: 'validate',
        email: 'test@example.com',
        age: 25,
        score: 85,
        backup_data: 'valid'
      };
      
      const fullyQualifiedResult = await ruleFlow.evaluate(config, fullyQualifiedInputs);
      
      expect(fullyQualifiedResult.complex_validation).toBe('fully_qualified');
      expect(fullyQualifiedResult.validation_status).toBe('all_checks_passed');
      expect(fullyQualifiedResult.qualified_score).toBe(95); // score + 10

      // Test partially qualified
      const partiallyQualifiedInputs = {
        trigger: 'validate',
        email: 'test@example.com', // valid email
        age: 15, // invalid age
        score: 70, // low score
        backup_data: null
      };
      
      const partiallyQualifiedResult = await ruleFlow.evaluate(config, partiallyQualifiedInputs);
      
      expect(partiallyQualifiedResult.complex_validation).toBe('partially_qualified');
      expect(partiallyQualifiedResult.validation_status).toBe('partial_pass');

      // Test not qualified
      const notQualifiedInputs = {
        trigger: 'validate',
        email: 'invalid-email',
        age: 15,
        score: 70,
        backup_data: null
      };
      
      const notQualifiedResult = await ruleFlow.evaluate(config, notQualifiedInputs);
      
      expect(notQualifiedResult.complex_validation).toBe('not_qualified');
    });

    it('should handle function operator errors gracefully', async () => {
      const config = {
        formulas: [
          {
            id: 'error_test',
            switch: 'input',
            when: [
              {
                if: { op: 'function', function: 'nonExistentFunction' },
                result: 'should_not_reach'
              }
            ],
            default: 'error_handled'
          }
        ]
      };

      const inputs = { input: 'test' };

      await expect(ruleFlow.evaluate(config, inputs)).rejects.toThrow(
        /Function.*not found|nonExistentFunction/
      );
    });
  });

  // ========================================
  // 🆕 INTEGRATION TESTS: All features together
  // ========================================
  describe('Integration Tests - All Enhanced Features', () => {
    it('should handle real-world business scenario with all features', async () => {
      const config = {
        formulas: [
          {
            id: 'order_processing',
            switch: 'trigger',
            when: [
              {
                if: {
                  and: [
                    { op: '>', var: 'order_amount', value: '$minimum_order' },
                    { op: 'function', var: 'customer_tier', function: 'isEligibleForCredit', params: ['$credit_score', '$annual_income'] }
                  ]
                },
                result: 'eligible_for_processing',
                set_vars: {
                  '$base_discount': '$order_amount * $discount_rate',
                  '$additional_bonus': '$base_discount * 0.1',
                  '$total_discount': '$base_discount + $additional_bonus',
                  '$final_amount': '$order_amount - $total_discount'
                }
              }
            ],
            default: 'not_eligible'
          },
          {
            id: 'discount_calculation',
            switch: '$order_processing',
            when: [
              {
                if: { op: '==', value: 'eligible_for_processing' },
                result: 'calculated',
                set_vars: {
                  '$discount_percentage': '$total_discount / $order_amount * 100'
                }
              }
            ],
            default: 'no_discount'
          }
        ]
      };

      const inputs = {
        trigger: 'process_order',
        order_amount: 1000,
        minimum_order: 500,
        customer_tier: 'gold',
        credit_score: 720,
        annual_income: 50000,
        discount_rate: 0.08
      };

      const result = await ruleFlow.evaluate(config, inputs);

      // Check eligibility
      expect(result.order_processing).toBe('eligible_for_processing');
      
      // Check calculations
      expect(result.base_discount).toBe(80); // 1000 * 0.08
      expect(result.additional_bonus).toBe(8); // 80 * 0.1
      expect(result.total_discount).toBe(88); // 80 + 8
      expect(result.final_amount).toBe(912); // 1000 - 88
      
      // Check discount calculation
      expect(result.discount_calculation).toBe('calculated');
      expect(result.discount_percentage).toBe(8.8); // 88 / 1000 * 100
    });

    it('should handle edge cases and error conditions', async () => {
      const config = {
        formulas: [
          {
            id: 'edge_case_test',
            switch: 'mode',
            when: [
              {
                if: { op: '==', value: 'test_undefined_reference' },
                result: 'processing',
                set_vars: {
                  '$result': '$undefined_variable * 2'
                }
              },
              {
                if: { op: '==', value: 'test_condition_variable' },
                result: 'condition_test',
                set_vars: {
                  '$threshold': 100
                }
              }
            ],
            default: 'no_action'
          },
          {
            id: 'condition_test',
            switch: 'value',
            when: [
              {
                if: { op: '>', value: '$threshold' },
                result: 'above_threshold'
              }
            ],
            default: 'below_threshold'
          }
        ]
      };

      // Test undefined variable error
      const errorInputs = { mode: 'test_undefined_reference' };
      await expect(ruleFlow.evaluate(config, errorInputs)).rejects.toThrow();

      // Test condition variable substitution
      const validInputs = { 
        mode: 'test_condition_variable', 
        value: 150 
      };
      const validResult = await ruleFlow.evaluate(config, validInputs);
      
      expect(validResult.edge_case_test).toBe('condition_test');
      expect(validResult.threshold).toBe(100);
      expect(validResult.condition_test).toBe('above_threshold');
    });
  });

  // ========================================
  // 🆕 PERFORMANCE TESTS
  // ========================================
  describe('Performance Tests for Enhanced Features', () => {
    it('should handle complex expressions efficiently', async () => {
      const config = {
        formulas: Array.from({ length: 10 }, (_, i) => ({
          id: `calc_${i}`,
          switch: 'trigger',
          when: [
            {
              if: { op: '==', value: 'calculate' },
              result: 'processed',
              set_vars: {
                [`$result_${i}`]: `$input_${i} * $multiplier + $base_value / 2`
              }
            }
          ],
          default: 'skipped'
        }))
      };

      const inputs: Record<string, any> = {
        trigger: 'calculate',
        multiplier: 2.5,
        base_value: 100
      };

      // Add input values
      for (let i = 0; i < 10; i++) {
        inputs[`input_${i}`] = (i + 1) * 10;
      }

      const startTime = performance.now();
      const result = await ruleFlow.evaluate(config, inputs);
      const endTime = performance.now();

      // Check results
      for (let i = 0; i < 10; i++) {
        expect(result[`calc_${i}`]).toBe('processed');
        const expectedValue = ((i + 1) * 10) * 2.5 + 100 / 2; // input * multiplier + base_value / 2
        expect(result[`result_${i}`]).toBe(expectedValue);
      }

      // Performance check (should complete within reasonable time)
      const executionTime = endTime - startTime;
      expect(executionTime).toBeLessThan(100); // Less than 100ms
    });
  });
});