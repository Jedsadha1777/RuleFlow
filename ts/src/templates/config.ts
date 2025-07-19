//  เพิ่ม/ลบ template ที่นี่

import { BMI_CALCULATOR, HEALTH_RISK_ASSESSMENT } from './healthcare';
import { LOAN_APPROVAL, CREDIT_SCORING } from './financial';
import { EMPLOYEE_PERFORMANCE, SALARY_CALCULATOR } from './hr';
import { DYNAMIC_PRICING, SHIPPING_CALCULATOR } from './ecommerce';
import { AUTO_INSURANCE_RISK, LIFE_INSURANCE_UNDERWRITING } from './insurance';
import { GRADE_CALCULATOR, SCHOLARSHIP_ELIGIBILITY } from './education';
import { PROPERTY_VALUATION, MORTGAGE_QUALIFICATION } from './real-estate';

//  Template Registry - เพิ่ม/ลบ ที่นี่
export const TEMPLATES = {

  // Healthcare
  'bmi_calculator': BMI_CALCULATOR,
  'health_risk_assessment': HEALTH_RISK_ASSESSMENT,
  
  // Financial
  'loan_approval': LOAN_APPROVAL,
  'credit_scoring': CREDIT_SCORING,
  
  // HR
  'employee_performance': EMPLOYEE_PERFORMANCE,
  'salary_calculator': SALARY_CALCULATOR,
  
  // E-commerce
  'dynamic_pricing': DYNAMIC_PRICING,
  'shipping_calculator': SHIPPING_CALCULATOR,
  
  // Insurance
  'auto_insurance_risk': AUTO_INSURANCE_RISK,
  'life_insurance_underwriting': LIFE_INSURANCE_UNDERWRITING,
  
  // Education
  'grade_calculator': GRADE_CALCULATOR,
  'scholarship_eligibility': SCHOLARSHIP_ELIGIBILITY,
  
  // Real Estate
  'property_valuation': PROPERTY_VALUATION,
  'mortgage_qualification': MORTGAGE_QUALIFICATION,

  // เพิ่ม templates ใหม่ที่นี่...
};

// Categories
export const CATEGORIES = {
  'healthcare': ['bmi_calculator', 'health_risk_assessment'],
  'financial': ['loan_approval', 'credit_scoring'],
  'hr': ['employee_performance', 'salary_calculator'],
  'ecommerce': ['dynamic_pricing', 'shipping_calculator'],
  'insurance': ['auto_insurance_risk', 'life_insurance_underwriting'],
  'education': ['grade_calculator', 'scholarship_eligibility'],
  'real-estate': ['property_valuation', 'mortgage_qualification'],
  // เพิ่ม categories ใหม่ที่นี่...
};

export type TemplateCategory = keyof typeof CATEGORIES;
export type TemplateName = keyof typeof TEMPLATES;


export const TEMPLATE_STATS = {
  total: Object.keys(TEMPLATES).length,
  byCategory: Object.fromEntries(
    Object.entries(CATEGORIES).map(([cat, templates]) => [cat, templates.length])
  ),
  byDifficulty: {
    beginner: Object.values(TEMPLATES).filter(t => t.metadata.difficulty === 'beginner').length,
    intermediate: Object.values(TEMPLATES).filter(t => t.metadata.difficulty === 'intermediate').length,
    advanced: Object.values(TEMPLATES).filter(t => t.metadata.difficulty === 'advanced').length,
  }
};