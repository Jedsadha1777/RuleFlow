// ts/tests/php-parity.test.ts - Fixed version

import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow } from '../src/RuleFlow';
import { FormulaProcessor } from '../src/core/FormulaProcessor';
import { FunctionRegistry } from '../src/functions/FunctionRegistry';

describe('PHP-TypeScript Parity Tests', () => {
  let ruleFlow: RuleFlow;
  let processor: FormulaProcessor;
  let functionRegistry: FunctionRegistry;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
    functionRegistry = new FunctionRegistry();
    processor = new FormulaProcessor(functionRegistry);
    
    // 🔧 FIX: Register all test functions in beforeEach
    
    // Functions for PHP Function Operator Behavior tests
    ruleFlow.registerFunction('validateCreditScore', (score: number) => {
      return score >= 600 && score <= 850;
    });

    ruleFlow.registerFunction('calculateRisk', (age: number, score: number) => {
      if (age < 25) return score * 0.8;
      if (age > 65) return score * 0.9;
      return score;
    });

    ruleFlow.registerFunction('isBusinessDay', (dayOfWeek: number) => {
      return dayOfWeek >= 1 && dayOfWeek <= 5; // Monday = 1, Friday = 5
    });

    // Functions for Complete PHP Workflow Reproduction tests
    ruleFlow.registerFunction('calculateInterestRate', (creditScore: number, loanAmount: number) => {
      if (creditScore >= 800) return 3.5;
      if (creditScore >= 700) return 4.5;
      if (creditScore >= 600) return 6.0;
      return 8.5;
    });

    ruleFlow.registerFunction('isEligibleForLoan', (income: number, debtRatio: number) => {
      return income >= 30000 && debtRatio <= 0.4;
    });

    // Function for rejection cases test
    ruleFlow.registerFunction('isValidEmail', (email: string) => {
      return typeof email === 'string' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    });
  });

  // ========================================
  // Test Case 1: PHP's exact set_vars behavior
  // ========================================
  describe('PHP set_vars Behavior Reproduction', () => {
    it('should match PHP processSetVars with simple reference', async () => {
      // Equivalent to PHP: "$calculated": "$base_value"
      const config = {
        formulas: [
          {
            id: 'simple_reference',
            switch: 'trigger',
            when: [
              {
                if: { op: '==', value: 'test' },
                result: 'processed',
                set_vars: {
                  '$calculated': '$base_value',
                  '$double_ref': '$calculated'
                }
              }
            ],
            default: 'not_processed'
          }
        ]
      };

      const inputs = {
        trigger: 'test',
        base_value: 42
      };

      const result = await ruleFlow.evaluate(config, inputs);
      
      // Should preserve exact value and type like PHP
      expect(result.calculated).toBe(42);
      expect(result.double_ref).toBe(42);
      expect(typeof result.calculated).toBe('number');
      expect(result.simple_reference).toBe('processed');
    });

    it('should match PHP hasVariablesOrOperators detection', async () => {
      // Test PHP's logic: hasVar || hasOp
      const config = {
        formulas: [
          {
            id: 'expression_detection',
            switch: 'mode',
            when: [
              {
                if: { op: '==', value: 'math' },
                result: 'calculated',
                set_vars: {
                  '$simple_math': '$a + $b',
                  '$complex_math': '$a * $b + ($c / 2)',
                  '$with_numbers': '$a + 100',
                  '$pure_numbers': '50 + 25'
                }
              }
            ],
            default: 'skipped'
          }
        ]
      };

      const inputs = {
        mode: 'math',
        a: 10,
        b: 5,
        c: 20
      };

      const result = await ruleFlow.evaluate(config, inputs);
      
      expect(result.simple_math).toBe(15); // 10 + 5
      expect(result.complex_math).toBe(60); // 10 * 5 + (20 / 2)
      expect(result.with_numbers).toBe(110); // 10 + 100
      expect(result.pure_numbers).toBe(75); // 50 + 25
    });

    it('should handle PHP-style error cases in set_vars', async () => {
      const config = {
        formulas: [
          {
            id: 'error_reference',
            switch: 'trigger',
            when: [
              {
                if: { op: '==', value: 'test' },
                result: 'processing',
                set_vars: {
                  '$invalid_ref': '$non_existent_variable'
                }
              }
            ]
          }
        ]
      };

      const inputs = { trigger: 'test' };

      // Should throw like PHP: "Reference variable '$non_existent_variable' not found in context"
      await expect(ruleFlow.evaluate(config, inputs)).rejects.toThrow(
        /Reference variable.*not found|non_existent_variable/
      );
    });
  });

  // ========================================
  // Test Case 2: PHP's condition value resolution
  // ========================================
  describe('PHP Condition Value Resolution', () => {
    it('should match PHP evaluateCondition with $ value substitution', async () => {
      // Equivalent to PHP's logic in FormulaProcessor::evaluateCondition
      const config = {
        formulas: [
          {
            id: 'dynamic_conditions',
            switch: 'user_score',
            when: [
              {
                // PHP: if (is_string($value) && substr($value, 0, 1) === '$')
                if: { op: '>', value: '$passing_grade' },
                result: 'excellent',
                set_vars: { '$status': 'top_performer' }
              },
              {
                if: { op: '>=', value: '$minimum_grade' },
                result: 'passed',
                set_vars: { '$status': 'satisfactory' }
              },
              {
                if: { op: '>=', value: '$failing_grade' },
                result: 'needs_improvement',
                set_vars: { '$status': 'warning' }
              }
            ],
            default: 'failed'
          }
        ]
      };

      const inputs = {
        user_score: 85,
        passing_grade: 90,
        minimum_grade: 70,
        failing_grade: 50
      };

      const result = await ruleFlow.evaluate(config, inputs);
      
      // user_score (85) >= minimum_grade (70) but not > passing_grade (90)
      expect(result.dynamic_conditions).toBe('passed');
      expect(result.status).toBe('satisfactory');
    });

    it('should handle PHP array conditions with variable substitution', async () => {
      const config = {
        formulas: [
          {
            id: 'array_conditions',
            switch: 'user_role',
            when: [
              {
                if: { op: 'in', value: '$admin_roles' },
                result: 'admin_access',
                set_vars: { '$permission_level': 100 }
              },
              {
                if: { op: 'in', value: '$user_roles' },
                result: 'user_access',
                set_vars: { '$permission_level': 50 }
              },
              {
                if: { op: 'not_in', value: '$blocked_roles' },
                result: 'guest_access',
                set_vars: { '$permission_level': 10 }
              }
            ],
            default: 'no_access'
          }
        ]
      };

      const inputs = {
        user_role: 'editor',
        admin_roles: ['admin', 'super_admin'],
        user_roles: ['editor', 'author', 'contributor'],
        blocked_roles: ['banned', 'suspended']
      };

      const result = await ruleFlow.evaluate(config, inputs);
      
      expect(result.array_conditions).toBe('user_access');
      expect(result.permission_level).toBe(50);
    });

    it('should reproduce PHP between operator with variable substitution', async () => {
      const config = {
        formulas: [
          {
            id: 'range_check',
            switch: 'temperature',
            when: [
              {
                if: { op: 'between', value: '$optimal_range' },
                result: 'optimal',
                set_vars: { '$climate_status': 'perfect' }
              },
              {
                if: { op: 'between', value: '$acceptable_range' },
                result: 'acceptable',
                set_vars: { '$climate_status': 'good' }
              }
            ],
            default: 'poor'
          }
        ]
      };

      const inputs = {
        temperature: 24,
        optimal_range: [22, 26],
        acceptable_range: [18, 30]
      };

      const result = await ruleFlow.evaluate(config, inputs);
      
      expect(result.range_check).toBe('optimal');
      expect(result.climate_status).toBe('perfect');
    });
  });

  // ========================================
  // Test Case 3: PHP's function operator behavior
  // ========================================
  describe('PHP Function Operator Behavior', () => {
    it('should match PHP function operator with single parameter', async () => {
      // Equivalent to PHP: case 'function': $registry->call($condition['function'], [$switchValue])
      const config = {
        formulas: [
          {
            id: 'credit_validation',
            switch: 'credit_score',
            when: [
              {
                if: { op: 'function', function: 'validateCreditScore' },
                result: 'valid_credit',
                set_vars: { '$credit_status': 'approved' }
              }
            ],
            default: 'invalid_credit'
          }
        ]
      };

      // Test valid credit score
      const validInputs = { credit_score: 720 };
      const validResult = await ruleFlow.evaluate(config, validInputs);
      
      expect(validResult.credit_validation).toBe('valid_credit');
      expect(validResult.credit_status).toBe('approved');

      // Test invalid credit score
      const invalidInputs = { credit_score: 500 };
      const invalidResult = await ruleFlow.evaluate(config, invalidInputs);
      
      expect(invalidResult.credit_validation).toBe('invalid_credit');
    });

    it('should handle PHP function operator with multiple parameters', async () => {
      const config = {
        formulas: [
          {
            id: 'risk_assessment',
            switch: 'process_type',
            when: [
              {
                if: { 
                  op: 'function', 
                  function: 'calculateRisk',
                  params: ['$customer_age', '$base_score']
                },
                result: 'risk_calculated',
              
              }
            ],
            default: 'no_calculation'
          }
        ]
      };

      const inputs = {
        process_type: 'calculate',
        customer_age: 30,
        base_score: 100
      };
      
      // Note: This test expects the function result to be used as boolean
      // PHP behavior: return (bool)$result;
      const result = await ruleFlow.evaluate(config, inputs);
      
      // calculateRisk(30, 100) = 100 (truthy), so condition passes
      expect(result.risk_assessment).toBe('risk_calculated');
    });

    it('should reproduce PHP function operator in nested conditions', async () => {
      const config = {
        formulas: [
          {
            id: 'complex_business_logic',
            switch: 'request_type',
            when: [
              {
                if: {
                  and: [
                    { op: 'function', var: 'current_day', function: 'isBusinessDay' },
                    { op: 'function', var: 'credit_score', function: 'validateCreditScore' },
                    { op: '>', var: 'amount', value: 1000 }
                  ]
                },
                result: 'approved_business_request',
                set_vars: { 
                  '$approval_status': 'immediate',
                  '$processing_fee': '$amount * 0.02'
                }
              },
              {
                if: {
                  or: [
                    { op: 'function', var: 'credit_score', function: 'validateCreditScore' },
                    { op: '<=', var: 'amount', value: 500 }
                  ]
                },
                result: 'conditional_approval',
                set_vars: { '$approval_status': 'review_required' }
              }
            ],
            default: 'rejected'
          }
        ]
      };

      // Test business day with good credit and high amount
      const businessDayInputs = {
        request_type: 'loan',
        current_day: 3, // Wednesday
        credit_score: 750,
        amount: 5000
      };

      const businessDayResult = await ruleFlow.evaluate(config, businessDayInputs);
      
      expect(businessDayResult.complex_business_logic).toBe('approved_business_request');
      expect(businessDayResult.approval_status).toBe('immediate');
      expect(businessDayResult.processing_fee).toBe(100); // 5000 * 0.02

      // Test weekend with good credit but low amount
      const weekendInputs = {
        request_type: 'loan',
        current_day: 6, // Saturday
        credit_score: 750,
        amount: 300
      };

      const weekendResult = await ruleFlow.evaluate(config, weekendInputs);
      
      expect(weekendResult.complex_business_logic).toBe('conditional_approval');
      expect(weekendResult.approval_status).toBe('review_required');
    });
  });

  // ========================================
  // Test Case 4: Complete PHP workflow reproduction
  // ========================================
  describe('Complete PHP Workflow Reproduction', () => {
    it('should reproduce complex PHP business logic exactly', async () => {
      // This configuration mimics a real PHP RuleFlow configuration
      const config = {
        formulas: [
          {
            id: 'loan_eligibility',
            switch: 'application_type',
            when: [
              {
                if: {
                  and: [
                    { op: 'function', var: 'annual_income', function: 'isEligibleForLoan', params: ['$annual_income', '$debt_to_income_ratio'] },
                    { op: '>=', var: 'credit_score', value: '$minimum_credit_score' },
                    { op: '<=', var: 'loan_amount', value: '$maximum_loan_amount' }
                  ]
                },
                result: 'eligible',
                set_vars: {
                  '$base_interest_rate': '$calculated_rate',
                  '$monthly_payment': '$loan_amount * ($base_interest_rate / 12) / (1 - (1 + $base_interest_rate / 12) ** (-$loan_term_months))',
                  '$total_interest': '$monthly_payment * $loan_term_months - $loan_amount'
                }
              }
            ],
            default: 'not_eligible'
          },
          {
            id: 'interest_calculation',
            switch: '$loan_eligibility',
            when: [
              {
                if: { op: '==', value: 'eligible' },
                result: 'calculated',
                set_vars: {
                  '$effective_rate': '$base_interest_rate + $risk_premium',
                  '$adjusted_monthly_payment': '$monthly_payment * (1 + $risk_premium)',
                  '$approval_amount': '$loan_amount'
                }
              }
            ],
            default: 'no_calculation'
          },
          {
            id: 'final_decision',
            switch: '$interest_calculation',
            when: [
              {
                if: { 
                  op: 'function',
                  function: 'calculateInterestRate',
                  params: ['$credit_score', '$loan_amount']
                },
                result: 'loan_approved',
                set_vars: {
                  '$final_interest_rate': '$calculated_rate',
                  '$decision_timestamp': '$current_timestamp',
                  '$loan_officer_id': '$processing_officer'
                }
              }
            ],
            default: 'loan_denied'
          }
        ]
      };

      const inputs = {
        application_type: 'standard_loan',
        annual_income: 60000,
        debt_to_income_ratio: 0.25,
        credit_score: 720,
        minimum_credit_score: 650,
        loan_amount: 200000,
        maximum_loan_amount: 500000,
        loan_term_months: 360, // 30 years
        calculated_rate: 0.045, // 4.5%
        risk_premium: 0.005, // 0.5%
        current_timestamp: Date.now(),
        processing_officer: 'LO001'
      };

      const result = await ruleFlow.evaluate(config, inputs);

      // Verify loan eligibility
      expect(result.loan_eligibility).toBe('eligible');
      
      // Verify interest calculations
      expect(result.base_interest_rate).toBe(0.045);
      expect(result.interest_calculation).toBe('calculated');
      expect(result.effective_rate).toBe(0.05); // 0.045 + 0.005
      
      // Verify final decision
      expect(result.final_decision).toBe('loan_approved');
      expect(result.final_interest_rate).toBe(4.5); // From calculateInterestRate function
      expect(result.decision_timestamp).toBe(inputs.current_timestamp);
      expect(result.loan_officer_id).toBe('LO001');

      // Verify complex calculations
      expect(typeof result.monthly_payment).toBe('number');
      expect(result.monthly_payment).toBeGreaterThan(0);
      expect(typeof result.total_interest).toBe('number');
      expect(result.total_interest).toBeGreaterThan(0);
    });

    it('should handle PHP-style rejection cases properly', async () => {
      const config = {
        formulas: [
          {
            id: 'strict_validation',
            switch: 'validation_mode',
            when: [
              {
                if: {
                  and: [
                    { op: 'function', var: 'email', function: 'isValidEmail' },
                    { op: '>', var: 'score', value: '$passing_threshold' },
                    { op: 'in', var: 'category', value: '$allowed_categories' }
                  ]
                },
                result: 'passed_validation',
                set_vars: {
                  '$validation_score': '$score + $bonus_points',
                  '$validation_timestamp': '$current_time'
                }
              }
            ],
            default: 'failed_validation'
          }
        ]
      };

      // Test rejection case: bad email
      const badEmailInputs = {
        validation_mode: 'strict',
        email: 'invalid-email',
        score: 85,
        passing_threshold: 80,
        category: 'premium',
        allowed_categories: ['premium', 'gold', 'platinum'],
        bonus_points: 5,
        current_time: '2024-01-01T12:00:00Z'
      };

      const badEmailResult = await ruleFlow.evaluate(config, badEmailInputs);
      expect(badEmailResult.strict_validation).toBe('failed_validation');
      expect(badEmailResult.validation_score).toBeUndefined();

      // Test rejection case: low score
      const lowScoreInputs = {
        validation_mode: 'strict',
        email: 'valid@example.com',
        score: 75,
        passing_threshold: 80,
        category: 'premium',
        allowed_categories: ['premium', 'gold', 'platinum'],
        bonus_points: 5,
        current_time: '2024-01-01T12:00:00Z'
      };

      const lowScoreResult = await ruleFlow.evaluate(config, lowScoreInputs);
      expect(lowScoreResult.strict_validation).toBe('failed_validation');

      // Test rejection case: wrong category
      const wrongCategoryInputs = {
        validation_mode: 'strict',
        email: 'valid@example.com',
        score: 85,
        passing_threshold: 80,
        category: 'basic',
        allowed_categories: ['premium', 'gold', 'platinum'],
        bonus_points: 5,
        current_time: '2024-01-01T12:00:00Z'
      };

      const wrongCategoryResult = await ruleFlow.evaluate(config, wrongCategoryInputs);
      expect(wrongCategoryResult.strict_validation).toBe('failed_validation');

      // Test success case
      const successInputs = {
        validation_mode: 'strict',
        email: 'valid@example.com',
        score: 85,
        passing_threshold: 80,
        category: 'premium',
        allowed_categories: ['premium', 'gold', 'platinum'],
        bonus_points: 5,
        current_time: '2024-01-01T12:00:00Z'
      };

      const successResult = await ruleFlow.evaluate(config, successInputs);
      expect(successResult.strict_validation).toBe('passed_validation');
      expect(successResult.validation_score).toBe(90); // 85 + 5
      expect(successResult.validation_timestamp).toBe('2024-01-01T12:00:00Z');
    });
  });

  // ========================================
  // Test Case 5: Error handling parity
  // ========================================
  describe('PHP Error Handling Parity', () => {
    it('should throw identical errors to PHP for missing variables', async () => {
      const config = {
        formulas: [
          {
            id: 'error_test',
            switch: 'mode',
            when: [
              {
                if: { op: '>', value: '$undefined_threshold' },
                result: 'should_not_reach',
                set_vars: {
                  '$another_error': '$also_undefined * 2'
                }
              }
            ]
          }
        ]
      };

      const inputs = { mode: 'test' };

      // Should throw error about undefined_threshold first
      await expect(ruleFlow.evaluate(config, inputs)).rejects.toThrow(
        /Condition variable.*not found|undefined_threshold/
      );
    });

    it('should handle PHP-style function call errors', async () => {
      const config = {
        formulas: [
          {
            id: 'function_error_test',
            switch: 'value',
            when: [
              {
                if: { 
                  op: 'function', 
                  function: 'nonExistentFunction',
                  params: ['$value']
                },
                result: 'should_fail'
              }
            ]
          }
        ]
      };

      const inputs = { value: 42 };

      await expect(ruleFlow.evaluate(config, inputs)).rejects.toThrow(
        /Function.*not found|nonExistentFunction/
      );
    });

    it('should preserve PHP data types and precision', async () => {
      const config = {
        formulas: [
          {
            id: 'type_preservation',
            switch: 'trigger',
            when: [
              {
                if: { op: '==', value: 'test' },
                result: 'calculated',
                set_vars: {
                  '$integer_calc': '$int_value * 2',
                  '$float_calc': '$float_value * 1.5',
                  '$string_copy': '$string_value',
                  '$boolean_copy': '$boolean_value'
                }
              }
            ]
          }
        ]
      };

      const inputs = {
        trigger: 'test',
        int_value: 42,
        float_value: 3.14159,
        string_value: 'hello world',
        boolean_value: true
      };

      const result = await ruleFlow.evaluate(config, inputs);
      
      // Type and precision preservation
      expect(result.integer_calc).toBe(84);
      expect(typeof result.integer_calc).toBe('number');
      
      expect(result.float_calc).toBeCloseTo(4.712385, 5);
      expect(typeof result.float_calc).toBe('number');
      
      expect(result.string_copy).toBe('hello world');
      expect(typeof result.string_copy).toBe('string');
      
      expect(result.boolean_copy).toBe(true);
      expect(typeof result.boolean_copy).toBe('boolean');
    });
  });

  // ========================================
  // Test Case 6: Performance comparison baseline
  // ========================================
  describe('Performance Baseline (vs PHP expectations)', () => {
    it('should process complex configurations efficiently', async () => {
      const complexConfig = {
        formulas: [
          {
            id: 'performance_test_1',
            switch: 'process_type',
            when: [
              {
                if: {
                  and: [
                    { op: '>', var: 'value1', value: '$threshold1' },
                    { op: '<', var: 'value2', value: '$threshold2' },
                    { op: 'in', var: 'category', value: '$valid_categories' }
                  ]
                },
                result: 'complex_calculation',
                set_vars: {
                  '$result1': '$value1 * $multiplier1 + $base1',
                  '$result2': '$value2 * $multiplier2 - $base2',
                  '$final_result': '$result1 + $result2 / 2'
                }
              }
            ],
            default: 'no_calculation'
          },
          {
            id: 'performance_test_2',
            switch: '$performance_test_1',
            when: [
              {
                if: { op: '==', value: 'complex_calculation' },
                result: 'post_processed',
                set_vars: {
                  '$adjusted_result': '$final_result * $adjustment_factor',
                  '$rounded_result': '$adjusted_result'
                }
              }
            ]
          }
        ]
      };

      const inputs = {
        process_type: 'calculate',
        value1: 100,
        value2: 50,
        threshold1: 90,
        threshold2: 60,
        category: 'A',
        valid_categories: ['A', 'B', 'C'],
        multiplier1: 1.5,
        multiplier2: 2.0,
        base1: 10,
        base2: 5,
        adjustment_factor: 1.1
      };

      const iterations = 1000;
      const startTime = performance.now();

      for (let i = 0; i < iterations; i++) {
        await ruleFlow.evaluate(complexConfig, inputs);
      }

      const endTime = performance.now();
      const totalTime = endTime - startTime;
      const avgTime = totalTime / iterations;

      // Performance expectation: should be comparable to PHP (< 1ms per evaluation)
      expect(avgTime).toBeLessThan(1.0);
      
      console.log(`Performance: ${avgTime.toFixed(3)}ms per evaluation (${iterations} iterations)`);

      // Verify correctness of final result
      const result = await ruleFlow.evaluate(complexConfig, inputs);
      expect(result.performance_test_1).toBe('complex_calculation');
      expect(result.performance_test_2).toBe('post_processed');
      expect(typeof result.final_result).toBe('number');
      expect(typeof result.adjusted_result).toBe('number');
    });
  });
});