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
        id: 'base_rate',
        switch: '$credit_score',
        when: [
          { if: { op: '>=', value: 750 }, result: 3.5 },
          { if: { op: '>=', value: 700 }, result: 4.0 },
          { if: { op: '>=', value: 650 }, result: 5.0 }
        ],
        default: 7.0
      },
      {
        id: 'credit_tier',
        switch: '$credit_score',
        when: [
          { if: { op: '>=', value: 750 }, result: 'excellent' },
          { if: { op: '>=', value: 700 }, result: 'good' },
          { if: { op: '>=', value: 650 }, result: 'fair' }
        ],
        default: 'poor'
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
            result: 'approved'
          },
          {
            if: {
              and: [
                { op: '<', var: 'debt_to_income_ratio', value: 0.5 },
                { op: '>=', var: 'credit_score', value: 600 }
              ]
            },
            result: 'conditional'
          }
        ],
        default: 'rejected'
      },
      {
        id: 'interest_rate',
        switch: '$loan_decision',
        when: [
          // ✅ ตอนนี้ '$base_rate' จะถูก resolve เป็นค่าจริง (3.5) แล้ว
          { if: { op: '==', value: 'approved' }, result: '$base_rate' },
          // ✅ Expression '$base_rate + 1.5' จะถูก evaluate ให้ (3.5 + 1.5 = 5.0)
          { if: { op: '==', value: 'conditional' }, result: '$base_rate + 1.5' }
        ],
        default: 0
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
    outputs: ['debt_to_income_ratio', 'base_rate', 'credit_tier', 'loan_decision', 'interest_rate']
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
        debt_to_income_ratio: 0.25,
        base_rate: 3.5,
        credit_tier: 'excellent',
        loan_decision: 'approved', 
        interest_rate: 3.5 // ✅ ตอนนี้จะได้ 3.5 แทน '$base_rate'
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
          { if: { op: '<=', value: 0.1 }, result: 100 },
          { if: { op: '<=', value: 0.3 }, result: 80 },
          { if: { op: '<=', value: 0.5 }, result: 60 },
          { if: { op: '<=', value: 0.7 }, result: 40 }
        ],
        default: 20
      },
      {
        id: 'credit_score',
        formula: '(payment_history_score * 0.35) + (credit_utilization_score * 0.30) + (credit_age_months * 0.15) + (new_credit_score * 0.10) + (credit_mix_score * 0.10)',
        inputs: ['payment_history_score', 'credit_utilization_score', 'credit_age_months', 'new_credit_score', 'credit_mix_score']      },
      {
        id: 'credit_rating',
        switch: '$credit_score',
        when: [
          { if: { op: '>=', value: 750 }, result: 'Excellent' },
          { if: { op: '>=', value: 700 }, result: 'Good' },
          { if: { op: '>=', value: 650 }, result: 'Fair' },
          { if: { op: '>=', value: 600 }, result: 'Poor' }
        ],
        default: 'Very Poor'
      }
    ]
  },
  metadata: {
    name: 'Credit Scoring System',
    description: 'Comprehensive credit score calculation based on multiple factors',
    category: 'financial',
    author: 'RuleFlow Team',
    version: '2.0.0',
    tags: ['credit', 'scoring', 'risk', 'assessment'],
    difficulty: 'advanced',
    estimatedTime: '15 minutes',
    inputs: ['late_payments_12m', 'missed_payments', 'credit_utilization_ratio', 'credit_length_months', 'new_credit_score', 'credit_mix_score'],
    outputs: ['payment_history_score', 'credit_utilization_score', 'credit_score', 'credit_rating']
  },
  examples: [
    {
      name: 'Good credit',
      description: 'Person with good credit history',
      inputs: { 
        late_payments_12m: 1,
        missed_payments: 0,
        credit_utilization_ratio: 0.25,
        credit_length_months: 48,
        new_credit_score: 80,
        credit_mix_score: 85
      },
      expectedOutputs: { 
        payment_history_score: 80,
        credit_utilization_score: 80,
        credit_score: 158,
        credit_rating: 'Poor'
      }
    }
  ]
};