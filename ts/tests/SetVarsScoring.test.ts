// tests/SetVarsScoring.test.ts - แก้ไขให้ครบ

import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow } from '../src/RuleFlow';

describe('Advanced Scoring with set_vars Tests', () => {
  let ruleFlow: RuleFlow;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
  });

  // Test 1: แก้ไขให้ตรงกับ actual output
  it('should handle set_vars in scoring ranges', async () => {
    const config = {
      formulas: [
        {
          id: 'scoring_1755022061219',
          scoring: {
            ifs: {
              vars: ['scorvar1', 'scorevar2'],
              tree: [
                {
                  if: { op: 'contains', value: 'cat' },
                  ranges: [
                    {
                      if: { op: 'starts_with', value: 'dog' },
                      score: 100,
                      set_vars: {
                        setvar1: 100,
                        setvar2: 200,
                        setvar3: 300
                      },
                      field1: 'field1000',
                      field2: 'field2000'
                    }
                  ]
                }
              ]
            }
          },
          as: 'storeas'
        }
      ]
    };

    const result = await ruleFlow.evaluate(config, {
      scorvar1: 'category',
      scorevar2: 'doghouse'
    });

    console.log('Full Result:', JSON.stringify(result, null, 2));

    // ✅ FIX: ใช้ metadata pattern
    expect(result.scoring_1755022061219).toBe(100);  // score value
    expect(result.scoring_1755022061219_field1).toBe('field1000');  // metadata
    expect(result.scoring_1755022061219_field2).toBe('field2000');  // metadata

    // Check set_vars were applied to context
    expect(result.setvar1).toBe(100);
    expect(result.setvar2).toBe(200);
    expect(result.setvar3).toBe(300);

    // Check 'as' variable gets score value
    expect(result.storeas).toBe(100);
    expect(result.storeas_field1).toBe('field1000');
    expect(result.storeas_field2).toBe('field2000');
  });

  // Test 2: แก้ให้ตรงกับ actual structure
  it('should handle set_vars with calculated values', async () => {
    const config = {
      formulas: [
        {
          id: 'complex_scoring',
          scoring: {
            ifs: {
              vars: ['income', 'age'],
              tree: [
                {
                  if: { op: '>=', value: 50000 },
                  ranges: [
                    {
                      if: { op: '>=', value: 30 },
                      score: 85,
                      set_vars: {
                        bonus_eligible: true,
                        bonus_amount: '$income * 0.1',
                        category: 'premium',
                        risk_level: 'low'
                      },
                      tier: 'gold',
                      status: 'approved'
                    }
                  ]
                }
              ]
            }
          },
          as: 'customer_profile'
        }
      ]
    };

    const result = await ruleFlow.evaluate(config, {
      income: 60000,
      age: 32
    });

    console.log('Complex Result:', JSON.stringify(result, null, 2));

    // ✅ FIX: ใช้ metadata pattern
    expect(result.complex_scoring).toBe(85);  // score value
    expect(result.complex_scoring_tier).toBe('gold');  // metadata
    expect(result.complex_scoring_status).toBe('approved');  // metadata

    // Check set_vars
    expect(result.bonus_eligible).toBe(true);
    expect(result.bonus_amount).toBe(6000);  // ✅ FIX: should be evaluated
    expect(result.category).toBe('premium');
    expect(result.risk_level).toBe('low');

    // Check 'as' variable
    expect(result.customer_profile).toBe(85);  // score value
    expect(result.customer_profile_tier).toBe('gold');
    expect(result.customer_profile_status).toBe('approved');
  });

  // Test 3: แก้ให้ตรงกับ actual structure
  it('should handle set_vars in nested scoring scenarios', async () => {
    const config = {
      formulas: [
        {
          id: 'loan_approval',
          scoring: {
            ifs: {
              vars: ['credit_score', 'income'],
              tree: [
                {
                  if: { op: '>=', value: 750 },
                  ranges: [
                    {
                      if: { op: '>=', value: 80000 },
                      score: 100,
                      decision: 'APPROVED',
                      set_vars: {
                        approval_status: 'instant_approval'
                      }
                    }
                  ]
                }
              ]
            }
          }
        }
      ]
    };

    const excellentResult = await ruleFlow.evaluate(config, {
      credit_score: 780,
      income: 100000
    });

    console.log('Excellent Credit Result:', JSON.stringify(excellentResult, null, 2));

    // ✅ FIX: ใช้ metadata pattern
    expect(excellentResult.loan_approval).toBe(100);  // score value
    expect(excellentResult.loan_approval_decision).toBe('APPROVED');  // metadata
    expect(excellentResult.approval_status).toBe('instant_approval');  // set_vars
  });

  // Test 4: แก้ให้ตรงกับ actual structure
  it('should handle multiple set_vars properly', async () => {
    const config = {
      formulas: [
        {
          id: 'first_scoring',
          scoring: {
            ifs: {
              vars: ['value1'],
              tree: [
                {
                  if: { op: '>=', value: 50 },
                  score: 30,
                  set_vars: {
                    shared_var: 'from_first',
                    unique_var1: 'first_only'
                  }
                }
              ]
            }
          }
        },
        {
          id: 'second_scoring',
          scoring: {
            ifs: {
              vars: ['value2'],
              tree: [
                {
                  if: { op: '>=', value: 60 },
                  score: 40,
                  set_vars: {
                    shared_var: 'from_second',
                    unique_var2: 'second_only'
                  }
                }
              ]
            }
          }
        }
      ]
    };

    const result = await ruleFlow.evaluate(config, {
      value1: 55,
      value2: 65
    });

    // ✅ FIX: ใช้ metadata pattern
    expect(result.first_scoring).toBe(30);   // score value
    expect(result.second_scoring).toBe(40);  // score value

    // Check set_vars
    expect(result.shared_var).toBe('from_second');
    expect(result.unique_var1).toBe('first_only');
    expect(result.unique_var2).toBe('second_only');

    console.log('Multiple set_vars Result:', JSON.stringify(result, null, 2));
  });
});