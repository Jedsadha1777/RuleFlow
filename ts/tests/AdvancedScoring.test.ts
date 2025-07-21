import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow } from '../src/RuleFlow.js';

describe('Advanced Scoring Tests', () => {
  let ruleFlow: RuleFlow;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
  });

  // Test 1: Accumulative Scoring
  describe('Accumulative Scoring', () => {
    it('should calculate credit score with multiple factors', async () => {
      const config = {
        formulas: [
          {
            id: 'credit_score',
            rules: [
              {
                var: 'income',
                ranges: [
                  { if: { op: '>=', value: 100000 }, score: 40 },
                  { if: { op: '>=', value: 50000 }, score: 25 },
                  { if: { op: '>=', value: 30000 }, score: 15 }
                ]
              },
              {
                var: 'employment_years',
                ranges: [
                  { if: { op: '>=', value: 5 }, score: 20 },
                  { if: { op: '>=', value: 2 }, score: 10 }
                ]
              },
              {
                var: 'has_property',
                if: { op: '==', value: true },
                score: 25
              },
              {
                var: 'debt_ratio',
                ranges: [
                  { if: { op: '<=', value: 0.3 }, score: 15 },
                  { if: { op: '<=', value: 0.5 }, score: 5 },
                  { if: { op: '>', value: 0.5 }, score: -10 }
                ]
              }
            ]
          }
        ]
      };

      // Test excellent profile
      const excellent = await ruleFlow.evaluate(config, {
        income: 120000,
        employment_years: 8,
        has_property: true,
        debt_ratio: 0.2
      });
      expect(excellent.credit_score).toBe(100); // 40 + 20 + 25 + 15

      // Test good profile
      const good = await ruleFlow.evaluate(config, {
        income: 60000,
        employment_years: 3,
        has_property: false,
        debt_ratio: 0.4
      });
      expect(good.credit_score).toBe(40); // 25 + 10 + 0 + 5

      // Test poor profile
      const poor = await ruleFlow.evaluate(config, {
        income: 25000,
        employment_years: 1,
        has_property: false,
        debt_ratio: 0.7
      });
      expect(poor.credit_score).toBe(-10); // 0 + 0 + 0 + (-10)
    });

    it('should handle weighted accumulative scoring', async () => {
      const config = {
        formulas: [
          {
            id: 'performance_score',
            rules: [
              {
                var: 'technical_skills',
                ranges: [
                  { if: { op: '>=', value: 90 }, result: 30 },
                  { if: { op: '>=', value: 70 }, result: 20 }
                ],
                weight: 1.5 // Technical skills more important
              },
              {
                var: 'communication',
                ranges: [
                  { if: { op: '>=', value: 80 }, result: 25 },
                  { if: { op: '>=', value: 60 }, result: 15 }
                ],
                weight: 1.0
              },
              {
                var: 'leadership',
                if: { op: '>=', value: 75 },
                result: 20,
                weight: 0.8
              }
            ]
          }
        ]
      };

      const inputs = {
        technical_skills: 95, // 30 * 1.5 = 45
        communication: 85,    // 25 * 1.0 = 25  
        leadership: 80        // 20 * 0.8 = 16
      };

      const result = await ruleFlow.evaluate(config, inputs);
      expect(result.performance_score).toBe(86); // 45 + 25 + 16
    });
  });

  // Test 2: Multi-dimensional Scoring
  describe('Multi-dimensional Scoring', () => {
    it('should handle employee bonus matrix', async () => {
      const config = {
        formulas: [
          {
            id: 'bonus_calculator',
            scoring: {
              ifs: {
                vars: ['performance_rating', 'tenure_years'],
                tree: [
                  {
                    if: { op: '>=', value: 90 }, // Excellent performance
                    ranges: [
                      { 
                        if: { op: '>=', value: 5 }, 
                        result: 15000, 
                        level: 'Senior Excellent',
                        set_vars: { '$bonus_type': 'premium' }
                      },
                      { 
                        if: { op: '>=', value: 2 }, 
                        result: 12000, 
                        level: 'Mid Excellent' 
                      },
                      { 
                        if: { op: '<', value: 2 }, 
                        result: 8000, 
                        level: 'Junior Excellent' 
                      }
                    ]
                  },
                  {
                    if: { op: '>=', value: 70 }, // Good performance
                    ranges: [
                      { if: { op: '>=', value: 5 }, result: 8000, level: 'Senior Good' },
                      { if: { op: '>=', value: 2 }, result: 6000, level: 'Mid Good' },
                      { if: { op: '<', value: 2 }, result: 4000, level: 'Junior Good' }
                    ]
                  },
                  {
                    if: { op: '>=', value: 50 }, // Average performance
                    ranges: [
                      { if: { op: '>=', value: 5 }, result: 3000, level: 'Senior Average' },
                      { if: { op: '>=', value: 2 }, result: 2000, level: 'Mid Average' },
                      { if: { op: '<', value: 2 }, result: 1000, level: 'Junior Average' }
                    ]
                  }
                ]
              }
            }
          }
        ]
      };

      // Test senior excellent employee
      const result1 = await ruleFlow.evaluate(config, {
        performance_rating: 95,
        tenure_years: 7
      });
      expect(result1.bonus_calculator).toBe(15000);
      expect(result1.bonus_calculator_level).toBe('Senior Excellent');
      expect(result1.bonus_type).toBe('premium');

      // Test junior good employee
      const result2 = await ruleFlow.evaluate(config, {
        performance_rating: 75,
        tenure_years: 1
      });
      expect(result2.bonus_calculator).toBe(4000);
      expect(result2.bonus_calculator_level).toBe('Junior Good');

      // Test mid average employee
      const result3 = await ruleFlow.evaluate(config, {
        performance_rating: 55,
        tenure_years: 3
      });
      expect(result3.bonus_calculator).toBe(2000);
      expect(result3.bonus_calculator_level).toBe('Mid Average');
    });

    it('should handle risk assessment matrix', async () => {
      const config = {
        formulas: [
          {
            id: 'risk_assessment',
            scoring: {
              ifs: {
                vars: ['credit_score', 'loan_amount'],
                tree: [
                  {
                    if: { op: '>=', value: 750 }, // Excellent credit
                    ranges: [
                      { 
                        if: { op: '>=', value: 1000000 }, 
                        result: 0, 
                        decision: 'REJECT',
                        level: 'HIGH_RISK' 
                      },
                      { 
                        if: { op: '>=', value: 500000 }, 
                        result: 85, 
                        decision: 'APPROVE',
                        level: 'LOW_RISK' 
                      },
                      { 
                        if: { op: '>=', value: 0 }, 
                        result: 95, 
                        decision: 'APPROVE',
                        level: 'VERY_LOW_RISK' 
                      }
                    ]
                  },
                  {
                    if: { op: '>=', value: 650 }, // Good credit
                    ranges: [
                      { 
                        if: { op: '>=', value: 500000 }, 
                        result: 0, 
                        decision: 'REJECT',
                        level: 'HIGH_RISK' 
                      },
                      { 
                        if: { op: '>=', value: 100000 }, 
                        result: 70, 
                        decision: 'APPROVE',
                        level: 'MEDIUM_RISK' 
                      },
                      { 
                        if: { op: '>=', value: 0 }, 
                        result: 80, 
                        decision: 'APPROVE',
                        level: 'LOW_RISK' 
                      }
                    ]
                  },
                  {
                    if: { op: '<', value: 650 }, // Poor credit
                    ranges: [
                      { 
                        if: { op: '>=', value: 100000 }, 
                        result: 0, 
                        decision: 'REJECT',
                        level: 'HIGH_RISK' 
                      },
                      { 
                        if: { op: '>=', value: 50000 }, 
                        result: 30, 
                        decision: 'MANUAL_REVIEW',
                        level: 'HIGH_RISK' 
                      },
                      { 
                        if: { op: '>=', value: 0 }, 
                        result: 50, 
                        decision: 'APPROVE',
                        level: 'MEDIUM_RISK' 
                      }
                    ]
                  }
                ]
              }
            }
          }
        ]
      };

      // Test excellent credit, small loan
      const result1 = await ruleFlow.evaluate(config, {
        credit_score: 800,
        loan_amount: 250000
      });
      expect(result1.risk_assessment).toBe(95);
      expect(result1.risk_assessment_decision).toBe('APPROVE');
      expect(result1.risk_assessment_level).toBe('VERY_LOW_RISK');

      // Test good credit, large loan (rejected)
      const result2 = await ruleFlow.evaluate(config, {
        credit_score: 700,
        loan_amount: 600000
      });
      expect(result2.risk_assessment).toBe(0);
      expect(result2.risk_assessment_decision).toBe('REJECT');
      expect(result2.risk_assessment_level).toBe('HIGH_RISK');

      // Test poor credit, medium loan (manual review)
      const result3 = await ruleFlow.evaluate(config, {
        credit_score: 600,
        loan_amount: 75000
      });
      expect(result3.risk_assessment).toBe(30);
      expect(result3.risk_assessment_decision).toBe('MANUAL_REVIEW');
      expect(result3.risk_assessment_level).toBe('HIGH_RISK');
    });
  });

  // Test 3: Complex Scoring with Variables
  describe('Complex Scoring with Variables', () => {
    it('should handle multi-step scoring with intermediate calculations', async () => {
      const config = {
        formulas: [
          // Step 1: Calculate base metrics
          {
            id: 'debt_to_income_ratio',
            formula: 'total_debt / monthly_income',
            inputs: ['total_debt', 'monthly_income']
          },
          {
            id: 'income_score',
            switch: 'monthly_income',
            when: [
              { if: { op: '>=', value: 10000 }, result: 50 },
              { if: { op: '>=', value: 7000 }, result: 35 },
              { if: { op: '>=', value: 5000 }, result: 25 },
              { if: { op: '>=', value: 3000 }, result: 15 }
            ],
            default: 5
          },
          // Step 2: Comprehensive credit evaluation
          {
            id: 'comprehensive_score',
            rules: [
              {
                var: 'income_score',
                if: { op: '>=', value: 1 },
                score: 0, // Use the income_score as-is
                weight: 1.0
              },
              {
                var: 'debt_to_income_ratio',
                ranges: [
                  { if: { op: '<=', value: 0.2 }, result: 30 },
                  { if: { op: '<=', value: 0.4 }, result: 20 },
                  { if: { op: '<=', value: 0.6 }, result: 10 },
                  { if: { op: '>', value: 0.6 }, result: -20 }
                ],
                weight: 1.2
              },
              {
                var: 'credit_history_years',
                ranges: [
                  { if: { op: '>=', value: 10 }, result: 20 },
                  { if: { op: '>=', value: 5 }, result: 15 },
                  { if: { op: '>=', value: 2 }, result: 10 }
                ],
                weight: 0.8
              }
            ]
          },
          // Step 3: Final decision based on comprehensive score
          {
            id: 'loan_decision',
            switch: 'comprehensive_score',
            when: [
              { 
                if: { op: '>=', value: 80 }, 
                result: 'APPROVED',
                set_vars: {
                  '$interest_rate': '3.5',
                  '$max_amount': '1000000'
                }
              },
              { 
                if: { op: '>=', value: 60 }, 
                result: 'APPROVED',
                set_vars: {
                  '$interest_rate': '4.5',
                  '$max_amount': '500000'
                }
              },
              { 
                if: { op: '>=', value: 40 }, 
                result: 'CONDITIONAL',
                set_vars: {
                  '$interest_rate': '6.0',
                  '$max_amount': '200000'
                }
              }
            ],
            default: 'REJECTED'
          }
        ]
      };

      // Test excellent candidate
      const excellent = await ruleFlow.evaluate(config, {
        monthly_income: 15000,
        total_debt: 2000,
        credit_history_years: 12
      });

      expect(excellent.debt_to_income_ratio).toBeCloseTo(0.133, 3);
      expect(excellent.income_score).toBe(50);
      expect(excellent.comprehensive_score).toBeCloseTo(102, 0); // 50*1.0 + 30*1.2 + 20*0.8
      expect(excellent.loan_decision).toBe('APPROVED');
      expect(excellent.interest_rate).toBe(3.5);
      expect(excellent.max_amount).toBe(1000000);

      // Test marginal candidate
      const marginal = await ruleFlow.evaluate(config, {
        monthly_income: 5500,
        total_debt: 2750,
        credit_history_years: 3
      });

      expect(marginal.debt_to_income_ratio).toBe(0.5);
      expect(marginal.income_score).toBe(25);
      expect(marginal.comprehensive_score).toBeCloseTo(45, 0); // 25*1.0 + 20*1.2 + 0*0.8
      expect(marginal.loan_decision).toBe('CONDITIONAL');
      expect(marginal.interest_rate).toBe(6.0);
      expect(marginal.max_amount).toBe(200000);

      // Test poor candidate
      const poor = await ruleFlow.evaluate(config, {
        monthly_income: 2500,
        total_debt: 2000,
        credit_history_years: 1
      });

      expect(poor.debt_to_income_ratio).toBe(0.8);
      expect(poor.income_score).toBe(5);
      expect(poor.comprehensive_score).toBeCloseTo(-19, 0);  // 5 + (-20)*1.2 + 0*0.8
      expect(poor.loan_decision).toBe('REJECTED');
    });
  });

  // Test 4: Advanced Operators
  describe('Advanced Operators', () => {
    it('should handle between and in operators', async () => {
      const config = {
        formulas: [
          {
            id: 'age_category_score',
            rules: [
              {
                var: 'age',
                ranges: [
                  { if: { op: 'between', value: [25, 35] }, result: 25, level: 'Prime' },
                  { if: { op: 'between', value: [18, 24] }, result: 15, level: 'Young' },
                  { if: { op: 'between', value: [36, 50] }, result: 20, level: 'Mature' },
                  { if: { op: '>', value: 50 }, result: 10, level: 'Senior' }
                ]
              },
              {
                var: 'region',
                ranges: [
                  { if: { op: 'in', value: ['BKK', 'CNX', 'HKT'] }, result: 15, level: 'Metro' },
                  { if: { op: 'in', value: ['NMA', 'KON', 'UDN'] }, result: 10, level: 'Regional' }
                ]
              },
              {
                var: 'occupation',
                ranges: [
                  { if: { op: 'in', value: ['doctor', 'lawyer', 'engineer'] }, result: 20, level: 'Professional' },
                  { if: { op: 'in', value: ['teacher', 'nurse', 'accountant'] }, result: 15, level: 'Skilled' },
                  { if: { op: 'in', value: ['clerk', 'sales', 'service'] }, result: 10, level: 'General' }
                ]
              }
            ]
          }
        ]
      };

      const result = await ruleFlow.evaluate(config, {
        age: 30,        // Prime: 25 points
        region: 'BKK',  // Metro: 15 points  
        occupation: 'doctor' // Professional: 20 points
      });

      expect(result.age_category_score).toBe(60); // 25 + 15 + 20
    });
  });

  // Test 5: Error Handling
  describe('Error Handling', () => {
    it('should handle missing variables gracefully', async () => {
      const config = {
        formulas: [
          {
            id: 'partial_score',
            rules: [
              {
                var: 'existing_var',
                if: { op: '>=', value: 50 },
                score: 10
              },
              {
                var: 'missing_var',
                if: { op: '>=', value: 100 },
                score: 20
              }
            ]
          }
        ]
      };

      const result = await ruleFlow.evaluate(config, {
        existing_var: 75
        // missing_var is not provided
      });

      expect(result.partial_score).toBe(10); // Only existing_var contributes
    });

    it('should throw error for invalid scoring configuration', async () => {
      const config = {
        formulas: [
          {
            id: 'invalid_scoring',
            scoring: {
              // Missing required fields
            }
          }
        ]
      };

      await expect(ruleFlow.evaluate(config, {}))
        .rejects
        .toThrow('Invalid scoring configuration');
    });
  });
});