// tests/template-system.test.ts

import { describe, it, expect } from 'vitest';
import { RuleFlow } from '../src/RuleFlow';
import * as Templates from '../src/templates/index';
import { TEMPLATES, CATEGORIES, TEMPLATE_STATS } from '../src/templates/config';

describe('Template System', () => {
  const ruleFlow = new RuleFlow();

  // ========================================
  // Test Template Registry
  // ========================================

  describe('Template Registry', () => {
    it('should have all required templates', () => {
      const expectedTemplates = [
        'bmi_calculator',
        'health_risk_assessment',
        'loan_approval',
        'credit_scoring',
        'employee_performance',
        'salary_calculator',
        'dynamic_pricing',
        'shipping_calculator',
        'auto_insurance_risk',
        'life_insurance_underwriting',
        'grade_calculator',
        'scholarship_eligibility',
        'property_valuation',
        'mortgage_qualification'
      ];

      const availableTemplates = Templates.getAvailableTemplates();
      expectedTemplates.forEach(template => {
        expect(availableTemplates).toContain(template);
      });

      expect(availableTemplates.length).toBeGreaterThanOrEqual(14);
    });

    it('should have all required categories', () => {
      const expectedCategories = [
        'healthcare',
        'financial',
        'hr',
        'ecommerce',
        'insurance',
        'education',
        'real-estate'
      ];

      const availableCategories = Templates.getAvailableCategories();
      expectedCategories.forEach(category => {
        expect(availableCategories).toContain(category);
      });
    });

    it('should provide correct template counts', () => {
      expect(TEMPLATE_STATS.total).toBeGreaterThanOrEqual(14);
      expect(TEMPLATE_STATS.byCategory.healthcare).toBe(2);
      expect(TEMPLATE_STATS.byCategory.financial).toBe(2);
      expect(TEMPLATE_STATS.byCategory.insurance).toBe(2);
      expect(TEMPLATE_STATS.byDifficulty.beginner).toBeGreaterThan(0);
      expect(TEMPLATE_STATS.byDifficulty.intermediate).toBeGreaterThan(0);
      expect(TEMPLATE_STATS.byDifficulty.advanced).toBeGreaterThan(0);
    });
  });

  // ========================================
  // Test Template Structure
  // ========================================

  describe('Template Structure', () => {
    it('should have valid template structure', () => {
      Object.entries(TEMPLATES).forEach(([name, template]) => {
        // Check required properties
        expect(template).toHaveProperty('config');
        expect(template).toHaveProperty('metadata');
        expect(template).toHaveProperty('examples');

        // Check config structure
        expect(template.config).toHaveProperty('formulas');
        expect(Array.isArray(template.config.formulas)).toBe(true);
        expect(template.config.formulas.length).toBeGreaterThan(0);

        // Check metadata structure
        expect(template.metadata).toHaveProperty('name');
        expect(template.metadata).toHaveProperty('description');
        expect(template.metadata).toHaveProperty('category');
        expect(template.metadata).toHaveProperty('difficulty');
        expect(template.metadata).toHaveProperty('inputs');
        expect(template.metadata).toHaveProperty('outputs');

        // Check examples structure
        expect(Array.isArray(template.examples)).toBe(true);
        expect(template.examples.length).toBeGreaterThan(0);

        template.examples.forEach(example => {
          expect(example).toHaveProperty('name');
          expect(example).toHaveProperty('inputs');
          expect(example).toHaveProperty('expectedOutputs');
        });
      });
    });

    it('should have unique template names', () => {
      const templateNames = Object.keys(TEMPLATES);
      const uniqueNames = [...new Set(templateNames)];
      expect(templateNames.length).toBe(uniqueNames.length);
    });

    it('should have valid difficulty levels', () => {
      const validDifficulties = ['beginner', 'intermediate', 'advanced'];
      Object.values(TEMPLATES).forEach(template => {
        expect(validDifficulties).toContain(template.metadata.difficulty);
      });
    });
  });

  // ========================================
  // Test Template Functions
  // ========================================

  describe('Template Functions', () => {
    it('should get template by name', () => {
      const template = Templates.getTemplate('bmi_calculator');
      expect(template).toBeDefined();
      expect(template?.metadata.name).toBe('BMI Calculator');
    });

    it('should get template config', () => {
      const config = Templates.getTemplateConfig('bmi_calculator');
      expect(config).toBeDefined();
      expect(config?.formulas).toBeDefined();
      expect(config?.formulas.length).toBeGreaterThan(0);
    });

    it('should get templates by category', () => {
      const healthcareTemplates = Templates.getTemplatesByCategory('healthcare');
      expect(healthcareTemplates).toContain('bmi_calculator');
      expect(healthcareTemplates).toContain('health_risk_assessment');

      const financialTemplates = Templates.getTemplatesByCategory('financial');
      expect(financialTemplates).toContain('loan_approval');
      expect(financialTemplates).toContain('credit_scoring');
    });

    it('should search templates by keyword', () => {
      const bmiResults = Templates.searchTemplates('bmi');
      expect(bmiResults).toContain('bmi_calculator');

      const loanResults = Templates.searchTemplates('loan');
      expect(loanResults).toContain('loan_approval');

      const healthResults = Templates.searchTemplates('health');
      expect(healthResults.length).toBeGreaterThan(0);
    });

    it('should get template metadata', () => {
      const metadata = Templates.getTemplateMetadata('bmi_calculator');
      expect(metadata).toBeDefined();
      expect(metadata?.category).toBe('healthcare');
      expect(metadata?.inputs).toContain('weight');
      expect(metadata?.inputs).toContain('height');
    });

    it('should get template examples', () => {
      const examples = Templates.getTemplateExamples('bmi_calculator');
      expect(examples.length).toBeGreaterThan(0);
      expect(examples[0]).toHaveProperty('name');
      expect(examples[0]).toHaveProperty('inputs');
      expect(examples[0]).toHaveProperty('expectedOutputs');
    });

    it('should check template existence', () => {
      expect(Templates.hasTemplate('bmi_calculator')).toBe(true);
      expect(Templates.hasTemplate('nonexistent_template')).toBe(false);
    });
  });

  // ========================================
  // Test RuleFlow Integration
  // ========================================

  describe('RuleFlow Integration', () => {
    it('should get templates through RuleFlow', () => {
      const templates = ruleFlow.getAvailableTemplates();
      expect(templates.length).toBeGreaterThan(0);
      expect(templates).toContain('bmi_calculator');
    });

    it('should get template config through RuleFlow', () => {
      const config = ruleFlow.getTemplate('bmi_calculator');
      expect(config).toBeDefined();
      expect(config?.formulas).toBeDefined();
    });

    it('should evaluate template directly', async () => {
      const result = await ruleFlow.evaluateTemplate('bmi_calculator', {
        weight: 70,
        height: 1.75
      });

      expect(result.bmi).toBeCloseTo(22.86, 2);
      expect(result.bmi_category).toBe('Normal');
      expect(result.health_score).toBe(100);
    });

    it('should test template with examples', async () => {
      const testResult = await ruleFlow.testTemplate('bmi_calculator', 0);
      
      expect(testResult.template).toBe('bmi_calculator');
      expect(testResult.inputs).toBeDefined();
      expect(testResult.outputs).toBeDefined();
      expect(testResult.expected).toBeDefined();
    });

    it('should get template categories through RuleFlow', () => {
      const categories = ruleFlow.getTemplateCategories();
      expect(categories).toContain('healthcare');
      expect(categories).toContain('financial');
      expect(categories).toContain('insurance');
    });

    it('should search templates through RuleFlow', () => {
      const results = ruleFlow.searchTemplates('insurance');
      expect(results.length).toBeGreaterThan(0);
      expect(results).toContain('auto_insurance_risk');
    });
  });

  // ========================================
  // Test Specific Templates
  // ========================================

  describe('Healthcare Templates', () => {
    it('should calculate BMI correctly', async () => {
        const result = await ruleFlow.evaluateTemplate('bmi_calculator', {
        weight: 85,
        height: 1.70
        });

        expect(result.bmi).toBeCloseTo(29.41, 2);
        expect(result.bmi_category).toBe('Overweight');
        expect(result.health_score).toBe(75); // แก้ไขแล้ว: switch logic แทน ternary
    });

    it('should assess health risk correctly', async () => {
        const result = await ruleFlow.evaluateTemplate('health_risk_assessment', {
        age: 60,
        smoking: true,
        exercise_hours_week: 1,
        family_history: true
        });

        expect(result.risk_factors).toBe(36); 
        expect(result.risk_level).toBe('Very High Risk');
    });
  });

  describe('Financial Templates', () => {
    it('should process loan approval correctly', async () => {
        const result = await ruleFlow.evaluateTemplate('loan_approval', {
        monthly_income: 8000,
        monthly_debt: 2000,
        credit_score: 780,
        employment_years: 5,
        trigger: 'evaluate'
        });

        expect(result.debt_to_income_ratio).toBeCloseTo(0.25, 2);
        expect(result.credit_tier).toBe('excellent');
        expect(result.loan_decision).toBe('approved');
        expect(result.interest_rate).toBe(3.5); 
    });

    it('should calculate credit score correctly', async () => {
        const result = await ruleFlow.evaluateTemplate('credit_scoring', {
        late_payments_12m: 1,
        missed_payments: 0,
        credit_utilization_ratio: 0.25,
        credit_age_months: 48,
        new_credit_score: 80,
        credit_mix_score: 85
        });

        expect(result.payment_history_score).toBe(180); 
        expect(result.credit_utilization_score).toBe(80);
        expect(result.credit_score).toBeCloseTo(110.7, 1);
        expect(result.credit_rating).toBe('Very Poor');
    });
  });

  describe('E-commerce Templates', () => {
    it('should calculate dynamic pricing correctly', async () => {
         const result = await ruleFlow.evaluateTemplate('dynamic_pricing', {
          base_price: 100,
          demand_level: 'high',
          inventory_level: 5,
          customer_tier: 'VIP',
          season: 'holiday' 
        });

        expect(result.demand_multiplier).toBe(1.3);
        expect(result.inventory_multiplier).toBe(1.2);
        expect(result.season_multiplier).toBe(1.2); 
        expect(result.customer_tier_discount).toBe(0.15);
  
        expect(result.final_price).toBeCloseTo(159.12, 1); 
        expect(result.price_category).toBe('Premium');
    });

    it('should calculate shipping costs correctly', async () => {
        const result = await ruleFlow.evaluateTemplate('shipping_calculator', {
        weight_kg: 15,
        shipping_zone: 'international',
        delivery_speed: 'express',
        is_fragile: true,
        order_value: 200,
        check_free_shipping: true
        });

        expect(result.weight_cost).toBe(25);
        expect(result.distance_multiplier).toBe(3.5);
        expect(result.speed_multiplier).toBe(2.0);
        // base_shipping_cost = 25 * 3.5 * 2.0 = 175
        // total_shipping_cost = 175 + 5 (fragile) + 2 (insurance) = 182
        expect(result.total_shipping_cost).toBe(182); // แก้ไขการคำนวณ
        expect(result.free_shipping_eligible).toBe(false);
        expect(result.final_shipping_cost).toBe(182);
    });
  }); 

  // ========================================
  // Test Error Handling
  // ========================================

  describe('Error Handling', () => {
    it('should handle non-existent template', () => {
      const template = Templates.getTemplate('nonexistent' as any);
      expect(template).toBeNull();
    });

    it('should handle invalid category', () => {
      const templates = Templates.getTemplatesByCategory('invalid' as any);
      expect(templates).toEqual([]);
    });

    it('should throw error for non-existent template evaluation', async () => {
      await expect(
        ruleFlow.evaluateTemplate('nonexistent', {})
      ).rejects.toThrow();
    });

    it('should throw error for invalid example index', async () => {
      await expect(
        ruleFlow.testTemplate('bmi_calculator', 999)
      ).rejects.toThrow();
    });
  });
});