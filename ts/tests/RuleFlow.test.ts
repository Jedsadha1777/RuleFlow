
import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow, RuleFlowException } from '../src/index.js';

describe('RuleFlow Core Tests', () => {
  let ruleFlow: RuleFlow;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
  });

  // Test 1: Basic Math
  it('should calculate simple multiplication', async () => {
    const config = {
      formulas: [
        {
          id: 'total',
          formula: 'price * quantity',
          inputs: ['price', 'quantity']
        }
      ]
    };

    const inputs = { price: 100, quantity: 2 };
    const result = await ruleFlow.evaluate(config, inputs);

    expect(result.total).toBe(200);
    expect(result.price).toBe(100);
    expect(result.quantity).toBe(2);
  });

  // Test 2: Switch Logic
  it('should handle grading system', async () => {
    const config = {
      formulas: [
        {
          id: 'grade',
          switch: 'score',
          when: [
            { if: { op: '>=', value: 90 }, result: 'A' },
            { if: { op: '>=', value: 80 }, result: 'B' },
            { if: { op: '>=', value: 70 }, result: 'C' }
          ],
          default: 'F'
        }
      ]
    };

    // Test excellent score
    const result1 = await ruleFlow.evaluate(config, { score: 95 });
    expect(result1.grade).toBe('A');

    // Test good score
    const result2 = await ruleFlow.evaluate(config, { score: 85 });
    expect(result2.grade).toBe('B');

    // Test failing score
    const result3 = await ruleFlow.evaluate(config, { score: 65 });
    expect(result3.grade).toBe('F');
  });

  // Test 3: Multiple Formulas
  it('should handle multiple formulas', async () => {
    const config = {
      formulas: [
        {
          id: 'subtotal',
          formula: 'price * quantity',
          inputs: ['price', 'quantity']
        },
        {
          id: 'total',
          formula: 'subtotal + tax',
          inputs: ['subtotal', 'tax']
        }
      ]
    };

    const inputs = { price: 100, quantity: 2, tax: 20 };
    const result = await ruleFlow.evaluate(config, inputs);

    expect(result.subtotal).toBe(200);
    expect(result.total).toBe(220);
  });

  // Test 4: Error Handling
  it('should throw error for missing inputs', async () => {
    const config = {
      formulas: [
        {
          id: 'total',
          formula: 'price * quantity',
          inputs: ['price', 'quantity']
        }
      ]
    };

    const inputs = { price: 100 }; // missing quantity

    await expect(ruleFlow.evaluate(config, inputs))
      .rejects
      .toThrow(RuleFlowException);
  });

  // Test 5: System Info
  it('should return system info', () => {
    const info = ruleFlow.getSystemInfo();
    expect(info.version).toBe('1.0.0-typescript');
    expect(info.engine).toBe('TypeScript');
  });
});