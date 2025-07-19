import { Template } from '../types';

export const PROPERTY_VALUATION: Template = {
  config: {
    formulas: [
      {
        id: 'size_value',
        formula: 'square_feet * price_per_sqft',
        inputs: ['square_feet', 'price_per_sqft']
      },
      {
        id: 'location_multiplier',
        switch: '$location_rating',
        when: [
          { if: { op: '==', value: 'Prime' }, result: 1.3 },
          { if: { op: '==', value: 'Excellent' }, result: 1.2 },
          { if: { op: '==', value: 'Good' }, result: 1.1 },
          { if: { op: '==', value: 'Average' }, result: 1.0 },
          { if: { op: '==', value: 'Below Average' }, result: 0.9 }
        ],
        default: 1.0
      },
      {
        id: 'condition_multiplier',
        switch: '$property_condition',
        when: [
          { if: { op: '==', value: 'Excellent' }, result: 1.15 },
          { if: { op: '==', value: 'Good' }, result: 1.05 },
          { if: { op: '==', value: 'Fair' }, result: 1.0 },
          { if: { op: '==', value: 'Poor' }, result: 0.85 }
        ],
        default: 1.0
      },
      {
        id: 'age_factor',
        switch: '$property_age',
        when: [
          { if: { op: '<=', value: 5 }, result: 1.1 },
          { if: { op: '<=', value: 15 }, result: 1.0 },
          { if: { op: '<=', value: 30 }, result: 0.95 },
          { if: { op: '<=', value: 50 }, result: 0.9 }
        ],
        default: 0.8
      },
      {
        id: 'amenities_score',
        rules: [
          { var: 'garage_spaces', ranges: [
            { if: { op: '>=', value: 3 }, score: 15000 },
            { if: { op: '>=', value: 2 }, score: 10000 },
            { if: { op: '>=', value: 1 }, score: 5000 }
          ]},
          { var: 'pool', if: { op: '==', value: true }, score: 20000 },
          { var: 'fireplace', if: { op: '==', value: true }, score: 8000 },
          { var: 'updated_kitchen', if: { op: '==', value: true }, score: 15000 },
          { var: 'master_suite', if: { op: '==', value: true }, score: 12000 }
        ]
      },
      {
        id: 'market_adjustment',
        switch: '$market_trend',
        when: [
          { if: { op: '==', value: 'hot' }, result: 1.15 },
          { if: { op: '==', value: 'strong' }, result: 1.08 },
          { if: { op: '==', value: 'stable' }, result: 1.0 },
          { if: { op: '==', value: 'slow' }, result: 0.95 },
          { if: { op: '==', value: 'declining' }, result: 0.9 }
        ],
        default: 1.0
      },
      {
        id: 'base_value',
        formula: 'size_value * location_multiplier * condition_multiplier * age_factor'
      },
      {
        id: 'estimated_value',
        formula: '(base_value + amenities_score) * market_adjustment'
      },
      {
        id: 'value_category',
        switch: '$estimated_value',
        when: [
          { if: { op: '>=', value: 1000000 }, result: 'Luxury', set_vars: { '$property_class': 'High-end' } },
          { if: { op: '>=', value: 500000 }, result: 'Premium', set_vars: { '$property_class': 'Upper-middle' } },
          { if: { op: '>=', value: 250000 }, result: 'Standard', set_vars: { '$property_class': 'Middle-market' } },
          { if: { op: '>=', value: 150000 }, result: 'Affordable', set_vars: { '$property_class': 'Entry-level' } }
        ],
        default: 'Budget',
        set_vars: { '$property_class': 'Low-end' }
      },
      {
        id: 'investment_analysis',
        switch: 'analyze_investment',
        when: [
          {
            if: {
              and: [
                { op: '>', var: 'estimated_value', value: 200000 },
                { op: '==', var: 'location_rating', value: 'Excellent' },
                { op: '==', var: 'market_trend', value: 'strong' }
              ]
            },
            result: 'Excellent Investment',
            set_vars: { 
              '$investment_score': 90,
              '$expected_appreciation': '8-12% annually'
            }
          },
          {
            if: {
              or: [
                { op: '==', var: 'market_trend', value: 'declining' },
                { op: '==', var: 'property_condition', value: 'Poor' },
                { op: '>', var: 'property_age', value: 50 }
              ]
            },
            result: 'High Risk Investment',
            set_vars: { 
              '$investment_score': 30,
              '$expected_appreciation': '0-3% annually'
            }
          }
        ],
        default: 'Moderate Investment',
        set_vars: { 
          '$investment_score': 65,
          '$expected_appreciation': '4-7% annually'
        }
      }
    ]
  },
  metadata: {
    name: 'Property Valuation Model',
    description: 'Comprehensive property valuation with market analysis and investment scoring',
    category: 'real-estate',
    author: 'RuleFlow Team',
    version: '1.0.0',
    tags: ['real-estate', 'valuation', 'property', 'investment', 'market'],
    difficulty: 'advanced',
    estimatedTime: '20 minutes',
    inputs: ['square_feet', 'price_per_sqft', 'location_rating', 'property_condition', 'property_age', 'garage_spaces', 'pool', 'fireplace', 'updated_kitchen', 'master_suite', 'market_trend'],
    outputs: ['size_value', 'base_value', 'estimated_value', 'value_category', 'investment_analysis', 'investment_score']
  },
  examples: [
    {
      name: 'Luxury property',
      description: 'High-end property in prime location',
      inputs: { 
        square_feet: 3500, 
        price_per_sqft: 300, 
        location_rating: 'Prime',
        property_condition: 'Excellent',
        property_age: 3,
        garage_spaces: 3,
        pool: true,
        fireplace: true,
        updated_kitchen: true,
        master_suite: true,
        market_trend: 'hot',
        analyze_investment: true
      },
      expectedOutputs: { 
        estimated_value: 1725000,
        value_category: 'Luxury',
        investment_analysis: 'Excellent Investment',
        investment_score: 90
      }
    },
    {
      name: 'Starter home',
      description: 'Affordable property for first-time buyers',
      inputs: { 
        square_feet: 1200, 
        price_per_sqft: 150, 
        location_rating: 'Good',
        property_condition: 'Fair',
        property_age: 25,
        garage_spaces: 1,
        pool: false,
        fireplace: false,
        updated_kitchen: false,
        master_suite: false,
        market_trend: 'stable',
        analyze_investment: true
      },
      expectedOutputs: { 
        estimated_value: 198000,
        value_category: 'Affordable',
        investment_analysis: 'Moderate Investment',
        investment_score: 65
      }
    }
  ]
};

export const MORTGAGE_QUALIFICATION: Template = {
  config: {
    formulas: [
      {
        id: 'monthly_gross_income',
        formula: 'annual_income / 12',
        inputs: ['annual_income']
      },
      {
        id: 'debt_to_income_ratio',
        formula: '(monthly_debt_payments + estimated_monthly_payment) / monthly_gross_income',
        inputs: ['monthly_debt_payments', 'estimated_monthly_payment']
      },
      {
        id: 'loan_to_value_ratio',
        formula: 'loan_amount / property_value',
        inputs: ['loan_amount', 'property_value']
      },
      {
        id: 'credit_tier',
        switch: '$credit_score',
        when: [
          { if: { op: '>=', value: 760 }, result: 'Excellent', set_vars: { '$interest_rate': 6.5 } },
          { if: { op: '>=', value: 700 }, result: 'Good', set_vars: { '$interest_rate': 7.0 } },
          { if: { op: '>=', value: 650 }, result: 'Fair', set_vars: { '$interest_rate': 7.5 } },
          { if: { op: '>=', value: 600 }, result: 'Poor', set_vars: { '$interest_rate': 8.5 } }
        ],
        default: 'Very Poor',
        set_vars: { '$interest_rate': 10.0 }
      },
      {
        id: 'down_payment_percentage',
        formula: 'down_payment / property_value',
        inputs: ['down_payment', 'property_value']
      },
      {
        id: 'qualification_status',
        switch: 'evaluate_qualification',
        when: [
          {
            if: {
              and: [
                { op: '<=', var: 'debt_to_income_ratio', value: 0.28 },
                { op: '>=', var: 'credit_score', value: 700 },
                { op: '>=', var: 'down_payment_percentage', value: 0.20 },
                { op: '>=', var: 'employment_years', value: 2 }
              ]
            },
            result: 'Fully Qualified',
            set_vars: { 
              '$approval_probability': 95,
              '$loan_terms': 'Excellent terms available'
            }
          },
          {
            if: {
              and: [
                { op: '<=', var: 'debt_to_income_ratio', value: 0.36 },
                { op: '>=', var: 'credit_score', value: 650 },
                { op: '>=', var: 'down_payment_percentage', value: 0.10 }
              ]
            },
            result: 'Conditionally Qualified',
            set_vars: { 
              '$approval_probability': 75,
              '$loan_terms': 'Standard terms with conditions'
            }
          },
          {
            if: {
              or: [
                { op: '>', var: 'debt_to_income_ratio', value: 0.43 },
                { op: '<', var: 'credit_score', value: 580 },
                { op: '<', var: 'down_payment_percentage', value: 0.03 }
              ]
            },
            result: 'Not Qualified',
            set_vars: { 
              '$approval_probability': 10,
              '$loan_terms': 'Improvement needed before qualification'
            }
          }
        ],
        default: 'Requires Review',
        set_vars: { 
          '$approval_probability': 50,
          '$loan_terms': 'Manual underwriting required'
        }
      },
      {
        id: 'recommended_actions',
        switch: '$qualification_status',
        when: [
          { 
            if: { op: '==', value: 'Not Qualified' }, 
            result: 'Improve credit score, reduce debt, increase down payment',
            set_vars: { '$next_steps': 'Wait 6-12 months before reapplying' }
          },
          { 
            if: { op: '==', value: 'Conditionally Qualified' }, 
            result: 'Provide additional documentation, consider mortgage insurance',
            set_vars: { '$next_steps': 'Proceed with application' }
          },
          { 
            if: { op: '==', value: 'Fully Qualified' }, 
            result: 'Proceed with loan application immediately',
            set_vars: { '$next_steps': 'Shop for best rates' }
          }
        ],
        default: 'Consult with loan officer for detailed review',
        set_vars: { '$next_steps': 'Schedule consultation' }
      }
    ]
  },
  metadata: {
    name: 'Mortgage Qualification Assessment',
    description: 'Comprehensive mortgage pre-qualification with recommendations',
    category: 'real-estate',
    author: 'RuleFlow Team',
    version: '1.0.0',
    tags: ['mortgage', 'qualification', 'lending', 'real-estate', 'finance'],
    difficulty: 'intermediate',
    estimatedTime: '12 minutes',
    inputs: ['annual_income', 'monthly_debt_payments', 'estimated_monthly_payment', 'loan_amount', 'property_value', 'credit_score', 'down_payment', 'employment_years'],
    outputs: ['debt_to_income_ratio', 'loan_to_value_ratio', 'qualification_status', 'approval_probability', 'recommended_actions']
  },
  examples: [
    {
      name: 'Qualified buyer',
      description: 'Well-qualified homebuyer',
      inputs: { 
        annual_income: 100000, 
        monthly_debt_payments: 800, 
        estimated_monthly_payment: 1800,
        loan_amount: 400000,
        property_value: 500000,
        credit_score: 750,
        down_payment: 100000,
        employment_years: 5,
        evaluate_qualification: true
      },
      expectedOutputs: { 
        qualification_status: 'Fully Qualified',
        debt_to_income_ratio: 0.312,
        approval_probability: 95,
        recommended_actions: 'Proceed with loan application immediately'
      }
    },
    {
      name: 'Marginal buyer',
      description: 'Buyer needing improvement',
      inputs: { 
        annual_income: 60000, 
        monthly_debt_payments: 1200, 
        estimated_monthly_payment: 1400,
        loan_amount: 200000,
        property_value: 220000,
        credit_score: 620,
        down_payment: 20000,
        employment_years: 1,
        evaluate_qualification: true
      },
      expectedOutputs: { 
        qualification_status: 'Not Qualified',
        debt_to_income_ratio: 0.52,
        approval_probability: 10,
        recommended_actions: 'Improve credit score, reduce debt, increase down payment'
      }
    }
  ]
};