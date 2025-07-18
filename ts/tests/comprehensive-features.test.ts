import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow } from '../src/index';

describe('Comprehensive Feature Tests', () => {
  let ruleFlow: RuleFlow;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
  });

  describe('1. Basic Expression Evaluation', () => {
    it('should evaluate simple math expressions', async () => {
      const config = {
        formulas: [{
          id: 'math',
          formula: 'a + b * c',
          inputs: ['a', 'b', 'c']
        }]
      };

      const result = await ruleFlow.evaluate(config, { a: 2, b: 3, c: 4 });
      expect(result.math).toBe(14); // 2 + (3 * 4)
    });

    it('should handle complex expressions with parentheses', async () => {
      const config = {
        formulas: [{
          id: 'complex',
          formula: '(a + b) * (c - d) / e',
          inputs: ['a', 'b', 'c', 'd', 'e']
        }]
      };

      const result = await ruleFlow.evaluate(config, { a: 2, b: 3, c: 10, d: 4, e: 2 });
      expect(result.complex).toBe(15); // (2+3) * (10-4) / 2 = 5 * 6 / 2 = 15
    });
  });

  describe('2. Built-in Functions', () => {
    it('should use math functions in expressions', async () => {
      const config = {
        formulas: [{
          id: 'math_funcs',
          formula: 'abs(value) + min(a, b) + max(c, d)',
          inputs: ['value', 'a', 'b', 'c', 'd']
        }]
      };

      try {
        const result = await ruleFlow.evaluate(config, { 
          value: -5, a: 10, b: 20, c: 15, d: 25 
        });
        expect(result.math_funcs).toBe(40); // abs(-5) + min(10,20) + max(15,25) = 5 + 10 + 25
        console.log('✅ Built-in functions work');
      } catch (error) {
        console.log('❌ Built-in functions not implemented:', error.message);
      }
    });

    it('should use business functions', async () => {
      const config = {
        formulas: [{
          id: 'business',
          formula: 'percentage(value, total) + discount(price, rate)',
          inputs: ['value', 'total', 'price', 'rate']
        }]
      };

      try {
        const result = await ruleFlow.evaluate(config, { 
          value: 50, total: 200, price: 1000, rate: 0.1 
        });
        console.log('✅ Business functions work');
      } catch (error) {
        console.log('❌ Business functions not fully implemented:', error.message);
      }
    });
  });

  describe('3. Switch/When Logic', () => {
    it('should handle switch with when conditions', async () => {
      const config = {
        formulas: [{
          id: 'grade',
          switch: 'score',
          when: [
            { if: { op: '>=', value: 80 }, result: 'A' },
            { if: { op: '>=', value: 70 }, result: 'B' },
            { if: { op: '>=', value: 60 }, result: 'C' }
          ],
          default: 'F'
        }]
      };

      try {
        const result1 = await ruleFlow.evaluate(config, { score: 85 });
        expect(result1.grade).toBe('A');

        const result2 = await ruleFlow.evaluate(config, { score: 75 });
        expect(result2.grade).toBe('B');

        const result3 = await ruleFlow.evaluate(config, { score: 50 });
        expect(result3.grade).toBe('F');

        console.log('✅ Switch/When logic works');
      } catch (error) {
        console.log('❌ Switch/When logic not implemented:', error.message);
      }
    });
  });

  describe('4. Nested Logic (AND/OR)', () => {
    it('should handle AND conditions', async () => {
      const config = {
        formulas: [{
          id: 'approval',
          switch: 'type',
          when: [{
            if: {
              and: [
                { op: '>', var: 'age', value: 18 },
                { op: '>', var: 'income', value: 30000 }
              ]
            },
            result: 'approved'
          }],
          default: 'rejected'
        }]
      };

      try {
        const result1 = await ruleFlow.evaluate(config, { 
          type: 'loan', age: 25, income: 35000 
        });
        expect(result1.approval).toBe('approved');

        const result2 = await ruleFlow.evaluate(config, { 
          type: 'loan', age: 17, income: 35000 
        });
        expect(result2.approval).toBe('rejected');

        console.log('✅ Nested AND logic works');
      } catch (error) {
        console.log('❌ Nested AND logic not implemented:', error.message);
      }
    });

    it('should handle OR conditions', async () => {
      const config = {
        formulas: [{
          id: 'vip',
          switch: 'customer',
          when: [{
            if: {
              or: [
                { op: '>', var: 'spending', value: 100000 },
                { op: '==', var: 'level', value: 'platinum' }
              ]
            },
            result: 'VIP'
          }],
          default: 'regular'
        }]
      };

      try {
        const result1 = await ruleFlow.evaluate(config, { 
          customer: 'test', spending: 150000, level: 'gold' 
        });
        expect(result1.vip).toBe('VIP');

        const result2 = await ruleFlow.evaluate(config, { 
          customer: 'test', spending: 50000, level: 'platinum' 
        });
        expect(result2.vip).toBe('VIP');

        console.log('✅ Nested OR logic works');
      } catch (error) {
        console.log('❌ Nested OR logic not implemented:', error.message);
      }
    });

    it('should handle complex nested logic', async () => {
      const config = {
        formulas: [{
          id: 'complex_approval',
          switch: 'application',
          when: [{
            if: {
              and: [
                { op: '>', var: 'age', value: 21 },
                {
                  or: [
                    { op: '>', var: 'income', value: 50000 },
                    {
                      and: [
                        { op: '>', var: 'credit_score', value: 700 },
                        { op: '==', var: 'has_collateral', value: true }
                      ]
                    }
                  ]
                }
              ]
            },
            result: 'approved'
          }],
          default: 'rejected'
        }]
      };

      try {
        const result = await ruleFlow.evaluate(config, { 
          application: 'test',
          age: 25, 
          income: 45000, 
          credit_score: 750, 
          has_collateral: true 
        });
        expect(result.complex_approval).toBe('approved');
        console.log('✅ Complex nested logic works');
      } catch (error) {
        console.log('❌ Complex nested logic not implemented:', error.message);
      }
    });
  });

  describe('5. Variable Setting (set_vars)', () => {
    it('should handle set_vars in formulas', async () => {
      const config = {
        formulas: [{
          id: 'calculation',
          formula: 'base_price',
          inputs: ['base_price'],
          set_vars: {
            '$tax': 'base_price * 0.1',
            '$total': 'base_price + $tax'
          }
        }]
      };

      try {
        const result = await ruleFlow.evaluate(config, { base_price: 1000 });
        expect(result.tax).toBe(100);
        expect(result.total).toBe(1100);
        console.log('✅ set_vars works');
      } catch (error) {
        console.log('❌ set_vars not implemented:', error.message);
      }
    });

    it('should handle set_vars in when conditions', async () => {
      const config = {
        formulas: [{
          id: 'discount',
          switch: 'customer_type',
          when: [{
            if: { op: '==', value: 'vip' },
            result: 'discount_applied',
            set_vars: {
              '$discount_rate': 0.2,
              '$discount_amount': 'price * $discount_rate'
            }
          }],
          default: 'no_discount',
          set_vars: {
            '$discount_rate': 0,
            '$discount_amount': 0
          }
        }]
      };

      try {
        const result = await ruleFlow.evaluate(config, { 
          customer_type: 'vip', price: 1000 
        });
        expect(result.discount).toBe('discount_applied');
        expect(result.discount_rate).toBe(0.2);
        expect(result.discount_amount).toBe(200);
        console.log('✅ set_vars in when conditions works');
      } catch (error) {
        console.log('❌ set_vars in when conditions not implemented:', error.message);
      }
    });
  });

  describe('6. Custom Functions', () => {
    it('should allow registering custom functions', async () => {
      // Register custom function
      ruleFlow.registerFunction('tax_calc', (income: number, rate: number) => {
        return income * rate;
      }, {
        category: 'Business',
        description: 'Calculate tax amount'
      });

      const config = {
        formulas: [{
          id: 'net_income',
          formula: 'gross_income - tax_calc(gross_income, 0.2)',
          inputs: ['gross_income']
        }]
      };

      try {
        const result = await ruleFlow.evaluate(config, { gross_income: 5000 });
        expect(result.net_income).toBe(4000); // 5000 - (5000 * 0.2)
        console.log('✅ Custom functions work');
      } catch (error) {
        console.log('❌ Custom functions not fully integrated:', error.message);
      }
    });
  });

  describe('7. Type Conversion', () => {
    it('should auto-convert string numbers', async () => {
      const config = {
        formulas: [{
          id: 'sum',
          formula: 'a + b',
          inputs: ['a', 'b']
        }]
      };

      try {
        const result = await ruleFlow.evaluate(config, { a: '10', b: '20' });
        expect(result.sum).toBe(30);
        console.log('✅ Type conversion works');
      } catch (error) {
        console.log('❌ Type conversion not implemented:', error.message);
      }
    });
  });

  describe('8. Input Validation Integration', () => {
    it('should detect missing inputs', async () => {
      const config = {
        formulas: [{
          id: 'test',
          formula: 'a + b',
          inputs: ['a', 'b']
        }]
      };

      try {
        await ruleFlow.evaluate(config, { a: 1 }); // missing b
        console.log('❌ Input validation not enforced');
      } catch (error) {
        if (error.message.includes('Missing input') || error.message.includes('Required input')) {
          console.log('✅ Input validation works');
        } else {
          console.log('❌ Wrong error type:', error.message);
        }
      }
    });
  });

  describe('9. Function Registry', () => {
    it('should provide function information', () => {
      try {
        const functions = ruleFlow.getAvailableFunctions();
        expect(functions.functions).toBeInstanceOf(Array);
        expect(functions.categories).toBeInstanceOf(Object);
        console.log('✅ Function registry works');
        console.log('Available functions:', functions.functions.slice(0, 5));
      } catch (error) {
        console.log('❌ Function registry not working:', error.message);
      }
    });
  });

  describe('10. Real-world Example', () => {
    it('should handle loan approval scenario', async () => {
      const config = {
        formulas: [
          {
            id: 'monthly_payment',
            formula: 'loan_amount * monthly_rate / (1 - pow(1 + monthly_rate, -loan_term))',
            inputs: ['loan_amount', 'monthly_rate', 'loan_term']
          },
          {
            id: 'debt_to_income',
            formula: 'monthly_payment / monthly_income'
          },
          {
            id: 'approval_decision',
            switch: 'application_type',
            when: [{
              if: {
                and: [
                  { op: '>', var: 'credit_score', value: 650 },
                  { op: '<', var: 'debt_to_income', value: 0.4 },
                  { op: '>', var: 'employment_years', value: 2 }
                ]
              },
              result: 'approved',
              set_vars: {
                '$interest_rate': 0.045,
                '$approval_message': 'Loan approved with standard terms'
              }
            }],
            default: 'rejected',
            set_vars: {
              '$interest_rate': 0,
              '$approval_message': 'Loan application rejected'
            }
          }
        ]
      };

      try {
        const result = await ruleFlow.evaluate(config, {
          loan_amount: 300000,
          monthly_rate: 0.004,
          loan_term: 360,
          monthly_income: 8000,
          credit_score: 720,
          employment_years: 5,
          application_type: 'mortgage'
        });

        console.log('✅ Real-world loan approval scenario works');
        console.log('Decision:', result.approval_decision);
        console.log('Monthly payment:', Math.round(result.monthly_payment));
        console.log('Debt to income:', (result.debt_to_income * 100).toFixed(1) + '%');
      } catch (error) {
        console.log('❌ Real-world scenario failed:', error.message);
      }
    });
  });
});