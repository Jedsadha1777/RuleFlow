// tests/enhanced-operators.test.ts

import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow } from '../src/RuleFlow.js';

describe('Enhanced Switch Operators', () => {
  let ruleFlow: RuleFlow;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
  });

  // ========================================
  // Test Between Operator
  // ========================================
  
  describe('Between Operator', () => {
    it('should work with numeric ranges', async () => {
      const config = {
        formulas: [
          {
            id: 'age_category',
            switch: '$age',
            when: [
              { if: { op: 'between', value: [13, 19] }, result: 'teenager' },
              { if: { op: 'between', value: [20, 64] }, result: 'adult' },
              { if: { op: 'between', value: [65, 120] }, result: 'senior' }
            ],
            default: 'child'
          }
        ]
      };

      // Test teenager range
      let result = await ruleFlow.evaluate(config, { age: 16 });
      expect(result.age_category).toBe('teenager');

      // Test adult range
      result = await ruleFlow.evaluate(config, { age: 35 });
      expect(result.age_category).toBe('adult');

      // Test senior range  
      result = await ruleFlow.evaluate(config, { age: 70 });
      expect(result.age_category).toBe('senior');

      // Test child (default)
      result = await ruleFlow.evaluate(config, { age: 8 });
      expect(result.age_category).toBe('child');

      // Test boundary values
      result = await ruleFlow.evaluate(config, { age: 13 }); // Min teenager
      expect(result.age_category).toBe('teenager');

      result = await ruleFlow.evaluate(config, { age: 19 }); // Max teenager
      expect(result.age_category).toBe('teenager');
    });

    it('should handle decimal ranges', async () => {
      const config = {
        formulas: [
          {
            id: 'grade_category',
            switch: '$score',
            when: [
              { if: { op: 'between', value: [3.5, 4.0] }, result: 'excellent' },
              { if: { op: 'between', value: [2.5, 3.49] }, result: 'good' }
            ],
            default: 'needs_improvement'
          }
        ]
      };

      let result = await ruleFlow.evaluate(config, { score: 3.8 });
      expect(result.grade_category).toBe('excellent');

      result = await ruleFlow.evaluate(config, { score: 3.0 });
      expect(result.grade_category).toBe('good');
    });
  });

  // ========================================
  // Test In/Not In Operators
  // ========================================

  describe('In/Not In Operators', () => {
    it('should work with string arrays', async () => {
      const config = {
        formulas: [
          {
            id: 'access_level',
            switch: '$role',
            when: [
              { if: { op: 'in', value: ['admin', 'superuser'] }, result: 'full_access' },
              { if: { op: 'in', value: ['manager', 'lead'] }, result: 'limited_access' },
              { if: { op: 'not_in', value: ['guest', 'anonymous'] }, result: 'authenticated' }
            ],
            default: 'no_access'
          }
        ]
      };

      // Test admin access
      let result = await ruleFlow.evaluate(config, { role: 'admin' });
      expect(result.access_level).toBe('full_access');

      // Test manager access
      result = await ruleFlow.evaluate(config, { role: 'manager' });
      expect(result.access_level).toBe('limited_access');

      // Test regular user (not_in guest/anonymous)
      result = await ruleFlow.evaluate(config, { role: 'user' });
      expect(result.access_level).toBe('authenticated');

      // Test guest (no access)
      result = await ruleFlow.evaluate(config, { role: 'guest' });
      expect(result.access_level).toBe('no_access');
    });

    it('should work with numeric arrays', async () => {
      const config = {
        formulas: [
          {
            id: 'prize_category',
            switch: '$ticket_number',
            when: [
              { if: { op: 'in', value: [1, 100, 1000] }, result: 'grand_prize' },
              { if: { op: 'in', value: [10, 50, 500] }, result: 'second_prize' },
              { if: { op: 'not_in', value: [13, 666, 999] }, result: 'consolation' }
            ],
            default: 'unlucky'
          }
        ]
      };

      let result = await ruleFlow.evaluate(config, { ticket_number: 1 });
      expect(result.prize_category).toBe('grand_prize');

      result = await ruleFlow.evaluate(config, { ticket_number: 50 });
      expect(result.prize_category).toBe('second_prize');

      result = await ruleFlow.evaluate(config, { ticket_number: 666 });
      expect(result.prize_category).toBe('unlucky');
    });
  });

  // ========================================
  // Test String Operators
  // ========================================

  describe('String Operators', () => {
    it('should work with contains operator', async () => {
      const config = {
        formulas: [
          {
            id: 'email_category',
            switch: '$email',
            when: [
              { if: { op: 'contains', value: '@company.com' }, result: 'internal' },
              { if: { op: 'contains', value: '@gmail.com' }, result: 'personal' },
              { if: { op: 'contains', value: '+' }, result: 'has_alias' }
            ],
            default: 'external'
          }
        ]
      };

      let result = await ruleFlow.evaluate(config, { email: 'john@company.com' });
      expect(result.email_category).toBe('internal');

      result = await ruleFlow.evaluate(config, { email: 'user@gmail.com' });
      expect(result.email_category).toBe('personal');

      result = await ruleFlow.evaluate(config, { email: 'test+alias@domain.com' });
      expect(result.email_category).toBe('has_alias');
    });

    it('should work with starts_with operator', async () => {
      const config = {
        formulas: [
          {
            id: 'file_type',
            switch: '$filename',
            when: [
              { if: { op: 'starts_with', value: 'IMG_' }, result: 'image' },
              { if: { op: 'starts_with', value: 'VID_' }, result: 'video' },
              { if: { op: 'starts_with', value: 'DOC_' }, result: 'document' }
            ],
            default: 'unknown'
          }
        ]
      };

      let result = await ruleFlow.evaluate(config, { filename: 'IMG_20231225.jpg' });
      expect(result.file_type).toBe('image');

      result = await ruleFlow.evaluate(config, { filename: 'VID_meeting.mp4' });
      expect(result.file_type).toBe('video');

      result = await ruleFlow.evaluate(config, { filename: 'random_file.txt' });
      expect(result.file_type).toBe('unknown');
    });

    it('should work with ends_with operator', async () => {
      const config = {
        formulas: [
          {
            id: 'file_format',
            switch: '$filename',
            when: [
              { if: { op: 'ends_with', value: '.pdf' }, result: 'pdf_document' },
              { if: { op: 'ends_with', value: '.jpg' }, result: 'jpeg_image' },
              { if: { op: 'ends_with', value: '.docx' }, result: 'word_document' }
            ],
            default: 'unknown_format'
          }
        ]
      };

      let result = await ruleFlow.evaluate(config, { filename: 'report.pdf' });
      expect(result.file_format).toBe('pdf_document');

      result = await ruleFlow.evaluate(config, { filename: 'photo.jpg' });
      expect(result.file_format).toBe('jpeg_image');

      result = await ruleFlow.evaluate(config, { filename: 'file.txt' });
      expect(result.file_format).toBe('unknown_format');
    });
  });

  // ========================================
  // Test Function Operators
  // ========================================

  describe('Function Operators', () => {
    beforeEach(() => {
      // Register custom validation functions
      ruleFlow.registerFunction('isValidEmail', (email: string) => {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
      });

      ruleFlow.registerFunction('calculateDiscount', (tier: string, amount: number) => {
        const discounts = { VIP: 0.2, GOLD: 0.15, SILVER: 0.1 };
        return amount * (discounts[tier as keyof typeof discounts] || 0);
      });
    });

    it('should work with function conditions', async () => {
      const config : any =  {
        formulas: [
          {
            id: 'email_status',
            switch: '$email',
            when: [
              { if: { op: 'function', function: 'isValidEmail' }, result: 'valid' }
            ],
            default: 'invalid'
          }
        ]
      };

      let result = await ruleFlow.evaluate(config, { email: 'test@example.com' });
      expect(result.email_status).toBe('valid');

      result = await ruleFlow.evaluate(config, { email: 'invalid-email' });
      expect(result.email_status).toBe('invalid');
    });

    it('should work with function calls in results', async () => {
      const config = {
        formulas: [
          {
            id: 'discount_amount',
            switch: '$customer_tier',
            when: [
              { 
                if: { op: 'in', value: ['VIP', 'GOLD', 'SILVER'] },
                function_call: 'calculateDiscount',
                params: ['$customer_tier', '$order_amount']
              }
            ],
            default: 0
          }
        ]
      };

      let result = await ruleFlow.evaluate(config, { 
        customer_tier: 'VIP', 
        order_amount: 1000 
      });
      expect(result.discount_amount).toBe(200); // 1000 * 0.2

      result = await ruleFlow.evaluate(config, { 
        customer_tier: 'GOLD', 
        order_amount: 500 
      });
      expect(result.discount_amount).toBe(75); // 500 * 0.15

      result = await ruleFlow.evaluate(config, { 
        customer_tier: 'BASIC', 
        order_amount: 1000 
      });
      expect(result.discount_amount).toBe(0); // Default
    });
  });

  // ========================================
  // Test Complex Scenarios
  // ========================================

  describe('Complex Operator Combinations', () => {
    it('should handle mixed operators in nested conditions', async () => {
      const config = {
        formulas: [
          {
            id: 'loan_approval',
            switch: 'evaluate',
            when: [
              {
                if: {
                  and: [
                    { op: 'between', var: 'age', value: [18, 65] },
                    { op: 'in', var: 'employment_status', value: ['full_time', 'self_employed'] },
                    { op: '>=', var: 'credit_score', value: 650 },
                    {
                      or: [
                        { op: '>=', var: 'annual_income', value: 50000 },
                        { op: 'contains', var: 'references', value: 'bank_statement' }
                      ]
                    }
                  ]
                },
                result: 'approved'
              }
            ],
            default: 'rejected'
          }
        ]
      };

      // Test approval case
      let result = await ruleFlow.evaluate(config, {
        evaluate: true,
        age: 35,
        employment_status: 'full_time',
        credit_score: 720,
        annual_income: 60000,
        references: 'employment_letter'
      });
      expect(result.loan_approval).toBe('approved');

      // Test rejection case - age out of range
      result = await ruleFlow.evaluate(config, {
        evaluate: true,
        age: 70,
        employment_status: 'full_time',
        credit_score: 720,
        annual_income: 60000,
        references: 'employment_letter'
      });
      expect(result.loan_approval).toBe('rejected');

      // Test rejection case - invalid employment
      result = await ruleFlow.evaluate(config, {
        evaluate: true,
        age: 35,
        employment_status: 'unemployed',
        credit_score: 720,
        annual_income: 60000,
        references: 'employment_letter'
      });
      expect(result.loan_approval).toBe('rejected');
    });
  });

  // ========================================
  // Test Error Cases  
  // ========================================

  describe('Error Handling', () => {
    it('should throw error for invalid between operator usage', async () => {
      const config = {
        formulas: [
          {
            id: 'invalid_between',
            switch: '$value',
            when: [
              { if: { op: 'between', value: [10] }, result: 'invalid' } // Missing second value
            ]
          }
        ]
      };

      await expect(ruleFlow.evaluate(config, { value: 15 }))
        .rejects.toThrow('Between operator requires array with 2 values');
    });

    it('should throw error for invalid in operator usage', async () => {
      const config = {
        formulas: [
          {
            id: 'invalid_in',
            switch: '$value',
            when: [
              { if: { op: 'in', value: 'not_an_array' }, result: 'invalid' }
            ]
          }
        ]
      };

      await expect(ruleFlow.evaluate(config, { value: 'test' }))
        .rejects.toThrow('In operator requires array value');
    });
  });
});