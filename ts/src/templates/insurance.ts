import { Template } from '../types';

export const AUTO_INSURANCE_RISK: Template = {
  config: {
    formulas: [
      {
        id: 'age_risk_factor',
        switch: '$driver_age',
        when: [
          { if: { op: '<', value: 25 }, result: 1.5 },
          { if: { op: '<', value: 35 }, result: 1.2 },
          { if: { op: '<=', value: 65 }, result: 1.0 }
        ],
        default: 1.3
      },
      {
        id: 'experience_factor',
        switch: '$driving_years',
        when: [
          { if: { op: '<', value: 3 }, result: 1.4 },
          { if: { op: '<', value: 10 }, result: 1.1 },
          { if: { op: '>=', value: 10 }, result: 0.9 }
        ],
        default: 1.0
      },
      {
        id: 'accident_factor',
        switch: '$accidents_5_years',
        when: [
          { if: { op: '==', value: 0 }, result: 0.8 },
          { if: { op: '==', value: 1 }, result: 1.2 },
          { if: { op: '>=', value: 2 }, result: 1.8 }
        ],
        default: 1.0
      },
      {
        id: 'vehicle_factor',
        switch: '$vehicle_type',
        when: [
          { if: { op: '==', value: 'sports_car' }, result: 2.0 },
          { if: { op: '==', value: 'luxury' }, result: 1.5 },
          { if: { op: '==', value: 'suv' }, result: 1.2 },
          { if: { op: '==', value: 'sedan' }, result: 1.0 },
          { if: { op: '==', value: 'compact' }, result: 0.9 }
        ],
        default: 1.0
      },
      {
        id: 'base_premium',
        formula: 'base_rate * age_risk_factor * experience_factor * accident_factor * vehicle_factor',
        inputs: ['base_rate']
      },
      {
        id: 'risk_category',
        switch: '$base_premium',
        when: [
          { if: { op: '>=', value: 2000 }, result: 'High Risk', set_vars: { '$approval_required': true } },
          { if: { op: '>=', value: 1200 }, result: 'Medium Risk', set_vars: { '$approval_required': false } },
          { if: { op: '>=', value: 800 }, result: 'Standard Risk', set_vars: { '$approval_required': false } }
        ],
        default: 'Low Risk',
        set_vars: { '$approval_required': false }
      },
      {
        id: 'final_premium',
        switch: 'calculate_discounts',
        when: [
          {
            if: {
              and: [
                { op: '==', var: 'good_student', value: true },
                { op: '<', var: 'driver_age', value: 25 }
              ]
            },
            result: 'base_premium * 0.9',
            set_vars: { '$discount_applied': 'Good Student Discount' }
          },
          {
            if: {
              and: [
                { op: '==', var: 'safe_driver', value: true },
                { op: '==', var: 'accidents_5_years', value: 0 }
              ]
            },
            result: 'base_premium * 0.85',
            set_vars: { '$discount_applied': 'Safe Driver Discount' }
          }
        ],
        default: 'base_premium',
        set_vars: { '$discount_applied': 'None' }
      }
    ]
  },
  metadata: {
    name: 'Auto Insurance Risk Assessment',
    description: 'Calculate auto insurance premiums based on driver and vehicle risk factors',
    category: 'insurance',
    author: 'RuleFlow Team',
    version: '1.0.0',
    tags: ['insurance', 'auto', 'risk', 'premium', 'assessment'],
    difficulty: 'intermediate',
    estimatedTime: '12 minutes',
    inputs: ['base_rate', 'driver_age', 'driving_years', 'accidents_5_years', 'vehicle_type', 'good_student', 'safe_driver'],
    outputs: ['age_risk_factor', 'base_premium', 'risk_category', 'final_premium', 'discount_applied']
  },
  examples: [
    {
      name: 'Young safe driver',
      description: 'Young driver with good student discount',
      inputs: { 
        base_rate: 800, 
        driver_age: 20, 
        driving_years: 2, 
        accidents_5_years: 0, 
        vehicle_type: 'compact',
        good_student: true,
        safe_driver: true,
        calculate_discounts: true
      },
      expectedOutputs: { 
        risk_category: 'Standard Risk', 
        final_premium: 1209.6, // 800 * 1.5 * 1.4 * 0.8 * 0.9 * 0.9
        discount_applied: 'Good Student Discount'
      }
    },
    {
      name: 'High risk driver',
      description: 'Sports car with accident history',
      inputs: { 
        base_rate: 800, 
        driver_age: 30, 
        driving_years: 8, 
        accidents_5_years: 2, 
        vehicle_type: 'sports_car',
        good_student: false,
        safe_driver: false,
        calculate_discounts: true
      },
      expectedOutputs: { 
        risk_category: 'High Risk', 
        final_premium: 3801.6, // 800 * 1.2 * 1.1 * 1.8 * 2.0
        approval_required: true
      }
    }
  ]
};

export const LIFE_INSURANCE_UNDERWRITING: Template = {
  config: {
    formulas: [
      {
        id: 'age_factor',
        switch: '$age',
        when: [
          { if: { op: '<=', value: 30 }, result: 1.0 },
          { if: { op: '<=', value: 40 }, result: 1.2 },
          { if: { op: '<=', value: 50 }, result: 1.5 },
          { if: { op: '<=', value: 60 }, result: 2.0 }
        ],
        default: 3.0
      },
      {
        id: 'health_score',
        rules: [
          { var: 'bmi', ranges: [
            { if: { op: '<', value: 18.5 }, score: -10 },
            { if: { op: '<', value: 25 }, score: 0 },
            { if: { op: '<', value: 30 }, score: -5 },
            { if: { op: '>=', value: 30 }, score: -15 }
          ]},
          { var: 'smoking', if: { op: '==', value: true }, score: -25 },
          { var: 'exercise_weekly', ranges: [
            { if: { op: '>=', value: 4 }, score: 10 },
            { if: { op: '>=', value: 2 }, score: 5 }
          ]},
          { var: 'chronic_conditions', if: { op: '>', value: 0 }, score: -20 }
        ]
      },
      {
        id: 'lifestyle_factor',
        switch: '$health_score',
        when: [
          { if: { op: '>=', value: 10 }, result: 0.8 },
          { if: { op: '>=', value: 0 }, result: 1.0 },
          { if: { op: '>=', value: -15 }, result: 1.3 },
          { if: { op: '>=', value: -30 }, result: 1.8 }
        ],
        default: 2.5
      },
      {
        id: 'occupation_factor',
        switch: '$occupation_risk',
        when: [
          { if: { op: '==', value: 'low' }, result: 1.0 },
          { if: { op: '==', value: 'medium' }, result: 1.2 },
          { if: { op: '==', value: 'high' }, result: 1.5 },
          { if: { op: '==', value: 'very_high' }, result: 2.0 }
        ],
        default: 1.0
      },
      {
        id: 'base_premium',
        formula: 'coverage_amount / 1000 * age_factor * lifestyle_factor * occupation_factor',
        inputs: ['coverage_amount']
      },
      {
        id: 'underwriting_decision',
        switch: 'evaluate',
        when: [
          {
            if: {
              and: [
                { op: '<', var: 'base_premium', value: 500 },
                { op: '>', var: 'health_score', value: -10 },
                { op: '<', var: 'age', value: 65 }
              ]
            },
            result: 'Standard',
            set_vars: { '$requires_medical_exam': false }
          },
          {
            if: {
              or: [
                { op: '>', var: 'base_premium', value: 1000 },
                { op: '<', var: 'health_score', value: -20 },
                { op: '>', var: 'age', value: 70 }
              ]
            },
            result: 'Declined',
            set_vars: { '$requires_medical_exam': false, '$decline_reason': 'High risk factors' }
          }
        ],
        default: 'Substandard',
        set_vars: { '$requires_medical_exam': true, '$additional_premium': 'base_premium * 0.5' }
      }
    ]
  },
  metadata: {
    name: 'Life Insurance Underwriting',
    description: 'Life insurance risk assessment and premium calculation',
    category: 'insurance',
    author: 'RuleFlow Team',
    version: '1.0.0',
    tags: ['insurance', 'life', 'underwriting', 'health', 'risk'],
    difficulty: 'advanced',
    estimatedTime: '20 minutes',
    inputs: ['age', 'bmi', 'smoking', 'exercise_weekly', 'chronic_conditions', 'occupation_risk', 'coverage_amount'],
    outputs: ['age_factor', 'health_score', 'base_premium', 'underwriting_decision', 'requires_medical_exam']
  },
  examples: [
    {
      name: 'Healthy young applicant',
      description: 'Low risk life insurance applicant',
      inputs: { 
        age: 30, 
        bmi: 23, 
        smoking: false, 
        exercise_weekly: 4, 
        chronic_conditions: 0, 
        occupation_risk: 'low',
        coverage_amount: 500000,
        evaluate: true
      },
      expectedOutputs: { 
        underwriting_decision: 'Standard',
        health_score: 10,
        requires_medical_exam: false
      }
    },
    {
      name: 'High risk applicant',
      description: 'Older smoker with health issues',
      inputs: { 
        age: 55, 
        bmi: 32, 
        smoking: true, 
        exercise_weekly: 0, 
        chronic_conditions: 2, 
        occupation_risk: 'medium',
        coverage_amount: 1000000,
        evaluate: true
      },
      expectedOutputs: { 
        underwriting_decision: 'Declined',
        health_score: -60,
        decline_reason: 'High risk factors'
      }
    }
  ]
};