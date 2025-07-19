import { Template } from '../types';

export const BMI_CALCULATOR: Template = {
  config: {
    formulas: [
      {
        id: 'bmi',
        formula: 'weight / (height ** 2)',
        inputs: ['weight', 'height']
      },
      {
        id: 'bmi_category',
        switch: '$bmi',
        when: [
          { 
            if: { op: '<', value: 18.5 }, 
            result: 'Underweight',
            set_vars: { 
              '$risk_level': 'low',
              '$recommendation': 'Consider weight gain under medical supervision'
            }
          },
          { 
            if: { op: '<', value: 25 }, 
            result: 'Normal',
            set_vars: { 
              '$risk_level': 'none',
              '$recommendation': 'Maintain current weight'
            }
          },
          { 
            if: { op: '<', value: 30 }, 
            result: 'Overweight',
            set_vars: { 
              '$risk_level': 'medium',
              '$recommendation': 'Consider diet and exercise plan'
            }
          }
        ],
        default: 'Obese',
        set_vars: { 
          '$risk_level': 'high',
          '$recommendation': 'Consult healthcare provider'
        }
      },
      {
        id: 'health_score',
        formula: 'bmi < 18.5 ? 70 : (bmi < 25 ? 100 : (bmi < 30 ? 75 : 50))'
      }
    ]
  },
  metadata: {
    name: 'BMI Calculator',
    description: 'Calculate Body Mass Index with health categories',
    category: 'healthcare',
    author: 'RuleFlow Team',
    version: '1.0.0',
    tags: ['bmi', 'health', 'fitness', 'weight'],
    difficulty: 'beginner',
    estimatedTime: '2 minutes',
    inputs: ['weight', 'height'],
    outputs: ['bmi', 'bmi_category', 'health_score', 'risk_level', 'recommendation']
  },
  examples: [
    {
      name: 'Normal weight',
      description: 'Healthy adult',
      inputs: { weight: 70, height: 1.75 },
      expectedOutputs: { 
        bmi: 22.86, 
        bmi_category: 'Normal', 
        health_score: 100
      }
    },
    {
      name: 'Overweight',
      description: 'Person with overweight BMI',
      inputs: { weight: 85, height: 1.70 },
      expectedOutputs: { 
        bmi: 29.41, 
        bmi_category: 'Overweight', 
        health_score: 75
      }
    }
  ]
};

export const HEALTH_RISK_ASSESSMENT: Template = {
  config: {
    formulas: [
      {
        id: 'risk_factors',
        rules: [
          { var: 'age', ranges: [
            { if: { op: '>', value: 65 }, score: 10 },
            { if: { op: '>', value: 50 }, score: 5 },
            { if: { op: '>', value: 35 }, score: 2 }
          ]},
          { var: 'smoking', if: { op: '==', value: true }, score: 15 },
          { var: 'exercise_hours_week', ranges: [
            { if: { op: '<', value: 2 }, score: 8 },
            { if: { op: '<', value: 4 }, score: 4 }
          ]},
          { var: 'family_history', if: { op: '==', value: true }, score: 8 }
        ]
      },
      {
        id: 'risk_level',
        switch: '$risk_factors',
        when: [
          { if: { op: '<', value: 10 }, result: 'Low Risk' },
          { if: { op: '<', value: 20 }, result: 'Medium Risk' },
          { if: { op: '<', value: 30 }, result: 'High Risk' }
        ],
        default: 'Very High Risk'
      }
    ]
  },
  metadata: {
    name: 'Health Risk Assessment',
    description: 'Health risk evaluation based on lifestyle factors',
    category: 'healthcare',
    author: 'RuleFlow Team',
    version: '1.1.0',
    tags: ['health', 'risk', 'assessment', 'lifestyle'],
    difficulty: 'intermediate',
    estimatedTime: '5 minutes',
    inputs: ['age', 'smoking', 'exercise_hours_week', 'family_history'],
    outputs: ['risk_factors', 'risk_level']
  },
  examples: [
    {
      name: 'Low risk',
      description: 'Young, healthy lifestyle',
      inputs: { age: 25, smoking: false, exercise_hours_week: 5, family_history: false },
      expectedOutputs: { risk_factors: 0, risk_level: 'Low Risk' }
    },
    {
      name: 'High risk',
      description: 'Multiple risk factors',
      inputs: { age: 60, smoking: true, exercise_hours_week: 1, family_history: true },
      expectedOutputs: { risk_factors: 28, risk_level: 'High Risk' }
    }
  ]
};