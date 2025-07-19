import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow } from '../src/RuleFlow.js';
import { FunctionRegistry } from '../src/functions/FunctionRegistry.js';
import { RuleFlowException } from '../src/exceptions/RuleFlowException.js';

describe('Function Integration Tests', () => {
  let ruleFlow: RuleFlow;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
  });

  // Test 1: Functions in Math Expressions
  describe('Functions in Math Expressions', () => {
    it('should use functions in formulas', async () => {
      const config = {
        formulas: [
          {
            id: 'calculation',
            formula: 'round(sqrt(pow(base, 2) + pow(height, 2)), 2)',
            inputs: ['base', 'height']
          }
        ]
      };

      const inputs = { base: 3, height: 4 };
      const result = await ruleFlow.evaluate(config, inputs);

      expect(result.calculation).toBe(5); // √(3² + 4²) = 5
    });

    it('should handle statistical functions', async () => {
      const config = {
        formulas: [
          {
            id: 'stats',
            formula: 'avg(score1, score2, score3)',
            inputs: ['score1', 'score2', 'score3']
          }
        ]
      };

      const inputs = { score1: 80, score2: 90, score3: 85 };
      const result = await ruleFlow.evaluate(config, inputs);

      expect(result.stats).toBeCloseTo(85, 2);
    });

    it('should chain multiple functions', async () => {
      const config = {
        formulas: [
          {
            id: 'complex_calc',
            formula: 'round(percentage(min(a, b), max(a, b)), 1)',
            inputs: ['a', 'b']
          }
        ]
      };

      const inputs = { a: 25, b: 100 };
      const result = await ruleFlow.evaluate(config, inputs);

      expect(result.complex_calc).toBe(25); // min(25,100)/max(25,100) * 100 = 25%
    });
  });

  // Test 2: Functions in Variable Setting
  describe('Functions in Variable Setting', () => {
    it('should use functions in set_vars', async () => {
      const config = {
        formulas: [{
          id: 'bmi_calculator',
          formula: 'weight + height', // Simple formula
          inputs: ['weight', 'height'],
          set_vars: {
            // ✅ แก้ไข: ใช้ bmi function แทน percentage
            '$bmi_value': 'bmi(weight, height)',
            '$rounded_bmi': 'round($bmi_value, 1)'
          },
          as: 'bmi_calculator'
        }]
      };

      const result = await ruleFlow.evaluate(config, { weight: 70, height: 1.75 });

      expect(result.bmi_calculator).toBe(71.75); // 70 + 1.75
      expect(result.bmi_value).toBeCloseTo(22.86, 2); // bmi(70, 1.75)
      expect(result.rounded_bmi).toBeCloseTo(22.9, 1);
    });

    it('should handle business functions in pricing', async () => {
      const config = {
        formulas: [{
          id: 'pricing',
          switch: 'customer_type',
          when: [{
            if: { op: '==', value: 'premium' },
            result: 'premium_pricing',
            set_vars: {
              // ✅ แก้ไข: ใช้ discount function แทน percentage
              '$discount_amount': 'percentage(base_price, 15)', // 15% of base_price
              '$final_price': 'discount(base_price, 15)' // Apply 15% discount
            }
          }],
          default: 'standard_pricing',
          set_vars: {
            '$discount_amount': 'percentage(base_price, 5)', // 5% of base_price  
            '$final_price': 'discount(base_price, 5)' // Apply 5% discount
          }
        }]
      };

      // Test premium customer
      const result1 = await ruleFlow.evaluate(config, { 
        customer_type: 'premium', 
        base_price: 100 
      });
      expect(result1.pricing).toBe('premium_pricing');
      expect(result1.discount_amount).toBe(15); // 15% of 100 = 15
      expect(result1.final_price).toBe(85); // 100 - 15% = 85

      // Test standard customer
      const result2 = await ruleFlow.evaluate(config, { 
        customer_type: 'standard', 
        base_price: 100 
      });
      expect(result2.pricing).toBe('standard_pricing');
      expect(result2.discount_amount).toBe(5); // 5% of 100 = 5
      expect(result2.final_price).toBe(95); // 100 - 5% = 95
    });
  });

  // Test 3: Functions with Nested Logic
  describe('Functions with Nested Logic', () => {
    it('should use functions in complex conditions', async () => {
      const config = {
        formulas: [
          {
            id: 'loan_approval',
            switch: 'application_type',
            when: [
              {
                if: {
                  and: [
                    { op: '>', var: 'age', value: 21 },
                    {
                      or: [
                        { op: '>', var: 'monthly_income', value: 50000 },
                        { op: '>', var: 'credit_score', value: 700 }
                      ]
                    }
                  ]
                },
                result: 'approved',
                set_vars: {
                  '$interest_rate': 'clamp(normalize(credit_score, 300, 850) * 0.1 + 0.03, 0.03, 0.15)',
                  '$max_loan': 'min(monthly_income * 60, 5000000)'
                }
              }
            ],
            default: 'rejected'
          }
        ]
      };

      const inputs = {
        application_type: 'personal',
        age: 30,
        monthly_income: 60000,
        credit_score: 750
      };

      const result = await ruleFlow.evaluate(config, inputs);

      expect(result.loan_approval).toBe('approved');
      expect(result.interest_rate).toBeGreaterThan(0.03);
      expect(result.interest_rate).toBeLessThan(0.15);
      expect(result.max_loan).toBe(3600000); // min(60000 * 60, 5000000)
    });
  });

  // Test 4: Custom Functions
  describe('Custom Functions', () => {
    it('should register and use custom functions', async () => {
      // Register custom function through RuleFlow
      ruleFlow.getFunctionRegistry().register('tax_calc', (income: number, rate: number) => {
        return income * rate;
      }, {
        category: 'Custom',
        description: 'Calculate tax amount'
      });

      const config = {
        formulas: [
          {
            id: 'net_income',
            formula: 'gross_income - tax_calc(gross_income, tax_rate)',
            inputs: ['gross_income', 'tax_rate']
          }
        ]
      };

      const inputs = { gross_income: 100000, tax_rate: 0.2 };
      const result = await ruleFlow.evaluate(config, inputs);

      expect(result.net_income).toBe(80000); // 100000 - (100000 * 0.2)
    });

    it('should handle function errors gracefully', async () => {
      const config = {
        formulas: [
          {
            id: 'error_test',
            formula: 'sqrt(-1)', // Invalid operation
            inputs: []
          }
        ]
      };

      await expect(ruleFlow.evaluate(config, {}))
        .rejects
        .toThrow(RuleFlowException);
    });
  });

  // Test 5: Multi-step Calculations with Functions
  describe('Multi-step Calculations', () => {
    it('should handle complex multi-step business logic', async () => {
      const config = {
        formulas: [
          {
            id: 'order_subtotal',
            formula: 'sum(item1_price, item2_price, item3_price)',
            inputs: ['item1_price', 'item2_price', 'item3_price']
          },
          {
            id: 'shipping_cost',
            switch: 'shipping_method',
            when: [
              { if: { op: '==', value: 'express' }, result: 50 },
              { if: { op: '==', value: 'standard' }, result: 20 }
            ],
            default: 0
          },
          {
            id: 'discount_amount',
            formula: 'if_null(percentage(order_subtotal, discount_percent), 0)',
            inputs: ['order_subtotal', 'discount_percent']
          },
          {
            id: 'tax_amount',
            formula: 'percentage(order_subtotal - discount_amount, tax_rate)',
            inputs: ['order_subtotal', 'discount_amount', 'tax_rate']
          },
          {
            id: 'final_total',
            formula: 'round(order_subtotal - discount_amount + tax_amount + shipping_cost, 2)',
            inputs: ['order_subtotal', 'discount_amount', 'tax_amount', 'shipping_cost']
          }
        ]
      };

      const inputs = {
        item1_price: 100,
        item2_price: 200,
        item3_price: 150,
        shipping_method: 'express',
        discount_percent: 10,
        tax_rate: 8
      };

      const result = await ruleFlow.evaluate(config, inputs);

      expect(result.order_subtotal).toBe(450);
      expect(result.shipping_cost).toBe(50);
      expect(result.discount_amount).toBe(45); // 10% of 450
      expect(result.tax_amount).toBe(32.4); // 8% of (450 - 45)
      expect(result.final_total).toBe(487.4); // 450 - 45 + 32.4 + 50
    });
  });

  // Test 6: Function Registry Integration
  describe('Function Registry Integration', () => {
    it('should access function registry from RuleFlow', () => {
      const registry = ruleFlow.getFunctionRegistry();
      expect(registry).toBeInstanceOf(FunctionRegistry);
      
      const functions = registry.getAvailableFunctions();
      expect(functions.length).toBeGreaterThan(0);
      expect(functions).toContain('abs');
      expect(functions).toContain('avg');
    });

    it('should categorize functions properly', () => {
      const categories = ruleFlow.getFunctionRegistry().getFunctionsByCategory();
      
      expect(categories.Math).toContain('abs');
      expect(categories.Statistics).toContain('avg');
      expect(categories.Business).toContain('percentage');
      expect(categories.Utility).toContain('clamp');
    });

    it('should provide function information', () => {
      const registry = ruleFlow.getFunctionRegistry();
      const info = registry.getFunctionInfo('bmi');
      
      expect(info?.name).toBe('bmi');
      expect(info?.category).toBe('Utility');
      expect(info?.description).toBe('Calculate BMI');
      expect(info?.parameters).toEqual(['weight', 'height']);
    });
  });
});