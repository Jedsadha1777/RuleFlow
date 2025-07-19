import { Template } from '../types';

export const EMPLOYEE_PERFORMANCE: Template = {
  config: {
    formulas: [
      {
        id: 'technical_score',
        rules: [
          { var: 'coding_quality', ranges: [
            { if: { op: '>=', value: 90 }, score: 25 },
            { if: { op: '>=', value: 80 }, score: 20 },
            { if: { op: '>=', value: 70 }, score: 15 }
          ], weight: 1.2 },
          { var: 'problem_solving', ranges: [
            { if: { op: '>=', value: 85 }, score: 20 },
            { if: { op: '>=', value: 75 }, score: 15 },
            { if: { op: '>=', value: 65 }, score: 10 }
          ], weight: 1.0 }
        ]
      },
      {
        id: 'soft_skills_score',
        rules: [
          { var: 'communication', ranges: [
            { if: { op: '>=', value: 90 }, score: 20 },
            { if: { op: '>=', value: 80 }, score: 15 },
            { if: { op: '>=', value: 70 }, score: 10 }
          ]},
          { var: 'teamwork', ranges: [
            { if: { op: '>=', value: 85 }, score: 15 },
            { if: { op: '>=', value: 75 }, score: 10 },
            { if: { op: '>=', value: 65 }, score: 5 }
          ]}
        ]
      },
      {
        id: 'total_score',
        formula: 'technical_score + soft_skills_score'
      },
      {
        id: 'performance_rating',
        switch: '$total_score',
        when: [
          { if: { op: '>=', value: 85 }, result: 'Exceptional', set_vars: { '$raise_percentage': 0.15 } },
          { if: { op: '>=', value: 70 }, result: 'Exceeds Expectations', set_vars: { '$raise_percentage': 0.10 } },
          { if: { op: '>=', value: 55 }, result: 'Meets Expectations', set_vars: { '$raise_percentage': 0.05 } },
          { if: { op: '>=', value: 40 }, result: 'Below Expectations', set_vars: { '$raise_percentage': 0.0 } }
        ],
        default: 'Unsatisfactory',
        set_vars: { '$raise_percentage': 0.0 }
      }
    ]
  },
  metadata: {
    name: 'Employee Performance Review',
    description: 'Comprehensive employee performance evaluation with scoring',
    category: 'hr',
    author: 'RuleFlow Team',
    version: '1.0.0',
    tags: ['performance', 'review', 'employee', 'evaluation'],
    difficulty: 'intermediate',
    estimatedTime: '10 minutes',
    inputs: ['coding_quality', 'problem_solving', 'communication', 'teamwork'],
    outputs: ['technical_score', 'soft_skills_score', 'total_score', 'performance_rating', 'raise_percentage']
  },
  examples: [
    {
      name: 'Exceptional performer',
      description: 'High-performing employee',
      inputs: { coding_quality: 95, problem_solving: 90, communication: 85, teamwork: 90 },
      expectedOutputs: { total_score: 90, performance_rating: 'Exceptional', raise_percentage: 0.15 }
    },
    {
      name: 'Average performer',
      description: 'Meets expectations',
      inputs: { coding_quality: 75, problem_solving: 70, communication: 75, teamwork: 70 },
      expectedOutputs: { total_score: 60, performance_rating: 'Meets Expectations', raise_percentage: 0.05 }
    }
  ]
};

export const SALARY_CALCULATOR: Template = {
  config: {
    formulas: [
      {
        id: 'experience_bonus',
        switch: '$years_experience',
        when: [
          { if: { op: '>=', value: 10 }, result: 0.20 },
          { if: { op: '>=', value: 7 }, result: 0.15 },
          { if: { op: '>=', value: 5 }, result: 0.10 },
          { if: { op: '>=', value: 3 }, result: 0.05 }
        ],
        default: 0.0
      },
      {
        id: 'education_bonus',
        switch: '$education_level',
        when: [
          { if: { op: '==', value: 'PhD' }, result: 0.15 },
          { if: { op: '==', value: 'Masters' }, result: 0.10 },
          { if: { op: '==', value: 'Bachelors' }, result: 0.05 }
        ],
        default: 0.0
      },
      {
        id: 'location_adjustment',
        switch: '$city',
        when: [
          { if: { op: '==', value: 'San Francisco' }, result: 1.4 },
          { if: { op: '==', value: 'New York' }, result: 1.3 },
          { if: { op: '==', value: 'Seattle' }, result: 1.2 },
          { if: { op: '==', value: 'Austin' }, result: 1.1 }
        ],
        default: 1.0
      },
      {
        id: 'total_bonus_rate',
        formula: '1 + experience_bonus + education_bonus'
      },
      {
        id: 'adjusted_salary',
        formula: 'base_salary * total_bonus_rate * location_adjustment',
        inputs: ['base_salary']
      },
      {
        id: 'salary_band',
        switch: '$adjusted_salary',
        when: [
          { if: { op: '>=', value: 150000 }, result: 'Senior Level' },
          { if: { op: '>=', value: 100000 }, result: 'Mid Level' },
          { if: { op: '>=', value: 60000 }, result: 'Junior Level' }
        ],
        default: 'Entry Level'
      }
    ]
  },
  metadata: {
    name: 'Salary Calculator',
    description: 'Calculate salary based on experience, education, and location',
    category: 'hr',
    author: 'RuleFlow Team',
    version: '1.0.0',
    tags: ['salary', 'compensation', 'calculator', 'hr'],
    difficulty: 'beginner',
    estimatedTime: '5 minutes',
    inputs: ['base_salary', 'years_experience', 'education_level', 'city'],
    outputs: ['experience_bonus', 'education_bonus', 'location_adjustment', 'adjusted_salary', 'salary_band']
  },
  examples: [
    {
      name: 'Senior developer in SF',
      description: 'Experienced developer in high-cost city',
      inputs: { base_salary: 80000, years_experience: 8, education_level: 'Masters', city: 'San Francisco' },
      expectedOutputs: { adjusted_salary: 140000, salary_band: 'Mid Level' }
    },
    {
      name: 'Junior developer',
      description: 'Entry-level position',
      inputs: { base_salary: 50000, years_experience: 1, education_level: 'Bachelors', city: 'Austin' },
      expectedOutputs: { adjusted_salary: 57750, salary_band: 'Entry Level' }
    }
  ]
};