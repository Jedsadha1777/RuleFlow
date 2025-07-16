// ts/tests/NestedLogic.test.ts

import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow, RuleFlowException } from '../src/index.js';

describe('Nested Logic Tests', () => {
  let ruleFlow: RuleFlow;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
  });

  // Test 1: Simple AND Logic
  it('should handle simple AND logic', async () => {
    const config = {
      formulas: [
        {
          id: 'approval',
          switch: 'application_type',
          when: [
            {
              if: {
                and: [
                  { op: '>', var: 'age', value: 18 },
                  { op: '>', var: 'income', value: 25000 }
                ]
              },
              result: 'approved'
            }
          ],
          default: 'rejected'
        }
      ]
    };

    // Test approved case (both conditions true)
    const result1 = await ruleFlow.evaluate(config, {
      application_type: 'personal',
      age: 25,
      income: 30000
    });
    expect(result1.approval).toBe('approved');

    // Test rejected case (age condition false)
    const result2 = await ruleFlow.evaluate(config, {
      application_type: 'personal',
      age: 17,
      income: 30000
    });
    expect(result2.approval).toBe('rejected');

    // Test rejected case (income condition false)
    const result3 = await ruleFlow.evaluate(config, {
      application_type: 'personal',
      age: 25,
      income: 20000
    });
    expect(result3.approval).toBe('rejected');
  });

  // Test 2: Simple OR Logic
  it('should handle simple OR logic', async () => {
    const config = {
      formulas: [
        {
          id: 'vip_status',
          switch: 'customer_type',
          when: [
            {
              if: {
                or: [
                  { op: '>', var: 'annual_spending', value: 100000 },
                  { op: '==', var: 'membership_level', value: 'platinum' }
                ]
              },
              result: 'VIP'
            }
          ],
          default: 'regular'
        }
      ]
    };

    // Test VIP case (high spending)
    const result1 = await ruleFlow.evaluate(config, {
      customer_type: 'individual',
      annual_spending: 150000,
      membership_level: 'gold'
    });
    expect(result1.vip_status).toBe('VIP');

    // Test VIP case (platinum member)
    const result2 = await ruleFlow.evaluate(config, {
      customer_type: 'individual',
      annual_spending: 50000,
      membership_level: 'platinum'
    });
    expect(result2.vip_status).toBe('VIP');

    // Test regular case (neither condition true)
    const result3 = await ruleFlow.evaluate(config, {
      customer_type: 'individual',
      annual_spending: 50000,
      membership_level: 'silver'
    });
    expect(result3.vip_status).toBe('regular');
  });

  // Test 3: Complex Nested Logic (AND + OR)
  it('should handle complex nested AND/OR logic', async () => {
    const config = {
      formulas: [
        {
          id: 'loan_decision',
          switch: 'application_type',
          when: [
            {
              if: {
                and: [
                  { op: '>', var: 'age', value: 25 },
                  {
                    or: [
                      { op: '>', var: 'income', value: 30000 },
                      { op: '==', var: 'has_collateral', value: true }
                    ]
                  },
                  { op: '!=', var: 'credit_status', value: 'blacklist' }
                ]
              },
              result: 'approved'
            }
          ],
          default: 'rejected'
        }
      ]
    };

    // Test approved case (age > 25 AND income > 30000 AND not blacklisted)
    const result1 = await ruleFlow.evaluate(config, {
      application_type: 'personal',
      age: 30,
      income: 35000,
      has_collateral: false,
      credit_status: 'good'
    });
    expect(result1.loan_decision).toBe('approved');

    // Test approved case (age > 25 AND has_collateral AND not blacklisted)
    const result2 = await ruleFlow.evaluate(config, {
      application_type: 'personal',
      age: 30,
      income: 25000,
      has_collateral: true,
      credit_status: 'fair'
    });
    expect(result2.loan_decision).toBe('approved');

    // Test rejected case (age <= 25)
    const result3 = await ruleFlow.evaluate(config, {
      application_type: 'personal',
      age: 22,
      income: 35000,
      has_collateral: false,
      credit_status: 'good'
    });
    expect(result3.loan_decision).toBe('rejected');

    // Test rejected case (blacklisted)
    const result4 = await ruleFlow.evaluate(config, {
      application_type: 'personal',
      age: 30,
      income: 35000,
      has_collateral: false,
      credit_status: 'blacklist'
    });
    expect(result4.loan_decision).toBe('rejected');

    // Test rejected case (age > 25 but neither income nor collateral condition met)
    const result5 = await ruleFlow.evaluate(config, {
      application_type: 'personal',
      age: 30,
      income: 25000,
      has_collateral: false,
      credit_status: 'good'
    });
    expect(result5.loan_decision).toBe('rejected');
  });

  // Test 4: Deep Nesting
  it('should handle deep nested logic', async () => {
    const config = {
      formulas: [
        {
          id: 'insurance_premium',
          switch: 'policy_type',
          when: [
            {
              if: {
                and: [
                  {
                    or: [
                      { op: '==', var: 'age_group', value: 'young' },
                      { op: '==', var: 'age_group', value: 'middle' }
                    ]
                  },
                  {
                    and: [
                      { op: '==', var: 'health_status', value: 'good' },
                      {
                        or: [
                          { op: '==', var: 'smoker', value: false },
                          { op: '<', var: 'years_smoking', value: 5 }
                        ]
                      }
                    ]
                  }
                ]
              },
              result: 'standard'
            }
          ],
          default: 'high_risk'
        }
      ]
    };

    // Test standard premium (young, good health, non-smoker)
    const result1 = await ruleFlow.evaluate(config, {
      policy_type: 'life',
      age_group: 'young',
      health_status: 'good',
      smoker: false,
      years_smoking: 0
    });
    expect(result1.insurance_premium).toBe('standard');

    // Test standard premium (middle-aged, good health, light smoker)
    const result2 = await ruleFlow.evaluate(config, {
      policy_type: 'life',
      age_group: 'middle',
      health_status: 'good',
      smoker: true,
      years_smoking: 3
    });
    expect(result2.insurance_premium).toBe('standard');

    // Test high risk (old age)
    const result3 = await ruleFlow.evaluate(config, {
      policy_type: 'life',
      age_group: 'senior',
      health_status: 'good',
      smoker: false,
      years_smoking: 0
    });
    expect(result3.insurance_premium).toBe('high_risk');

    // Test high risk (poor health)
    const result4 = await ruleFlow.evaluate(config, {
      policy_type: 'life',
      age_group: 'young',
      health_status: 'poor',
      smoker: false,
      years_smoking: 0
    });
    expect(result4.insurance_premium).toBe('high_risk');

    // Test high risk (heavy smoker)
    const result5 = await ruleFlow.evaluate(config, {
      policy_type: 'life',
      age_group: 'young',
      health_status: 'good',
      smoker: true,
      years_smoking: 10
    });
    expect(result5.insurance_premium).toBe('high_risk');
  });

  // Test 5: Mixed with Variable Setting
  it('should handle nested logic with variable setting', async () => {
    const config = {
      formulas: [
        {
          id: 'discount_calculation',
          switch: 'customer_tier',
          when: [
            {
              if: {
                and: [
                  { op: '>=', var: 'order_amount', value: 1000 },
                  {
                    or: [
                      { op: '==', var: 'loyalty_years', value: 5 },
                      { op: '==', var: 'is_birthday_month', value: true }
                    ]
                  }
                ]
              },
              result: 'premium_discount',
              set_vars: {
                '$discount_rate': 0.15,
                '$bonus_points': 'order_amount * 0.02'
              }
            }
          ],
          default: 'no_discount',
          set_vars: {
            '$discount_rate': 0,
            '$bonus_points': 0
          }
        }
      ]
    };

    // Test premium discount case
    const result1 = await ruleFlow.evaluate(config, {
      customer_tier: 'gold',
      order_amount: 1500,
      loyalty_years: 5,
      is_birthday_month: false
    });
    expect(result1.discount_calculation).toBe('premium_discount');
    expect(result1.discount_rate).toBe(0.15);
    expect(result1.bonus_points).toBe(30); // 1500 * 0.02 = 30

    // Test no discount case
    const result2 = await ruleFlow.evaluate(config, {
      customer_tier: 'gold',
      order_amount: 500,
      loyalty_years: 2,
      is_birthday_month: false
    });
    expect(result2.discount_calculation).toBe('no_discount');
    expect(result2.discount_rate).toBe(0);
    expect(result2.bonus_points).toBe(0);
  });

  // Test 6: Error Cases
  it('should handle errors in nested conditions', async () => {
    const config = {
      formulas: [
        {
          id: 'test',
          switch: 'type',
          when: [
            {
              if: {
                and: [
                  { op: '>', var: 'missing_var', value: 10 }
                ]
              },
              result: 'success'
            }
          ]
        }
      ]
    };

    // Missing variable should not crash, just return undefined comparison
    const result = await ruleFlow.evaluate(config, {
      type: 'test'
    });
    expect(result.test).toBeUndefined(); // no match, no default
  });
});