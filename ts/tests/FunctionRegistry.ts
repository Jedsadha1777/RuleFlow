// ts/tests/FunctionRegistry.test.ts

import { describe, it, expect, beforeEach } from 'vitest';
import { FunctionRegistry } from '../src/functions/FunctionRegistry.js';
import { RuleFlowException } from '../src/exceptions/RuleFlowException.js';

describe('Function Registry Tests', () => {
  let registry: FunctionRegistry;

  beforeEach(() => {
    registry = new FunctionRegistry();
  });

  // Test 1: Basic Math Functions
  describe('Math Functions', () => {
    it('should handle basic math operations', () => {
      expect(registry.call('abs', [-5])).toBe(5);
      expect(registry.call('min', [1, 3, 2])).toBe(1);
      expect(registry.call('max', [1, 3, 2])).toBe(3);
      expect(registry.call('sqrt', [16])).toBe(4);
    });

    it('should handle rounding functions', () => {
      expect(registry.call('round', [3.14159, 2])).toBe(3.14);
      expect(registry.call('ceil', [3.1])).toBe(4);
      expect(registry.call('floor', [3.9])).toBe(3);
    });

    it('should handle power and logarithm', () => {
      expect(registry.call('pow', [2, 3])).toBe(8);
      expect(registry.call('exp', [0])).toBe(1);
      expect(Math.abs(registry.call('log', [Math.E]) - 1)).toBeLessThan(0.001);
    });

    it('should throw errors for invalid math operations', () => {
      expect(() => registry.call('sqrt', [-1])).toThrow();
      expect(() => registry.call('log', [0])).toThrow();
      expect(() => registry.call('log', [-1])).toThrow();
    });
  });

  // Test 2: Statistics Functions
  describe('Statistics Functions', () => {
    it('should calculate averages correctly', () => {
      expect(registry.call('avg', [1, 2, 3, 4, 5])).toBe(3);
      expect(registry.call('avg', [10])).toBe(10);
      expect(registry.call('avg', [])).toBe(0);
    });

    it('should calculate sum and count', () => {
      expect(registry.call('sum', [1, 2, 3, 4, 5])).toBe(15);
      expect(registry.call('count', [1, 2, 3])).toBe(3);
      expect(registry.call('count', [])).toBe(0);
    });

    it('should calculate median correctly', () => {
      expect(registry.call('median', [1, 2, 3, 4, 5])).toBe(3);
      expect(registry.call('median', [1, 2, 3, 4])).toBe(2.5);
      expect(registry.call('median', [5])).toBe(5);
      expect(registry.call('median', [])).toBe(0);
    });

    it('should calculate variance and standard deviation', () => {
      const variance = registry.call('variance', [1, 2, 3, 4, 5]);
      expect(variance).toBe(2); // Variance of [1,2,3,4,5] is 2

      const stddev = registry.call('stddev', [1, 2, 3, 4, 5]);
      expect(Math.abs(stddev - Math.sqrt(2))).toBeLessThan(0.001);
    });
  });

  // Test 3: Business Functions
  describe('Business Functions', () => {
    it('should calculate percentages', () => {
      expect(registry.call('percentage', [25, 100])).toBe(25);
      expect(registry.call('percentage', [1, 3])).toBeCloseTo(33.333, 2);
      expect(registry.call('percentage', [0, 100])).toBe(0);
      expect(registry.call('percentage', [50, 0])).toBe(0);
    });

    it('should calculate compound interest', () => {
      const result = registry.call('compound_interest', [1000, 0.05, 2, 1]);
      expect(result).toBeCloseTo(1102.5, 1);

      // Test with zero rate
      expect(registry.call('compound_interest', [1000, 0, 2, 1])).toBe(1000);
    });

    it('should calculate simple interest', () => {
      const result = registry.call('simple_interest', [1000, 0.05, 2]);
      expect(result).toBe(1100);
    });

    it('should apply discounts and markups', () => {
      expect(registry.call('discount', [100, 0.1])).toBe(90);
      expect(registry.call('markup', [100, 0.1])).toBe(110);
    });
  });

  // Test 4: Utility Functions
  describe('Utility Functions', () => {
    it('should clamp values correctly', () => {
      expect(registry.call('clamp', [5, 1, 10])).toBe(5);
      expect(registry.call('clamp', [-5, 1, 10])).toBe(1);
      expect(registry.call('clamp', [15, 1, 10])).toBe(10);
    });

    it('should normalize values', () => {
      expect(registry.call('normalize', [5, 0, 10])).toBe(0.5);
      expect(registry.call('normalize', [0, 0, 10])).toBe(0);
      expect(registry.call('normalize', [10, 0, 10])).toBe(1);
      expect(registry.call('normalize', [5, 5, 5])).toBe(0); // Edge case
    });

    it('should handle coalesce and if_null', () => {
      expect(registry.call('coalesce', [null, undefined, 'value'])).toBe('value');
      expect(registry.call('coalesce', [null, null, null])).toBe(null);
      expect(registry.call('coalesce', [])).toBe(null);

      expect(registry.call('if_null', [null, 'default'])).toBe('default');
      expect(registry.call('if_null', ['value', 'default'])).toBe('value');
    });

    it('should calculate BMI correctly', () => {
      const bmi = registry.call('bmi', [70, 175]); // 70kg, 175cm
      expect(bmi).toBeCloseTo(22.86, 2);
      
      expect(() => registry.call('bmi', [70, 0])).toThrow();
      expect(() => registry.call('bmi', [70, -175])).toThrow();
    });

    it('should calculate age correctly', () => {
      const birthDate = new Date();
      birthDate.setFullYear(birthDate.getFullYear() - 25);
      
      const age = registry.call('age', [birthDate.toISOString()]);
      expect(age).toBe(25);
    });
  });

  // Test 5: Function Management
  describe('Function Management', () => {
    it('should register and call custom functions', () => {
      registry.register('double', (x: number) => x * 2, {
        category: 'Custom',
        description: 'Double a number'
      });

      expect(registry.call('double', [5])).toBe(10);
      expect(registry.hasFunction('double')).toBe(true);
    });

    it('should list available functions', () => {
      const functions = registry.getAvailableFunctions();
      expect(functions).toContain('abs');
      expect(functions).toContain('avg');
      expect(functions).toContain('percentage');
      expect(functions).toContain('clamp');
    });

    it('should categorize functions correctly', () => {
      const categories = registry.getFunctionsByCategory();
      
      expect(categories.Math).toContain('abs');
      expect(categories.Statistics).toContain('avg');
      expect(categories.Business).toContain('percentage');
      expect(categories.Utility).toContain('clamp');
    });

    it('should get function info', () => {
      const info = registry.getFunctionInfo('abs');
      expect(info?.name).toBe('abs');
      expect(info?.category).toBe('Math');
      expect(info?.description).toBe('Absolute value');
    });

    it('should unregister functions', () => {
      registry.register('temp', () => 'test');
      expect(registry.hasFunction('temp')).toBe(true);
      
      registry.unregister('temp');
      expect(registry.hasFunction('temp')).toBe(false);
    });

    it('should throw error for unknown functions', () => {
      expect(() => registry.call('nonexistent', [])).toThrow(RuleFlowException);
    });

    it('should handle function call errors', () => {
      registry.register('error_func', () => {
        throw new Error('Test error');
      });

      expect(() => registry.call('error_func', [])).toThrow(RuleFlowException);
    });
  });

  // Test 6: Edge Cases
  describe('Edge Cases', () => {
    it('should handle empty arguments', () => {
      expect(registry.call('sum', [])).toBe(0);
      expect(registry.call('avg', [])).toBe(0);
      expect(registry.call('count', [])).toBe(0);
    });

    it('should handle single arguments', () => {
      expect(registry.call('min', [5])).toBe(5);
      expect(registry.call('max', [5])).toBe(5);
      expect(registry.call('avg', [5])).toBe(5);
    });

    it('should handle mixed data types appropriately', () => {
      expect(registry.call('count', [1, 'string', true, null])).toBe(4);
      expect(registry.call('coalesce', [0, false, ''])).toBe(0); // 0 is not null
    });
  });
});