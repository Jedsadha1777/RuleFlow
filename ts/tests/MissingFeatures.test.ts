import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow } from '../src/index.js';

describe('Missing Features Tests - PHP Compatibility', () => {
  let ruleFlow: RuleFlow;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
  });

  function debugScoringResult(result: any, prefix: string = '') {
    console.log(`\n=== Debug Scoring ${prefix} ===`);
    console.log('Full result object:', JSON.stringify(result, null, 2));
    
    const mainKeys = Object.keys(result).filter(k => !k.includes('_'));
    const additionalKeys = Object.keys(result).filter(k => k.includes('_'));
    
    console.log('Main results:', mainKeys.map(k => `${k}: ${result[k]}`));
    console.log('Additional properties:', additionalKeys.map(k => `${k}: ${result[k]}`));
    console.log('======================\n');
    }


  // ===================================================================
  // Test 1: default_vars in switch statements
  // ===================================================================
  describe('default_vars Support', () => {
    it('should handle default_vars when no condition matches', async () => {
      const config = {
        formulas: [
          {
            id: 'promotion_check',
            switch: 'cart_total',
            when: [
              {
                if: { op: '>=', value: 1000 },
                result: 'free_shipping',
                set_vars: { 
                  '$shipping_cost': 0,
                  '$discount_rate': 0.1 
                }
              },
              {
                if: { op: '>=', value: 500 },
                result: 'discount_only',
                set_vars: { 
                  '$shipping_cost': 100,
                  '$discount_rate': 0.05 
                }
              }
            ],
            default: 'no_promotion',
            default_vars: {  // ✅ Test default_vars
              '$shipping_cost': 200,
              '$discount_rate': 0,
              '$free_gift': false,
              '$promotion_code': 'NONE'
            }
          }
        ]
      };

      // Test case ที่ไม่ match เงื่อนไขใดๆ
      const result = await ruleFlow.evaluate(config, { cart_total: 300 });
      
      expect(result.promotion_check).toBe('no_promotion');
      expect(result.shipping_cost).toBe(200);      // จาก default_vars
      expect(result.discount_rate).toBe(0);        // จาก default_vars
      expect(result.free_gift).toBe(false);        // จาก default_vars
      expect(result.promotion_code).toBe('NONE');  // จาก default_vars
    });

    it('should not use default_vars when condition matches', async () => {
      const config = {
        formulas: [
          {
            id: 'shipping_calculation',
            switch: 'order_amount',
            when: [
              {
                if: { op: '>=', value: 1000 },
                result: 'free',
                set_vars: { '$shipping_fee': 0 }
              }
            ],
            default: 'standard',
            default_vars: { '$shipping_fee': 150 }
          }
        ]
      };

      // Test case ที่ match เงื่อนไข - ไม่ควรใช้ default_vars
      const result = await ruleFlow.evaluate(config, { order_amount: 1200 });
      
      expect(result.shipping_calculation).toBe('free');
      expect(result.shipping_fee).toBe(0);  // จาก set_vars ใน when, ไม่ใช่ default_vars
    });
  });

  // ===================================================================
  // Test 2: function_call in when conditions
  // ===================================================================
  describe('Function Call in When Conditions', () => {
    beforeEach(() => {
      // Register test functions
      ruleFlow.registerFunction('calculate_bonus', (salary: number, performance: number) => {
        return Math.round(salary * (performance / 100) * 0.1);
      });

      ruleFlow.registerFunction('calculate_interest_rate', (credit_score: number, income: number) => {
        if (credit_score >= 750 && income >= 5000) return 3.5;
        if (credit_score >= 700 && income >= 4000) return 4.5;
        return 6.0;
      });

      ruleFlow.registerFunction('risk_assessment', (age: number, history: string) => {
        const baseRisk = age < 25 ? 'high' : age < 60 ? 'medium' : 'low';
        return history === 'clean' ? baseRisk : 'high';
      });
    });

    it('should execute function_call in when condition', async () => {
      const config = {
        formulas: [
          {
            id: 'employee_bonus',
            switch: 'performance_rating',
            when: [
              {
                if: { op: '>=', value: 90 },
                function_call: 'calculate_bonus',  // ✅ Function call in when
                params: ['$salary', '$performance_rating']
              },
              {
                if: { op: '>=', value: 70 },
                function_call: 'calculate_bonus',
                params: ['$salary', 80]  // Fixed performance value
              }
            ],
            default: 0
          }
        ]
      };

      // Test high performance
      const result1 = await ruleFlow.evaluate(config, {
        salary: 50000,
        performance_rating: 95
      });
      expect(result1.employee_bonus).toBe(4750); // 50000 * 0.95 * 0.1

      // Test medium performance
      const result2 = await ruleFlow.evaluate(config, {
        salary: 60000,
        performance_rating: 75
      });
      expect(result2.employee_bonus).toBe(4800); // 60000 * 0.8 * 0.1

      // Test low performance
      const result3 = await ruleFlow.evaluate(config, {
        salary: 40000,
        performance_rating: 60
      });
      expect(result3.employee_bonus).toBe(0); // Default
    });

    it('should handle multiple function calls with different parameters', async () => {
      const config = {
        formulas: [
          {
            id: 'loan_rate',
            switch: 'application_type',
            when: [
              {
                if: { op: '==', value: 'premium' },
                function_call: 'calculate_interest_rate',
                params: ['$credit_score', '$monthly_income']
              },
              {
                if: { op: '==', value: 'standard' },
                function_call: 'calculate_interest_rate', 
                params: ['$credit_score', '$monthly_income']
              }
            ],
            default: 8.5
          }
        ]
      };

      const result = await ruleFlow.evaluate(config, {
        application_type: 'premium',
        credit_score: 780,
        monthly_income: 6000
      });

      expect(result.loan_rate).toBe(3.5);
    });
  });

  // ===================================================================
  // Test 3: function_call in default values
  // ===================================================================
  describe('Function Call in Default Values', () => {
    beforeEach(() => {
      ruleFlow.registerFunction('default_shipping', (weight: number, distance: number) => {
        return Math.max(50, weight * 10 + distance * 2);
      });

      ruleFlow.registerFunction('penalty_calculation', (days_late: number) => {
        return Math.min(1000, days_late * 25);
      });
    });

    it('should execute function_call in default value', async () => {
      const config = {
        formulas: [
          {
            id: 'shipping_cost',
            switch: 'shipping_method',
            when: [
              {
                if: { op: '==', value: 'express' },
                result: 300
              },
              {
                if: { op: '==', value: 'overnight' },
                result: 500
              }
            ],
            default: {  // ✅ Function call in default
              function_call: 'default_shipping',
              params: ['$package_weight', '$delivery_distance']
            }
          }
        ]
      };

      // Test case ที่ใช้ default function
      const result = await ruleFlow.evaluate(config, {
        shipping_method: 'standard',
        package_weight: 5,
        delivery_distance: 20
      });

      expect(result.shipping_cost).toBe(90); // max(50, 5*10 + 20*2)
    });

    it('should handle complex default function with multiple params', async () => {
      const config = {
        formulas: [
          {
            id: 'late_fee',
            switch: 'payment_status',
            when: [
              {
                if: { op: '==', value: 'on_time' },
                result: 0
              },
              {
                if: { op: '==', value: 'early' },
                result: -50  // Discount for early payment
              }
            ],
            default: {
              function_call: 'penalty_calculation',
              params: ['$days_overdue']
            }
          }
        ]
      };

      const result = await ruleFlow.evaluate(config, {
        payment_status: 'late',
        days_overdue: 15
      });

      expect(result.late_fee).toBe(375); // min(1000, 15 * 25)
    });
  });

  // ===================================================================
  // Test 4: Additional properties in scoring results
  // ===================================================================
  describe('Additional Properties in Scoring', () => {
    it('should store additional properties from scoring ranges', async () => {
      const config = {
        formulas: [
          {
            id: 'credit_assessment',
            scoring: {
              ifs: {
                vars: ['credit_score', 'annual_income'],
                tree: [
                  {
                    if: { op: '>=', value: 750 },
                    ranges: [
                      {
                        if: { op: '>=', value: 80000 },
                        score: 100,
                        tier: 'Platinum',           // ✅ Additional property
                        credit_limit: 100000,      // ✅ Additional property
                        interest_rate: 2.9,        // ✅ Additional property
                        cashback_rate: 0.02,       // ✅ Additional property
                        annual_fee: 0,             // ✅ Additional property
                        decision: 'instant_approval'
                      },
                      {
                        if: { op: '>=', value: 50000 },
                        score: 85,
                        tier: 'Gold',
                        credit_limit: 50000,
                        interest_rate: 3.9,
                        cashback_rate: 0.015,
                        annual_fee: 100,
                        decision: 'approved'
                      }
                    ]
                  },
                  {
                    if: { op: '>=', value: 650 },
                    ranges: [
                      {
                        if: { op: '>=', value: 40000 },
                        score: 70,
                        tier: 'Silver',
                        credit_limit: 25000,
                        interest_rate: 5.9,
                        cashback_rate: 0.01,
                        annual_fee: 200,
                        decision: 'conditional_approval'
                      }
                    ]
                  }
                ]
              }
            }
          }
        ]
      };

      // Test Platinum tier
      const result1 = await ruleFlow.evaluate(config, {
        credit_score: 780,
        annual_income: 120000
      });

      expect(result1.credit_assessment).toBe(100);
      expect(result1.credit_assessment_decision).toBe('instant_approval');
      // ✅ Test additional properties
      expect(result1.credit_assessment_tier).toBe('Platinum');
      expect(result1.credit_assessment_credit_limit).toBe(100000);
      expect(result1.credit_assessment_interest_rate).toBe(2.9);
      expect(result1.credit_assessment_cashback_rate).toBe(0.02);
      expect(result1.credit_assessment_annual_fee).toBe(0);

      // Test Gold tier
      const result2 = await ruleFlow.evaluate(config, {
        credit_score: 760,
        annual_income: 60000
      });

      expect(result2.credit_assessment).toBe(85);
      expect(result2.credit_assessment_tier).toBe('Gold');
      expect(result2.credit_assessment_credit_limit).toBe(50000);
      expect(result2.credit_assessment_interest_rate).toBe(3.9);
    });

    it('should handle additional properties in tree nodes', async () => {
      const config = {
        formulas: [
          {
            id: 'insurance_quote',
            scoring: {
              ifs: {
                vars: ['age', 'driving_record'],
                tree: [
                  {
                    if: { op: 'between', value: [25, 45] },
                    score: 80,
                    base_premium: 1200,        // ✅ Additional property in node
                    risk_category: 'low',       // ✅ Additional property in node
                    discount_eligible: true,    // ✅ Additional property in node
                    max_coverage: 500000       // ✅ Additional property in node
                  },
                  {
                    if: { op: 'between', value: [18, 24] },
                    score: 60,
                    base_premium: 2400,
                    risk_category: 'high',
                    discount_eligible: false,
                    max_coverage: 250000
                  }
                ]
              }
            }
          }
        ]
      };

      const result = await ruleFlow.evaluate(config, {
        age: 35,
        driving_record: 'clean'
      });

      expect(result.insurance_quote).toBe(80);
      expect(result.insurance_quote_base_premium).toBe(1200);
      expect(result.insurance_quote_risk_category).toBe('low');
      expect(result.insurance_quote_discount_eligible).toBe(true);
      expect(result.insurance_quote_max_coverage).toBe(500000);
    });
  });

  // ===================================================================
  // Test 5: Integration test - ทุก feature รวมกัน
  // ===================================================================
  describe('Integration Test - All Missing Features', () => {
    beforeEach(() => {
      ruleFlow.registerFunction('calculate_premium_rate', (income: number, risk_score: number) => {
        const base_rate = 0.05;
        const income_factor = income > 100000 ? 0.8 : income > 50000 ? 0.9 : 1.0;
        const risk_factor = risk_score < 30 ? 0.8 : risk_score < 70 ? 1.0 : 1.2;
        return base_rate * income_factor * risk_factor;
      });

      ruleFlow.registerFunction('rejection_reason', (credit_score: number) => {
        if (credit_score < 500) return 'poor_credit_history';
        if (credit_score < 600) return 'insufficient_credit_history';
        return 'income_verification_required';
      });
    });

    it('should handle complex loan application with all features', async () => {
      const config = {
        formulas: [
          {
            id: 'loan_decision',
            switch: 'credit_score',
            when: [
              {
                if: { op: '>=', value: 750 },
                function_call: 'calculate_premium_rate',  // ✅ Function in when
                params: ['$annual_income', '$risk_score'],
                set_vars: { '$approval_status': 'instant' }
              },
              {
                if: { op: '>=', value: 650 },
                result: 'manual_review',
                set_vars: { 
                  '$approval_status': 'pending',
                  '$review_time': 48 
                }
              }
            ],
            default: {  // ✅ Function in default
              function_call: 'rejection_reason',
              params: ['$credit_score']
            },
            default_vars: {  // ✅ default_vars
              '$interest_rate': 0,
              '$max_loan_amount': 0,
              '$approval_status': 'rejected'
            }
          },
          {
            id: 'loan_terms',
            scoring: {
              ifs: {
                vars: ['annual_income', 'debt_to_income'],
                tree: [
                  {
                    if: { op: '>=', value: 100000 },
                    ranges: [
                      {
                        if: { op: '<=', value: 0.3 },
                        score: 100,
                        max_amount: 500000,      // ✅ Additional properties
                        term_years: 30,          // ✅ Additional properties
                        processing_fee: 0,       // ✅ Additional properties
                        early_payment: true      // ✅ Additional properties
                      }
                    ]
                  }
                ]
              }
            }
          }
        ]
      };

      // Test case 1: High credit score (function call in when)
      const result1 = await ruleFlow.evaluate(config, {
        credit_score: 780,
        annual_income: 120000,
        risk_score: 25,
        debt_to_income: 0.2
      });

      expect(result1.loan_decision).toBe(0.032); // Function result
      expect(result1.approval_status).toBe('instant');
      expect(result1.loan_terms).toBe(100);
      expect(result1.loan_terms_max_amount).toBe(500000);
      expect(result1.loan_terms_term_years).toBe(30);

      // Test case 2: Low credit score (function call in default)
      const result2 = await ruleFlow.evaluate(config, {
        credit_score: 450,
        annual_income: 40000,
        risk_score: 80,
        debt_to_income: 0.5
      });

      expect(result2.loan_decision).toBe('poor_credit_history'); // Function result
      expect(result2.approval_status).toBe('rejected'); // default_vars
      expect(result2.interest_rate).toBe(0); // default_vars
      expect(result2.max_loan_amount).toBe(0); // default_vars
    });
  });

  // ===================================================================
  // Test 6: Error handling for missing features
  // ===================================================================
  describe('Error Handling', () => {
    it('should throw error for missing function in when condition', async () => {
      const config = {
        formulas: [
          {
            id: 'test',
            switch: 'value',
            when: [
              {
                if: { op: '==', value: 'test' },
                function_call: 'non_existent_function',
                params: ['param']
              }
            ],
            default: 0
          }
        ]
      };

      await expect(
        ruleFlow.evaluate(config, { value: 'test' })
      ).rejects.toThrow();
    });

    it('should throw error for missing function in default', async () => {
      const config = {
        formulas: [
          {
            id: 'test',
            switch: 'value',
            when: [],
            default: {
              function_call: 'missing_function',
              params: []
            }
          }
        ]
      };

      await expect(
        ruleFlow.evaluate(config, { value: 'anything' })
      ).rejects.toThrow();
    });
  });
});

// ===================================================================
// Performance test for missing features
// ===================================================================
describe('Performance Tests for Missing Features', () => {
  let ruleFlow: RuleFlow;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
    ruleFlow.registerFunction('fast_calculation', (a: number, b: number) => a * b + 100);
  });

  it('should handle large datasets with missing features', async () => {
    const config = {
      formulas: [
        {
          id: 'processing',
          switch: 'type',
          when: [
            {
              if: { op: '==', value: 'premium' },
              function_call: 'fast_calculation',
              params: ['$value1', '$value2']
            }
          ],
          default: 'standard',
          default_vars: { '$result': 50 }
        }
      ]
    };

    const startTime = performance.now();
    
    // Test with 1000 iterations
    for (let i = 0; i < 1000; i++) {
      await ruleFlow.evaluate(config, {
        type: i % 2 === 0 ? 'premium' : 'standard',
        value1: i,
        value2: i * 2
      });
    }
    
    const endTime = performance.now();
    const executionTime = endTime - startTime;
    
    // Should complete within reasonable time (less than 1 second)
    expect(executionTime).toBeLessThan(1000);
    console.log(`Performance test completed in ${executionTime.toFixed(2)}ms`);
  });
});