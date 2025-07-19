import { Template } from '../types.js';

export const LOAN_APPROVAL: Template = {
  config: {
    formulas: [
      {
        id: 'debt_to_income_ratio',
        formula: 'monthly_debt / monthly_income',
        inputs: ['monthly_debt', 'monthly_income']
      },
      {
        id: 'credit_tier',
        switch: '$credit_score',
        when: [
          { if: { op: '>=', value: 750 }, result: 'excellent', set_vars: { '$base_rate': 3.5 } },
          { if: { op: '>=', value: 700 }, result: 'good', set_vars: { '$base_rate': 4.0 } },
          { if: { op: '>=', value: 650 }, result: 'fair', set_vars: { '$base_rate': 5.0 } }
        ],
        default: 'poor',
        set_vars: { '$base_rate': 7.0 }
      },
      {
        id: 'loan_decision',
        switch: 'trigger',
        when: [
          {
            if: {
              and: [
                { op: '<', var: 'debt_to_income_ratio', value: 0.4 },
                { op: '>=', var: 'credit_score', value: 650 },
                { op: '>=', var: 'employment_years', value: 2 }
              ]
            },
            result: 'approved',
            set_vars: {
              '$interest_rate': '$base_rate',
              '$max_amount': 'monthly_income * 60'
            }
          },
          {
            if: {
              and: [
                { op: '<', var: 'debt_to_income_ratio', value: 0.5 },
                { op: '>=', var: 'credit_score', value: 600 }
              ]
            },
            result: 'conditional',
            set_vars: {
              '$interest_rate': '$base_rate + 1.5',
              '$max_amount': 'monthly_income * 40'
            }
          }
        ],
        default: 'rejected',
        set_vars: { '$interest_rate': 0, '$max_amount': 0 }
      }
    ]
  },
  metadata: {
    name: 'Loan Approval System',
    description: 'Loan approval with credit scoring and risk assessment',
    category: 'financial',
    author: 'RuleFlow Team',
    version: '1.2.0',
    tags: ['loan', 'credit', 'approval', 'banking'],
    difficulty: 'intermediate',
    estimatedTime: '10 minutes',
    inputs: ['monthly_income', 'monthly_debt', 'credit_score', 'employment_years'],
    outputs: ['debt_to_income_ratio', 'credit_tier', 'loan_decision', 'interest_rate', 'max_amount']
  },
  examples: [
    {
      name: 'Approved loan',
      description: 'High-quality borrower',
      inputs: { 
        monthly_income: 8000, 
        monthly_debt: 2000, 
        credit_score: 780, 
        employment_years: 5,
        trigger: 'evaluate'
      },
      expectedOutputs: { 
        loan_decision: 'approved', 
        interest_rate: 3.5
      }
    },
    {
      name: 'Rejected loan',
      description: 'High debt ratio',
      inputs: { 
        monthly_income: 4000, 
        monthly_debt: 2500, 
        credit_score: 580, 
        employment_years: 1,
        trigger: 'evaluate'
      },
      expectedOutputs: { 
        loan_decision: 'rejected', 
        interest_rate: 0
      }
    }
  ]
};

export const CREDIT_SCORING: Template = {
  config: {
    formulas: [
      {
        id: 'payment_history_score',
        rules: [
          { var: 'late_payments_12m', ranges: [
            { if: { op: '==', value: 0 }, score: 100 },
            { if: { op: '<=', value: 2 }, score: 80 },
            { if: { op: '<=', value: 4 }, score: 60 }
          ]},
          { var: 'missed_payments', ranges: [
            { if: { op: '==', value: 0 }, score: 100 },
            { if: { op: '<=', value: 1 }, score: 50 }
          ]}
        ]
      },
      {
        id: 'credit_utilization_score',
        switch: '$credit_utilization_ratio',
        when: [
          { if: { op: '<', value: 0.1 }, result: 100 },
          { if: { op: '<', value: 0.3 }, result: 80 },
          { if: { op: '<', value: 0.5 }, result: 60 }
        ],
        default: 20
      },
      {
        id: 'credit_score',
        formula: '(payment_history_score * 0.35) + (credit_utilization_score * 0.30) + (credit_age_months * 0.15) + (credit_mix_score * 0.10) + (new_credit_score * 0.10)'
      },
      {
        id: 'credit_rating',
        switch: '$credit_score',
        when: [
          { if: { op: '>=', value: 800 }, result: 'Exceptional' },
          { if: { op: '>=', value: 740 }, result: 'Very Good' },
          { if: { op: '>=', value: 670 }, result: 'Good' },
          { if: { op: '>=', value: 580 }, result: 'Fair' }
        ],
        default: 'Poor'
      }
    ]
  },
  metadata: {
    name: 'Credit Scoring Model',
    description: 'FICO-style credit score calculation',
    category: 'financial',
    author: 'RuleFlow Team',
    version: '1.0.0',
    tags: ['credit', 'score', 'fico', 'rating'],
    difficulty: 'advanced',
    estimatedTime: '15 minutes',
    inputs: ['late_payments_12m', 'missed_payments', 'credit_utilization_ratio', 'credit_age_months', 'credit_mix_score', 'new_credit_score'],
    outputs: ['payment_history_score', 'credit_utilization_score', 'credit_score', 'credit_rating']
  },
  examples: [
    {
      name: 'Excellent credit',
      description: 'Perfect payment history',
      inputs: { 
        late_payments_12m: 0, 
        missed_payments: 0, 
        credit_utilization_ratio: 0.05, 
        credit_age_months: 120, 
        credit_mix_score: 85, 
        new_credit_score: 90 
      },
      expectedOutputs: { 
        credit_score: 820, 
        credit_rating: 'Exceptional' 
      }
    }
  ]
};