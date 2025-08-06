import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow } from '../src/RuleFlow.js';

describe('Complex Formula Tests', () => {
  let ruleFlow: RuleFlow;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
  });

  it('should calculate complex formula correctly', async () => {
    const config = {
      formulas: [
        {
          id: 'complex_result',
          formula: 'max(0, min(100, (i+3)*((((m_2*1)+(m_1*2)+(m_0*3))-(((m_2+m_1+m_0)/3)*(6)))/2) + (((m_2+m_1+m_0)/3)-((((m_2*1)+(m_1*2)+(m_0*3))-(((m_2+m_1+m_0)/3)*(6)))/2)*2)))',
          inputs: ['i', 'm_2', 'm_1', 'm_0']
        }
      ]
    };

    const result = await ruleFlow.evaluate(config, { i: 5, m_2: 10, m_1: 20, m_0: 30 });
    expect(result.complex_result).toBe(80);
  });

  it('should handle different test cases', async () => {
    const config = {
      formulas: [
        {
          id: 'test_result',
          formula: 'max(0, min(100, (i+3)*((((m_2*1)+(m_1*2)+(m_0*3))-(((m_2+m_1+m_0)/3)*(6)))/2) + (((m_2+m_1+m_0)/3)-((((m_2*1)+(m_1*2)+(m_0*3))-(((m_2+m_1+m_0)/3)*(6)))/2)*2)))',
          inputs: ['i', 'm_2', 'm_1', 'm_0']
        }
      ]
    };

    // ค่าเท่ากัน
    const result1 = await ruleFlow.evaluate(config, { i: 1, m_2: 5, m_1: 5, m_0: 5 });
    expect(result1.test_result).toBe(5);

    // ค่าสูง
    const result2 = await ruleFlow.evaluate(config, { i: 10, m_2: 50, m_1: 60, m_0: 70 });
    expect(result2.test_result).toBe(100);

    // ค่าศูนย์
    const result3 = await ruleFlow.evaluate(config, { i: 0, m_2: 0, m_1: 0, m_0: 0 });
    expect(result3.test_result).toBe(0);
  });

  it('should handle step by step calculation', async () => {
    const config = {
      formulas: [
        {
          id: 'avg',
          formula: '(m_2 + m_1 + m_0) / 3',
          inputs: ['m_2', 'm_1', 'm_0']
        },
        {
          id: 'final',
          formula: 'max(0, min(100, (i + 3) + avg))',
          inputs: ['i', 'avg']
        }
      ]
    };

    const result = await ruleFlow.evaluate(config, { i: 5, m_2: 10, m_1: 20, m_0: 30 });
    expect(result.avg).toBe(20);
    expect(result.final).toBe(28);
  });
});