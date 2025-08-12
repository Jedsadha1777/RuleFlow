// tests/SetVarsScoring.test.ts

import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow } from '../src/RuleFlow';

describe('Advanced Scoring with set_vars Tests', () => {
  let ruleFlow: RuleFlow;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
  });

  // Test 1: Basic set_vars functionality
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
      scorvar1: 'category',     // contains 'cat' ✅
      scorevar2: 'doghouse'     // starts_with 'dog' ✅
    });

    console.log('Full Result:', JSON.stringify(result, null, 2));

    // Check main scoring result
    expect(result.scoring_1755022061219).toBeDefined();
    expect(result.scoring_1755022061219.score).toBe(100);
    expect(result.scoring_1755022061219.field1).toBe('field1000');
    expect(result.scoring_1755022061219.field2).toBe('field2000');

    // Check set_vars were applied to context
    expect(result.setvar1).toBe(100);
    expect(result.setvar2).toBe(200);
    expect(result.setvar3).toBe(300);

    // Check stored as variable
    expect(result.storeas).toEqual(result.scoring_1755022061219);
  });

  // Test 2: set_vars with complex expressions
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
                    },
                    {
                      if: { op: '>=', value: 25 },
                      score: 70,
                      set_vars: {
                        bonus_eligible: true,
                        bonus_amount: '$income * 0.05',
                        category: 'standard',
                        risk_level: 'medium'
                      },
                      tier: 'silver'
                    }
                  ]
                },
                {
                  if: { op: '>=', value: 30000 },
                  ranges: [
                    {
                      if: { op: '>=', value: 35 },
                      score: 60,
                      set_vars: {
                        bonus_eligible: false,
                        category: 'standard',
                        risk_level: 'medium'
                      },
                      tier: 'bronze'
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

    // Check scoring result
    expect(result.complex_scoring.score).toBe(85);
    expect(result.complex_scoring.tier).toBe('gold');
    expect(result.complex_scoring.status).toBe('approved');

    // Check set_vars
    expect(result.bonus_eligible).toBe(true);
    expect(result.bonus_amount).toBe('$income * 0.1');
    expect(result.category).toBe('premium');
    expect(result.risk_level).toBe('low');

    // Check as variable
    expect(result.customer_profile).toEqual(result.complex_scoring);
  });

  // Test 3: set_vars with nested conditions
  it('should handle set_vars in nested scoring scenarios', async () => {
    const config = {
      formulas: [
        {
          id: 'loan_approval',
          scoring: {
            ifs: {
              vars: ['credit_score', 'debt_ratio'],
              tree: [
                {
                  if: { op: '>=', value: 750 }, // Excellent credit
                  ranges: [
                    {
                      if: { op: '<=', value: 0.3 }, // Low debt ratio
                      score: 100,
                      set_vars: {
                        approval_status: 'instant_approval',
                        interest_rate: 3.5,
                        max_amount: 500000,
                        processing_time: '24_hours',
                        requires_verification: false
                      },
                      decision: 'APPROVED',
                      risk_category: 'minimal'
                    },
                    {
                      if: { op: '<=', value: 0.5 }, // Medium debt ratio
                      score: 85,
                      set_vars: {
                        approval_status: 'conditional_approval',
                        interest_rate: 4.2,
                        max_amount: 300000,
                        processing_time: '48_hours',
                        requires_verification: true
                      },
                      decision: 'CONDITIONAL',
                      risk_category: 'low'
                    }
                  ]
                },
                {
                  if: { op: '>=', value: 650 }, // Good credit
                  ranges: [
                    {
                      if: { op: '<=', value: 0.4 },
                      score: 70,
                      set_vars: {
                        approval_status: 'manual_review',
                        interest_rate: 5.5,
                        max_amount: 200000,
                        processing_time: '72_hours',
                        requires_verification: true,
                        additional_docs_required: true
                      },
                      decision: 'REVIEW_REQUIRED',
                      risk_category: 'medium'
                    }
                  ]
                }
              ]
            }
          }
        }
      ]
    };

    // Test case 1: Excellent credit, low debt
    const excellentResult = await ruleFlow.evaluate(config, {
      credit_score: 780,
      debt_ratio: 0.25
    });

    expect(excellentResult.results.loan_approval.score).toBe(100);
    expect(excellentResult.results.loan_approval.decision).toBe('APPROVED');
    expect(excellentResult.results.approval_status).toBe('instant_approval');
    expect(excellentResult.results.interest_rate).toBe(3.5);
    expect(excellentResult.results.max_amount).toBe(500000);
    expect(excellentResult.results.requires_verification).toBe(false);

    // Test case 2: Good credit, medium debt
    const goodResult = await ruleFlow.evaluate(config, {
      credit_score: 680,
      debt_ratio: 0.35
    });

    expect(goodResult.results.loan_approval.score).toBe(70);
    expect(goodResult.results.loan_approval.decision).toBe('REVIEW_REQUIRED');
    expect(goodResult.results.approval_status).toBe('manual_review');
    expect(goodResult.results.interest_rate).toBe(5.5);
    expect(goodResult.results.additional_docs_required).toBe(true);

    console.log('Excellent Credit Result:', JSON.stringify(excellentResult.results, null, 2));
    console.log('Good Credit Result:', JSON.stringify(goodResult.results, null, 2));
  });

  // Test 4: Multiple set_vars overwriting
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
                  ranges: [
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
                  ranges: [
                    {
                      if: { op: '>=', value: 60 },
                      score: 40,
                      set_vars: {
                        shared_var: 'from_second', // Should overwrite
                        unique_var2: 'second_only'
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

    const result = await ruleFlow.evaluate(config, {
      value1: 55,
      value2: 65
    });

    expect(result.results.first_scoring.score).toBe(30);
    expect(result.results.second_scoring.score).toBe(40);

    // Check variable overwriting
    expect(result.results.shared_var).toBe('from_second'); // Latest wins
    expect(result.results.unique_var1).toBe('first_only');
    expect(result.results.unique_var2).toBe('second_only');

    console.log('Multiple set_vars Result:', JSON.stringify(result.results, null, 2));
  });
});