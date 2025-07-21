import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow } from '../src/RuleFlow';
import { RuleFlowConfig } from '../src/types';

describe('Simple $ Notation Tests', () => {
  let ruleFlow: RuleFlow;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
  });

  it('should handle $ in formula expression', async () => {
    const config: RuleFlowConfig = {
      formulas: [
        {
          id: 'step1',
          formula: 'base * 2',
          inputs: ['base'],
          as: '$temp'
        },
        {
          id: 'step2',
          formula: '$temp + 10',
          inputs: ['$temp']
        }
      ]
    };

    const result = await ruleFlow.evaluate(config, { base: 5 });
    expect(result.temp).toBe(10);      // 5 * 2
    expect(result.step2).toBe(20);     // 10 + 10
  });

  it('should handle $ in switch value', async () => {
    const config: RuleFlowConfig = {
      formulas: [
        {
          id: 'category',
          formula: 'score + 10',
          inputs: ['score'],
          as: '$cat'
        },
        {
          id: 'rating',
          switch: '$cat',
          when: [
            { if: { op: '>=', value: 90 }, result: 'A' },
            { if: { op: '>=', value: 80 }, result: 'B' }
          ],
          default: 'C'
        }
      ]
    };

    const result = await ruleFlow.evaluate(config, { score: 85 });
    expect(result.cat).toBe(95);       // 85 + 10
    expect(result.rating).toBe('A');   // 95 >= 90
  });

  it('should handle $ in as field', async () => {
    const config: RuleFlowConfig = {
      formulas: [
        {
          id: 'calc1',
          formula: 'a + b',
          inputs: ['a', 'b'],
          as: '$result1'
        },
        {
          id: 'calc2',
          formula: 'c * d',
          inputs: ['c', 'd'],
          as: '$result2'
        },
        {
          id: 'final',
          formula: '$result1 + $result2',
          inputs: ['$result1', '$result2']
        }
      ]
    };

    const result = await ruleFlow.evaluate(config, { a: 1, b: 2, c: 3, d: 4 });
    expect(result.result1).toBe(3);    // 1 + 2
    expect(result.result2).toBe(12);   // 3 * 4
    expect(result.final).toBe(15);     // 3 + 12
  });

  it('should work with nested logic and $ notation', async () => {
    const config = {
      formulas: [
        {
          id: 'base_score',
          formula: 'performance * 0.6 + goals * 0.4',
          inputs: ['performance', 'goals'],
          as: '$base_score'
        },
        {
          id: 'bonus_check',
          switch: 'department',
          when: [
            {
              if: {
                and: [
                  { op: '==', value: 'sales' },
                  { op: '>', var: '$base_score', value: 80 }
                ]
              },
              result: 'eligible',
              set_vars: { '$bonus_multiplier': 1.5 }
            }
          ],
          default: 'not_eligible',
          set_vars: { '$bonus_multiplier': 1.0 }
        },
        {
          id: 'final_score',
          formula: '$base_score * $bonus_multiplier',
          inputs: ['$base_score', '$bonus_multiplier'],
          as: '$final_score'
        }
      ]
    };

    const result = await ruleFlow.evaluate(config, {
      performance: 90,
      goals: 85,
      department: 'sales'
    });

    expect(result.base_score).toBe(88); // 90*0.6 + 85*0.4
    expect(result.bonus_check).toBe('eligible');
    expect(result.bonus_multiplier).toBe(1.5);
    expect(result.final_score).toBe(132); // 88 * 1.5
  });
});