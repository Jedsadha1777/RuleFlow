import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow } from '../src/RuleFlow';

describe('Simple Set Vars Tests', () => {
  let ruleFlow: RuleFlow;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
  });

  // Test 1: Basic scoring with set_vars (skip contains for now)
  it('should handle basic scoring with set_vars', async () => {
    const config = {
      formulas: [
        {
          id: 'scoring_test',
          scoring: {
            ifs: {
              vars: ['scorvar1', 'scorevar2'],
              tree: [
                {
                  if: { op: '>=', value: 5 }, // Change to simple numeric test
                  ranges: [
                    {
                      if: { op: '>=', value: 100 }, // Change to simple numeric test
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
      scorvar1: 10,     // >= 5 ✅
      scorevar2: 150    // >= 100 ✅
    });

    console.log('=== Basic Test Results ===');
    console.log('Full Results:', JSON.stringify(result, null, 2));

    // Check that we got results
    expect(result).toBeDefined();

    // ✅ แก้ไข: result ไม่มี .results property
    // Check main scoring result
    expect(result.storeas).toBe(100);   // score value
    expect(result.storeas_field1).toBe('field1000'); // metadata แยกออกมา  
    expect(result.storeas_field2).toBe('field2000');   // metadata แยกออกมา

    // Check set_vars were applied to context
    expect(result.setvar1).toBe(100);
    expect(result.setvar2).toBe(200);  
    expect(result.setvar3).toBe(300);

   
  });

  // Test 2: Simple accumulative rules with set_vars
  it('should handle accumulative rules with set_vars', async () => {
    const config = {
      formulas: [
        {
          id: 'accumulative_test',
          rules: [
            {
              var: 'income',
              ranges: [
                {
                  if: { op: '>=', value: 50000 },
                  score: 50,
                  set_vars: {
                    income_tier: 'high',
                    bonus_eligible: true
                  }
                },
                {
                  if: { op: '>=', value: 30000 },
                  score: 30,
                  set_vars: {
                    income_tier: 'medium',
                    bonus_eligible: false
                  }
                }
              ]
            },
            {
              var: 'age',
              ranges: [
                {
                  if: { op: '>=', value: 30 },
                  score: 25,
                  set_vars: {
                    age_group: 'senior'
                  }
                },
                {
                  if: { op: '>=', value: 18 },
                  score: 15,
                  set_vars: {
                    age_group: 'adult'
                  }
                }
              ]
            }
          ]
        }
      ]
    };

    const result = await ruleFlow.evaluate(config, {
      income: 60000,  // Should get 50 points + high tier
      age: 35         // Should get 25 points + senior group
    });

    console.log('=== Accumulative Test Results ===');
    console.log('Full Results:', JSON.stringify(result, null, 2));

    // Check total score
    expect(result.accumulative_test).toBe(75); // 50 + 25

    // Check set_vars from income range
    expect(result.income_tier).toBe('high');
    expect(result.bonus_eligible).toBe(true);

    // Check set_vars from age range
    expect(result.age_group).toBe('senior');
  });

  // Test 3: Simple multi-dimensional with arrays
  it('should handle complex nested scenarios', async () => {
    const config = {
      formulas: [
        {
          id: 'complex_scoring',
          scoring: {
            ifs: {
              vars: ['level', 'points'],
              tree: [
                {
                  if: { op: '>=', value: 5 },
                  ranges: [
                    {
                      if: { op: '>=', value: 1000 },
                      score: 200,
                      set_vars: {
                        membership: 'platinum',
                        perks: ['lounge_access', 'priority_boarding'],
                        discount_rate: 0.2
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
      level: 6,
      points: 1500
    });

    console.log('=== Complex Test Results ===');
    console.log('Full Results:', JSON.stringify(result, null, 2));

    // Check scoring result
    expect(result.complex_scoring).toBe(200);

    // Check set_vars
    expect(result.membership).toBe('platinum');
    expect(result.perks).toEqual(['lounge_access', 'priority_boarding']);
    expect(result.discount_rate).toBe(0.2);
  });
});