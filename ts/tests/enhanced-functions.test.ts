import { describe, it, expect, beforeEach } from 'vitest';
import { FunctionRegistry } from '../src/functions/FunctionRegistry';
import { RuleFlowException } from '../src/exceptions/RuleFlowException';

describe('Enhanced Built-in Functions', () => {
  let registry: FunctionRegistry;

  beforeEach(() => {
    registry = new FunctionRegistry();
  });

  // ========================================
  // Test Enhanced Math Functions
  // ========================================
  
  describe('Enhanced Math Functions', () => {
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

    // 🆕 Test trigonometric functions (new)
    it('should handle trigonometric functions', () => {
      expect(Math.abs(registry.call('sin', [Math.PI / 2]) - 1)).toBeLessThan(0.001);
      expect(Math.abs(registry.call('cos', [0]) - 1)).toBeLessThan(0.001);
      expect(Math.abs(registry.call('tan', [Math.PI / 4]) - 1)).toBeLessThan(0.001);
    });

    // 🆕 Test logarithm with base (enhanced)
    it('should handle logarithm with custom base', () => {
      expect(Math.abs(registry.call('log', [8, 2]) - 3)).toBeLessThan(0.001); // log base 2 of 8 = 3
      expect(Math.abs(registry.call('log', [100, 10]) - 2)).toBeLessThan(0.001); // log base 10 of 100 = 2
    });

    it('should throw errors for invalid math operations', () => {
      expect(() => registry.call('sqrt', [-1])).toThrow();
      expect(() => registry.call('log', [0])).toThrow();
      expect(() => registry.call('log', [-1])).toThrow();
    });
  });

  // ========================================
  // Test Statistics Functions
  // ========================================
  
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
      expect(registry.call('median', [1, 3, 5])).toBe(3);
      expect(registry.call('median', [1, 2, 3, 4])).toBe(2.5);
      expect(registry.call('median', [5])).toBe(5);
      expect(registry.call('median', [])).toBe(0);
    });

    it('should calculate variance and standard deviation', () => {
      const values = [1, 2, 3, 4, 5];
      const variance = registry.call('variance', values);
      const stddev = registry.call('stddev', values);
      
      expect(variance).toBeCloseTo(2, 1);
      expect(stddev).toBeCloseTo(Math.sqrt(2), 2);
    });
  });

  // ========================================
  // Test Enhanced Business Functions
  // ========================================
  
  describe('Enhanced Business Functions', () => {
    it('should calculate percentages correctly', () => {
      expect(registry.call('percentage', [100, 15])).toBe(15); // 15% of 100 = 15
      expect(registry.call('percentage_of', [25, 100])).toBe(25); // 25 is 25% of 100
      expect(registry.call('percentage_of', [50, 200])).toBe(25); // 50 is 25% of 200
    });

    it('should handle interest calculations', () => {
      // Simple interest: P(1 + rt)
      expect(registry.call('simple_interest', [1000, 0.05, 2])).toBe(1100);
      
      // Compound interest: P(1 + r/n)^(nt)
      const compoundResult = registry.call('compound_interest', [1000, 0.05, 2, 1]);
      expect(compoundResult).toBeCloseTo(1102.5, 1);
    });

    // 🆕 Test new business functions
    it('should apply discounts correctly', () => {
      expect(registry.call('discount', [100, 20])).toBe(80); // 20% off 100 = 80
      expect(registry.call('discount', [50, 10])).toBe(45);  // 10% off 50 = 45
    });

    it('should apply markup correctly', () => {
      expect(registry.call('markup', [100, 50])).toBe(150); // 50% markup on 100 = 150
      expect(registry.call('markup', [80, 25])).toBe(100);  // 25% markup on 80 = 100
    });

    it('should calculate loan payments (PMT)', () => {
      // $100,000 loan at 5% annual (0.004167 monthly) for 30 years (360 payments)
      const monthlyPayment = registry.call('pmt', [100000, 0.004167, 360]);
      expect(monthlyPayment).toBeCloseTo(536.82, 1); // Should be around $536.82
      
      // Zero interest loan
      expect(registry.call('pmt', [12000, 0, 12])).toBe(1000); // $12k / 12 months = $1000
    });

    it('should validate business function parameters', () => {
      expect(() => registry.call('discount', [100, -10])).toThrow('between 0 and 100');
      expect(() => registry.call('discount', [100, 110])).toThrow('between 0 and 100');
      expect(() => registry.call('markup', [100, -10])).toThrow('non-negative');
      expect(() => registry.call('pmt', [-1000, 0.05, 12])).toThrow('Invalid loan parameters');
    });
  });

  // ========================================
  // Test Enhanced Utility Functions
  // ========================================
  
  describe('Enhanced Utility Functions', () => {
    it('should handle clamp and normalize', () => {
      expect(registry.call('clamp', [15, 10, 20])).toBe(15);
      expect(registry.call('clamp', [5, 10, 20])).toBe(10);
      expect(registry.call('clamp', [25, 10, 20])).toBe(20);

      expect(registry.call('normalize', [5, 0, 10])).toBe(0.5);
      expect(registry.call('normalize', [0, 0, 10])).toBe(0);
      expect(registry.call('normalize', [10, 0, 10])).toBe(1);
    });

    it('should handle coalesce and if_null', () => {
      expect(registry.call('coalesce', [null, undefined, 'value'])).toBe('value');
      expect(registry.call('coalesce', [null, null, null])).toBe(null);
      expect(registry.call('if_null', [null, 'default'])).toBe('default');
      expect(registry.call('if_null', ['value', 'default'])).toBe('value');
    });

    // 🆕 Test new utility functions
    it('should calculate BMI correctly', () => {
      const bmi = registry.call('bmi', [70, 1.75]); // 70kg, 1.75m
      expect(bmi).toBeCloseTo(22.86, 2);
      
      expect(() => registry.call('bmi', [70, 0])).toThrow();
      expect(() => registry.call('bmi', [-70, 1.75])).toThrow();
    });

    it('should calculate age correctly', () => {
      // Test with a known date
      const birthDate = new Date();
      birthDate.setFullYear(birthDate.getFullYear() - 25);
      birthDate.setMonth(birthDate.getMonth() - 1); // Make sure it's past birthday
      
      const age = registry.call('age', [birthDate.toISOString()]);
      expect(age).toBe(25);
      
      // Test with string date
      expect(registry.call('age', ['1990-01-01'])).toBeGreaterThan(30);
      
      // Test invalid date
      expect(() => registry.call('age', ['invalid-date'])).toThrow('Invalid birth date');
    });

    it('should calculate days between dates', () => {
      const date1 = '2024-01-01';
      const date2 = '2024-01-11';
      
      expect(registry.call('days_between', [date1, date2])).toBe(10);
      expect(registry.call('days_between', [date2, date1])).toBe(10); // Should be absolute
      
      // Test with Date objects
      const d1 = new Date('2024-01-01');
      const d2 = new Date('2024-01-08');
      expect(registry.call('days_between', [d1, d2])).toBe(7);
      
      // Test invalid dates
      expect(() => registry.call('days_between', ['invalid', '2024-01-01'])).toThrow('Invalid date');
    });

    it('should detect weekends correctly', () => {
      expect(registry.call('is_weekend', ['2024-01-06'])).toBe(true);  // Saturday
      expect(registry.call('is_weekend', ['2024-01-07'])).toBe(true);  // Sunday
      expect(registry.call('is_weekend', ['2024-01-08'])).toBe(false); // Monday
      expect(registry.call('is_weekend', ['2024-01-05'])).toBe(false); // Friday
      
      // Test with Date object
      const saturday = new Date('2024-01-06');
      expect(registry.call('is_weekend', [saturday])).toBe(true);
      
      // Test invalid date
      expect(() => registry.call('is_weekend', ['invalid-date'])).toThrow('Invalid date');
    });
  });

  // ========================================
  // Test Function Registry Management
  // ========================================
  
  describe('Function Registry Management', () => {
    it('should have all required functions', () => {
      const expectedFunctions = [
        // Math
        'abs', 'min', 'max', 'sqrt', 'round', 'ceil', 'floor', 'pow', 'log', 'exp',
        'sin', 'cos', 'tan', // New trigonometric
        
        // Statistics
        'avg', 'sum', 'count', 'median', 'variance', 'stddev',
        
        // Business
        'percentage', 'percentage_of', 'compound_interest', 'simple_interest',
        'discount', 'markup', 'pmt', // New business functions
        
        // Utility
        'clamp', 'normalize', 'coalesce', 'if_null',
        'bmi', 'age', 'days_between', 'is_weekend' // New utility functions
      ];

      const availableFunctions = registry.getAvailableFunctions();
      expectedFunctions.forEach(func => {
        expect(availableFunctions).toContain(func);
      });
      
      // Should have at least 30 functions now (vs 25+ before)
      expect(availableFunctions.length).toBeGreaterThanOrEqual(30);
    });

    it('should categorize functions correctly', () => {
      const categories = registry.getFunctionsByCategory();
      
      // Math category should have trigonometric functions
      expect(categories.Math).toContain('sin');
      expect(categories.Math).toContain('cos');
      expect(categories.Math).toContain('tan');
      expect(categories.Math.length).toBeGreaterThanOrEqual(13); // 10 original + 3 trig
      
      // Business category should have new functions
      expect(categories.Business).toContain('discount');
      expect(categories.Business).toContain('markup');
      expect(categories.Business).toContain('pmt');
      expect(categories.Business.length).toBeGreaterThanOrEqual(7); // 4 original + 3 new
      
      // Utility category should have new functions
      expect(categories.Utility).toContain('bmi');
      expect(categories.Utility).toContain('age');
      expect(categories.Utility).toContain('days_between');
      expect(categories.Utility).toContain('is_weekend');
      expect(categories.Utility.length).toBeGreaterThanOrEqual(8); // 4 original + 4 new
    });

    it('should provide function information', () => {
      // Test new trigonometric function info
      const sinInfo = registry.getFunctionInfo('sin');
      expect(sinInfo?.name).toBe('sin');
      expect(sinInfo?.category).toBe('Math');
      expect(sinInfo?.description).toBe('Sine function');
      
      // Test new business function info
      const pmtInfo = registry.getFunctionInfo('pmt');
      expect(pmtInfo?.name).toBe('pmt');
      expect(pmtInfo?.category).toBe('Business');
      expect(pmtInfo?.description).toBe('Calculate loan payment amount');
      
      // Test new utility function info
      const bmiInfo = registry.getFunctionInfo('bmi');
      expect(bmiInfo?.name).toBe('bmi');
      expect(bmiInfo?.category).toBe('Utility');
      expect(bmiInfo?.description).toBe('Calculate BMI');
    });

    it('should handle function registration and unregistration', () => {
      // Register a custom function
      registry.register('custom_test', (x: number) => x * 2, {
        category: 'Custom',
        description: 'Test function'
      });
      
      expect(registry.hasFunction('custom_test')).toBe(true);
      expect(registry.call('custom_test', [5])).toBe(10);
      
      // Unregister the function
      expect(registry.unregister('custom_test')).toBe(true);
      expect(registry.hasFunction('custom_test')).toBe(false);
    });
  });

  // ========================================
  // Test Integration with Expressions
  // ========================================
  
  describe('Function Integration', () => {
    it('should work in complex calculations', () => {
      // Test combining multiple new functions
      const principal = 50000;
      const annualRate = 0.06;
      const monthlyRate = annualRate / 12;
      const years = 15;
      const payments = years * 12;
      
      const monthlyPayment = registry.call('pmt', [principal, monthlyRate, payments]);
      expect(monthlyPayment).toBeCloseTo(421.93, 1);
      
      // Apply discount to the payment
      const discountedPayment = registry.call('discount', [monthlyPayment, 10]);
      expect(discountedPayment).toBeCloseTo(379.74, 1);
    });

    it('should handle date-based business logic', () => {
      const today = new Date();
      const weekend = registry.call('is_weekend', [today]);
      
      if (weekend) {
        expect(typeof weekend).toBe('boolean');
        expect(weekend).toBe(true);
      } else {
        expect(weekend).toBe(false);
      }
      
      // Calculate age and determine discount
      const birthDate = new Date();
      birthDate.setFullYear(birthDate.getFullYear() - 66);
      const age = registry.call('age', [birthDate]);
      expect(age).toBeGreaterThanOrEqual(65);
      
      // Senior citizen discount
      const basePrice = 100;
      const seniorDiscount = age >= 65 ? 15 : 0;
      const discountedPrice = registry.call('discount', [basePrice, seniorDiscount]);
      expect(discountedPrice).toBe(85);
    });

    it('should validate all parameter combinations', () => {
      // Test edge cases for new functions
      
      // BMI edge cases
      expect(() => registry.call('bmi', [0, 1.75])).toThrow();
      expect(() => registry.call('bmi', [70, 0])).toThrow();
      
      // Age edge cases  
      expect(() => registry.call('age', [''])).toThrow();
      expect(() => registry.call('age', ['not-a-date'])).toThrow();
      
      // Days between edge cases
      expect(() => registry.call('days_between', ['2024-01-01', 'invalid'])).toThrow();
      
      // Weekend edge cases
      expect(() => registry.call('is_weekend', ['invalid-date'])).toThrow();
      
      // PMT edge cases
      expect(() => registry.call('pmt', [0, 0.05, 12])).toThrow();
      expect(() => registry.call('pmt', [1000, -0.05, 12])).toThrow();
      expect(() => registry.call('pmt', [1000, 0.05, 0])).toThrow();
    });
  });

  // ========================================
  // Test Performance & Compatibility
  // ========================================
  
  describe('Performance & Compatibility', () => {
    it('should maintain performance with increased function count', () => {
      const iterations = 1000;
      const start = performance.now();
      
      for (let i = 0; i < iterations; i++) {
        registry.call('sin', [Math.PI / 4]);
        registry.call('discount', [100, 15]);
        registry.call('bmi', [70, 1.75]);
        registry.call('pmt', [1000, 0.05, 12]);
      }
      
      const duration = performance.now() - start;
      expect(duration).toBeLessThan(100); // Should complete in under 100ms
    });

    it('should maintain backward compatibility', () => {
      // All original functions should still work exactly the same
      expect(registry.call('abs', [-5])).toBe(5);
      expect(registry.call('avg', [1, 2, 3])).toBe(2);
      expect(registry.call('percentage', [100, 10])).toBe(10);
      expect(registry.call('clamp', [15, 10, 20])).toBe(15);
    });
  });

  // ========================================
  // Test Real-world Scenarios
  // ========================================
  
  describe('Real-world Scenarios', () => {
    it('should handle hotel pricing scenario', () => {
      const basePrice = 100;
      const isWeekendStay = registry.call('is_weekend', ['2024-01-06']); // Saturday
      
      // Weekend markup
      const weekendPrice = isWeekendStay 
        ? registry.call('markup', [basePrice, 50]) // 50% weekend markup
        : basePrice;
      
      // Member discount
      const memberDiscount = 15;
      const finalPrice = registry.call('discount', [weekendPrice, memberDiscount]);
      
      if (isWeekendStay) {
        expect(weekendPrice).toBe(150); // 100 + 50%
        expect(finalPrice).toBe(127.5); // 150 - 15%
      }
    });

    it('should handle loan qualification scenario', () => {
      const loanAmount = 300000;
      const annualRate = 0.045; // 4.5%
      const monthlyRate = annualRate / 12;
      const years = 30;
      const payments = years * 12;
      
      // Calculate monthly payment
      const monthlyPayment = registry.call('pmt', [loanAmount, monthlyRate, payments]);
      expect(monthlyPayment).toBeCloseTo(1520.06, 1);
      
      // Calculate total interest paid
      const totalPaid = monthlyPayment * payments;
      const totalInterest = totalPaid - loanAmount;
      expect(totalInterest).toBeGreaterThan(200000); // Significant interest over 30 years
    });

    it('should handle health assessment scenario', () => {
      const weight = 75; // kg
      const height = 1.75; // meters
      const birthDate = '1975-06-15';
      
      const bmi = registry.call('bmi', [weight, height]);
      const age = registry.call('age', [birthDate]);
      
      expect(bmi).toBeCloseTo(24.49, 1);
      expect(age).toBeGreaterThan(35);
      
      // Health risk assessment
      let riskFactor = 0;
      if (bmi > 30) riskFactor += 2;
      else if (bmi > 25) riskFactor += 1;
      
      if (age > 50) riskFactor += 2;
      else if (age > 40) riskFactor += 1;
      
      expect(riskFactor).toBeGreaterThanOrEqual(1); // Some risk due to age
    });
  });
});