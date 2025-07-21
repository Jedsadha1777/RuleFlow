import { describe, it, expect, beforeEach } from 'vitest';
import { RuleFlow } from '../src/RuleFlow';
import { FUNCTION_TEMPLATES, FunctionTemplateManager } from '../src/functions/templates/index';

describe('New Function Templates Integration', () => {
  let ruleFlow: RuleFlow;
  let templateManager: FunctionTemplateManager;

  beforeEach(() => {
    ruleFlow = new RuleFlow();
    templateManager = new FunctionTemplateManager();
  });

  // ========================================
  // Test Template Registry Updates
  // ========================================

  describe('Template Registry', () => {
    it('should have all 7 templates (4 original + 3 new)', () => {
      const expectedTemplates = [
        'hotel', 'financial', 'ecommerce', 'healthcare', // Original
        'date', 'business', 'education' // New
      ];
      
      const availableTemplates = ruleFlow.getAvailableFunctionTemplates();
      
      expectedTemplates.forEach(template => {
        expect(availableTemplates).toContain(template);
      });
      
      expect(availableTemplates.length).toBe(7);
    });

    it('should provide information for new templates', () => {
      const dateInfo = ruleFlow.getFunctionTemplateInfo('date');
      expect(dateInfo?.name).toBe('Thai Date Functions');
      expect(dateInfo?.category).toBe('Date');
      
      const businessInfo = ruleFlow.getFunctionTemplateInfo('business');
      expect(businessInfo?.name).toBe('Advanced Business Functions');
      expect(businessInfo?.category).toBe('Business');
      
      const educationInfo = ruleFlow.getFunctionTemplateInfo('education');
      expect(educationInfo?.name).toBe('Education Functions');
      expect(educationInfo?.category).toBe('Education');
    });

    it('should show increased function count', () => {
      const summary = templateManager.getTemplateSummary();
      
      expect(summary.length).toBe(7);
      
      // ตรวจสอบว่ามี templates ใหม่
      const templateNames = summary.map(t => t.name);
      expect(templateNames).toContain('date');
      expect(templateNames).toContain('business');
      expect(templateNames).toContain('education');
      
      // ตรวจสอบว่ามี functions เพิ่มขึ้น
      const totalFunctions = summary.reduce((sum, template) => sum + template.functionCount, 0);
      expect(totalFunctions).toBeGreaterThan(50); // Should be 50+ functions now
    });
  });

  // ========================================
  // Test Date Functions
  // ========================================

  describe('Date Functions Template', () => {
    beforeEach(() => {
      ruleFlow.loadFunctionTemplate('date');
    });

    it('should load date template and register functions', () => {
      expect(ruleFlow.isFunctionTemplateLoaded('date')).toBe(true);
      
      // แก้ไข: เปลี่ยนจากการเช็ค string ไปเป็นเช็ค name property
      const availableFunctions = ruleFlow.getAvailableFunctions();
      const functionNames = availableFunctions.map(func => func.name);
      
      const dateFunctions = [
        'is_business_day', 'days_until', 'is_holiday', 'format_thai_date',
        'business_days_between', 'thai_fiscal_year', 'is_weekend_thai', 'thai_quarter'
      ];
      
      dateFunctions.forEach(func => {
        expect(functionNames).toContain(func);
      });
    });

    it('should handle Thai business day calculations', async () => {
      // ใช้ direct function call แทน formula เพราะ is_business_day คืน boolean
      const functionRegistry = ruleFlow['functionRegistry']; // access private
      
      const isBusinessDay = functionRegistry.call('is_business_day', ['2025-07-21']);
      expect(typeof isBusinessDay).toBe('boolean');
      
      const thaiDate = functionRegistry.call('format_thai_date', ['2025-07-20']);
      expect(thaiDate).toBe('20 กรกฎาคม 2568');
    });

    it('should calculate business days between dates', async () => {
      const functionRegistry = ruleFlow['functionRegistry'];
      
      const businessDays = functionRegistry.call('business_days_between', ['2025-07-01', '2025-07-31']);
      
      // July มี 31 วัน ลบ weekends และ holidays = ประมาณ 22-23 วัน
      expect(businessDays).toBeGreaterThan(15);
      expect(businessDays).toBeLessThan(25);
    });

    it('should recognize Thai holidays', async () => {
      const functionRegistry = ruleFlow['functionRegistry'];
      
      const isHoliday = functionRegistry.call('is_holiday', ['2025-04-13']); // Songkran
      expect(isHoliday).toBe(true);
      
      const fiscalYear = functionRegistry.call('thai_fiscal_year', ['2025-10-01']);
      expect(fiscalYear).toBe(2026);
    });
  });

  // ========================================
  // Test Business Functions
  // ========================================

  describe('Business Functions Template', () => {
    beforeEach(() => {
      ruleFlow.loadFunctionTemplate('business');
    });

    it('should load business template and register functions', () => {
      expect(ruleFlow.isFunctionTemplateLoaded('business')).toBe(true);
      
      // แก้ไข: เปลี่ยนจากการเช็ค string ไปเป็นเช็ค name property
      const availableFunctions = ruleFlow.getAvailableFunctions();
      const functionNames = availableFunctions.map(func => func.name);
      
      const businessFunctions = [
        'shipping_cost', 'tax_amount', 'loyalty_points', 'seasonal_multiplier',
        'tier_discount', 'bulk_discount', 'working_hours_multiplier',
        'payment_processing_fee', 'customer_lifetime_value', 'profit_margin'
      ];
      
      businessFunctions.forEach(func => {
        expect(functionNames).toContain(func);
      });
    });

    it('should calculate shipping costs correctly', async () => {
      const config = {
        formulas: [
          {
            id: 'standard_shipping',
            formula: 'shipping_cost(weight, distance, "standard")',
            inputs: ['weight', 'distance']
          },
          {
            id: 'express_shipping',
            formula: 'shipping_cost(weight, distance, "express")',
            inputs: ['weight', 'distance']
          }
        ]
      };

      const result = await ruleFlow.evaluate(config, {
        weight: 5,      // 5kg
        distance: 100   // 100km
      });

      expect(result.standard_shipping).toBe(22.5); // (5*2.5 + 100*0.1) * 1.0
      expect(result.express_shipping).toBe(45);    // (5*2.5 + 100*0.1) * 2.0
    });

    it('should handle complex business calculations', async () => {
      const functionRegistry = ruleFlow['functionRegistry'];
      
      const seasonalPrice = functionRegistry.call('seasonal_multiplier', ['2025-04-13', 1000]);
      expect(seasonalPrice).toBe(2000); // Peak season 2x
      
      const tierSavings = functionRegistry.call('tier_discount', [1000, 'gold', 3]);
      expect(tierSavings).toBe(180); // Gold 15% + 3% loyalty = 18%
      
      const bulkResult = functionRegistry.call('bulk_discount', [150, 10]);
      expect(bulkResult.total).toBe(1350);
      expect(bulkResult.discountPercent).toBe(10);
    });

    it('should calculate payment processing fees', async () => {
      const config = {
        formulas: [
          {
            id: 'credit_fee',
            formula: 'payment_processing_fee(payment_amount, "credit_card")',
            inputs: ['payment_amount']
          },
          {
            id: 'bank_fee',
            formula: 'payment_processing_fee(payment_amount, "bank_transfer")',
            inputs: ['payment_amount']
          }
        ]
      };

      const result = await ruleFlow.evaluate(config, {
        payment_amount: 1000
      });

      expect(result.credit_fee).toBe(29);    // 2.9% of 1000
      expect(result.bank_fee).toBe(20);      // 15 fixed + 0.5% of 1000
    });
  });

  // ========================================
  // Test Education Functions
  // ========================================

  describe('Education Functions Template', () => {
    beforeEach(() => {
      ruleFlow.loadFunctionTemplate('education');
    });

    it('should load education template and register functions', () => {
      expect(ruleFlow.isFunctionTemplateLoaded('education')).toBe(true);
      
      // แก้ไข: เปลี่ยนจากการเช็ค string ไปเป็นเช็ค name property
      const availableFunctions = ruleFlow.getAvailableFunctions();
      const functionNames = availableFunctions.map(func => func.name);
      
      const educationFunctions = [
        'calculate_gpa', 'grade_to_point', 'academic_standing', 'credits_to_graduate',
        'semester_gpa', 'grade_distribution', 'class_rank', 'attendance_rate',
        'course_difficulty', 'graduation_timeline', 'scholarship_eligibility'
      ];
      
      educationFunctions.forEach(func => {
        expect(functionNames).toContain(func);
      });
    });

    it('should calculate GPA correctly', async () => {
      const functionRegistry = ruleFlow['functionRegistry'];
      
      const gradeAPoints = functionRegistry.call('grade_to_point', ['A', '4.0']);
      expect(gradeAPoints).toBe(4.0);
      
      const gradeBPlusPoints = functionRegistry.call('grade_to_point', ['B+', '4.0']);
      expect(gradeBPlusPoints).toBe(3.3);
      
      const academicStatus = functionRegistry.call('academic_standing', [3.8]);
      expect(academicStatus).toBe('Summa Cum Laude');
    });

    it('should handle graduation planning', async () => {
      const functionRegistry = ruleFlow['functionRegistry'];
      
      const graduationProgress = functionRegistry.call('credits_to_graduate', [90, 120]);
      expect(graduationProgress.remaining).toBe(30);
      expect(graduationProgress.progress).toBe(75);
      expect(graduationProgress.canGraduate).toBe(false);
      
      const attendanceStatus = functionRegistry.call('attendance_rate', [28, 30]);
      expect(attendanceStatus.rate).toBeCloseTo(93.33, 1);
      expect(attendanceStatus.status).toBe('Good');
    });
  });

  // ========================================
  // Test Multi-Template Integration
  // ========================================

  describe('Multi-Template Integration', () => {
    beforeEach(() => {
      // Load multiple templates
      ruleFlow.loadFunctionTemplates(['date', 'business', 'education']);
    });

    it('should load multiple templates simultaneously', () => {
      const loadedTemplates = ruleFlow.getLoadedFunctionTemplates();
      expect(loadedTemplates).toContain('date');
      expect(loadedTemplates).toContain('business');
      expect(loadedTemplates).toContain('education');
      expect(loadedTemplates.length).toBe(3);
    });

    it('should handle complex business scenario with multiple function types', async () => {
      const functionRegistry = ruleFlow['functionRegistry'];
      
      const isWeekendStay = functionRegistry.call('is_weekend_thai', ['2025-04-13']);
      expect(isWeekendStay).toBe(true); // Sunday
      
      const seasonalPrice = functionRegistry.call('seasonal_multiplier', ['2025-04-13', 2000]);
      expect(seasonalPrice).toBe(4000); // 2x peak season
      
      const customerDiscount = functionRegistry.call('tier_discount', [4000, 'platinum', 5]);
      expect(customerDiscount).toBeGreaterThan(800); // Platinum 20% + 5% loyalty
      
      const paymentFee = functionRegistry.call('payment_processing_fee', [3200, 'credit_card']);
      expect(paymentFee).toBeCloseTo(92.8, 1); // 2.9% of 3200
    });

    it('should handle educational scenario with date functions', async () => {
      const functionRegistry = ruleFlow['functionRegistry'];
      
      // days_until จะคืนค่าลบถ้าวันที่อยู่ในอดีต
      const daysToGraduation = functionRegistry.call('days_until', ['2026-05-15']);
      expect(daysToGraduation).toBeGreaterThan(200); // Should be 200+ days away
      
      const currentGpaStanding = functionRegistry.call('academic_standing', [3.7]);
      expect(currentGpaStanding).toBe('Magna Cum Laude');
      
      const scholarshipScore = functionRegistry.call('scholarship_eligibility', [3.7, 90, 75, 4]);
      expect(scholarshipScore.eligible).toBe(true);
      expect(scholarshipScore.score).toBeGreaterThan(80);
    });
  });

  // ========================================
  // Test Performance & Compatibility
  // ========================================

  describe('Performance & Compatibility', () => {
    it('should maintain performance with increased template count', () => {
      // Load all templates
      ruleFlow.loadFunctionTemplates(['hotel', 'financial', 'ecommerce', 'healthcare', 'date', 'business', 'education']);
      
      const start = performance.now();
      
      // Run multiple evaluations
      for (let i = 0; i < 100; i++) {
        ruleFlow.getAvailableFunctions();
        ruleFlow.getFunctionTemplateSummary();
      }
      
      const duration = performance.now() - start;
      expect(duration).toBeLessThan(50); // Should complete in under 50ms
    });

    it('should handle template search functionality', () => {
      const manager = new FunctionTemplateManager();
      
      // Search for date-related functions
      const dateResults = manager.searchFunctions('date');
      expect(dateResults.length).toBeGreaterThan(3);
      expect(dateResults.some(r => r.name.includes('thai'))).toBe(true);
      
      // Search for business functions
      const businessResults = manager.searchFunctions('discount');
      expect(businessResults.length).toBeGreaterThanOrEqual(2);
      expect(businessResults.some(r => r.category === 'Business')).toBe(true);
      
      // Search for education functions
      const educationResults = manager.searchFunctions('gpa');
      expect(educationResults.length).toBeGreaterThanOrEqual(1);
      expect(educationResults.some(r => r.category === 'Education')).toBe(true);
    });

    it('should maintain backward compatibility with existing templates', async () => {
      // Load original template
      ruleFlow.loadFunctionTemplate('healthcare');
      
      // Test existing BMI calculation still works
      const config = {
        formulas: [
          {
            id: 'bmi_value',
            formula: 'bmi(weight, height)',
            inputs: ['weight', 'height']
          }
        ]
      };

      const result = await ruleFlow.evaluate(config, {
        weight: 70,
        height: 1.75
      });

      expect(result.bmi_value).toBeCloseTo(22.86, 2);
    });
  });

  // ========================================
  // Test Error Handling
  // ========================================

  describe('Error Handling', () => {
    beforeEach(() => {
      ruleFlow.loadFunctionTemplates(['date', 'business', 'education']);
    });

    it('should handle invalid dates gracefully', async () => {
      const functionRegistry = ruleFlow['functionRegistry'];
      
      expect(() => {
        functionRegistry.call('is_business_day', ['not-a-date']);
      }).toThrow();
    });

    it('should handle invalid business parameters', async () => {
      const functionRegistry = ruleFlow['functionRegistry'];
      
      expect(() => {
        functionRegistry.call('shipping_cost', [-5, 100, 'standard']);
      }).toThrow();
    });

    it('should handle invalid education parameters', async () => {
      const functionRegistry = ruleFlow['functionRegistry'];
      
      expect(() => {
        functionRegistry.call('academic_standing', [5.0]); // Invalid GPA > 4.0
      }).toThrow();
    });
  });
});