import { Template } from '../types';

export const GRADE_CALCULATOR: Template = {
  config: {
    formulas: [
      {
        id: 'weighted_score',
        formula: '(homework * 0.20) + (quizzes * 0.15) + (midterm * 0.25) + (final_exam * 0.30) + (participation * 0.10)',
        inputs: ['homework', 'quizzes', 'midterm', 'final_exam', 'participation']
      },
      {
        id: 'letter_grade',
        switch: '$weighted_score',
        when: [
          { if: { op: '>=', value: 97 }, result: 'A+', set_vars: { '$gpa_points': 4.0 } },
          { if: { op: '>=', value: 93 }, result: 'A', set_vars: { '$gpa_points': 4.0 } },
          { if: { op: '>=', value: 90 }, result: 'A-', set_vars: { '$gpa_points': 3.7 } },
          { if: { op: '>=', value: 87 }, result: 'B+', set_vars: { '$gpa_points': 3.3 } },
          { if: { op: '>=', value: 83 }, result: 'B', set_vars: { '$gpa_points': 3.0 } },
          { if: { op: '>=', value: 80 }, result: 'B-', set_vars: { '$gpa_points': 2.7 } },
          { if: { op: '>=', value: 77 }, result: 'C+', set_vars: { '$gpa_points': 2.3 } },
          { if: { op: '>=', value: 73 }, result: 'C', set_vars: { '$gpa_points': 2.0 } },
          { if: { op: '>=', value: 70 }, result: 'C-', set_vars: { '$gpa_points': 1.7 } },
          { if: { op: '>=', value: 67 }, result: 'D+', set_vars: { '$gpa_points': 1.3 } },
          { if: { op: '>=', value: 60 }, result: 'D', set_vars: { '$gpa_points': 1.0 } }
        ],
        default: 'F',
        set_vars: { '$gpa_points': 0.0 }
      },
      {
        id: 'performance_category',
        switch: '$weighted_score',
        when: [
          { if: { op: '>=', value: 90 }, result: 'Excellent', set_vars: { '$recommendation': 'Outstanding work! Consider advanced courses.' } },
          { if: { op: '>=', value: 80 }, result: 'Good', set_vars: { '$recommendation': 'Good performance. Keep up the effort.' } },
          { if: { op: '>=', value: 70 }, result: 'Satisfactory', set_vars: { '$recommendation': 'Meeting expectations. Room for improvement.' } },
          { if: { op: '>=', value: 60 }, result: 'Needs Improvement', set_vars: { '$recommendation': 'Additional study and tutoring recommended.' } }
        ],
        default: 'Unsatisfactory',
        set_vars: { '$recommendation': 'Immediate intervention required. Consider retaking course.' }
      },
      {
        id: 'honors_eligible',
        switch: 'check_honors',
        when: [
          {
            if: {
              and: [
                { op: '>=', var: 'weighted_score', value: 85 },
                { op: '>=', var: 'participation', value: 90 },
                { op: '>=', var: 'final_exam', value: 80 }
              ]
            },
            result: true,
            set_vars: { '$honors_type': 'Dean\'s List' }
          }
        ],
        default: false,
        set_vars: { '$honors_type': 'None' }
      }
    ]
  },
  metadata: {
    name: 'Student Grade Calculator',
    description: 'Calculate final grades with weighted components and honors eligibility',
    category: 'education',
    author: 'RuleFlow Team',
    version: '1.0.0',
    tags: ['grades', 'education', 'student', 'gpa', 'assessment'],
    difficulty: 'beginner',
    estimatedTime: '5 minutes',
    inputs: ['homework', 'quizzes', 'midterm', 'final_exam', 'participation'],
    outputs: ['weighted_score', 'letter_grade', 'gpa_points', 'performance_category', 'honors_eligible']
  },
  examples: [
    {
      name: 'Excellent student',
      description: 'High-performing student eligible for honors',
      inputs: { 
        homework: 95, 
        quizzes: 92, 
        midterm: 88, 
        final_exam: 90, 
        participation: 95,
        check_honors: true
      },
      expectedOutputs: { 
        weighted_score: 91.15, 
        letter_grade: 'A-', 
        gpa_points: 3.7,
        honors_eligible: true
      }
    },
    {
      name: 'Struggling student',
      description: 'Student needing additional support',
      inputs: { 
        homework: 65, 
        quizzes: 70, 
        midterm: 55, 
        final_exam: 62, 
        participation: 75,
        check_honors: true
      },
      expectedOutputs: { 
        weighted_score: 63.45, 
        letter_grade: 'D',
        gpa_points: 1.0,
        honors_eligible: false
      }
    }
  ]
};

export const SCHOLARSHIP_ELIGIBILITY: Template = {
  config: {
    formulas: [
      {
        id: 'academic_score',
        rules: [
          { var: 'gpa', ranges: [
            { if: { op: '>=', value: 3.8 }, score: 40 },
            { if: { op: '>=', value: 3.5 }, score: 30 },
            { if: { op: '>=', value: 3.2 }, score: 20 },
            { if: { op: '>=', value: 3.0 }, score: 10 }
          ]},
          { var: 'sat_score', ranges: [
            { if: { op: '>=', value: 1400 }, score: 30 },
            { if: { op: '>=', value: 1200 }, score: 20 },
            { if: { op: '>=', value: 1000 }, score: 10 }
          ]},
          { var: 'class_rank_percentile', ranges: [
            { if: { op: '>=', value: 95 }, score: 20 },
            { if: { op: '>=', value: 90 }, score: 15 },
            { if: { op: '>=', value: 80 }, score: 10 }
          ]}
        ]
      },
      {
        id: 'extracurricular_score',
        rules: [
          { var: 'leadership_positions', ranges: [
            { if: { op: '>=', value: 3 }, score: 15 },
            { if: { op: '>=', value: 2 }, score: 10 },
            { if: { op: '>=', value: 1 }, score: 5 }
          ]},
          { var: 'volunteer_hours', ranges: [
            { if: { op: '>=', value: 200 }, score: 15 },
            { if: { op: '>=', value: 100 }, score: 10 },
            { if: { op: '>=', value: 50 }, score: 5 }
          ]},
          { var: 'sports_activities', ranges: [
            { if: { op: '>=', value: 2 }, score: 10 },
            { if: { op: '>=', value: 1 }, score: 5 }
          ]}
        ]
      },
      {
        id: 'financial_need_score',
        switch: '$family_income',
        when: [
          { if: { op: '<', value: 30000 }, result: 20 },
          { if: { op: '<', value: 50000 }, result: 15 },
          { if: { op: '<', value: 75000 }, result: 10 },
          { if: { op: '<', value: 100000 }, result: 5 }
        ],
        default: 0
      },
      {
        id: 'total_score',
        formula: 'academic_score + extracurricular_score + financial_need_score'
      },
      {
        id: 'scholarship_tier',
        switch: '$total_score',
        when: [
          { 
            if: { op: '>=', value: 90 }, 
            result: 'Full Scholarship', 
            set_vars: { 
              '$award_amount': 50000, 
              '$coverage': 'Full tuition, room, board, and books' 
            } 
          },
          { 
            if: { op: '>=', value: 70 }, 
            result: 'Partial Scholarship', 
            set_vars: { 
              '$award_amount': 25000, 
              '$coverage': 'Tuition and books' 
            } 
          },
          { 
            if: { op: '>=', value: 50 }, 
            result: 'Merit Award', 
            set_vars: { 
              '$award_amount': 10000, 
              '$coverage': 'Partial tuition' 
            } 
          }
        ],
        default: 'Not Eligible',
        set_vars: { '$award_amount': 0, '$coverage': 'None' }
      },
      {
        id: 'special_consideration',
        switch: 'check_special',
        when: [
          {
            if: {
              or: [
                { op: '==', var: 'first_generation_college', value: true },
                { op: '==', var: 'underrepresented_minority', value: true },
                { op: '>', var: 'hardship_circumstances', value: 0 }
              ]
            },
            result: 'Eligible for Additional Review',
            set_vars: { '$bonus_consideration': 10 }
          }
        ],
        default: 'Standard Review',
        set_vars: { '$bonus_consideration': 0 }
      }
    ]
  },
  metadata: {
    name: 'Scholarship Eligibility Assessment',
    description: 'Comprehensive scholarship evaluation based on academics, activities, and need',
    category: 'education',
    author: 'RuleFlow Team',
    version: '1.0.0',
    tags: ['scholarship', 'financial aid', 'education', 'assessment', 'eligibility'],
    difficulty: 'advanced',
    estimatedTime: '15 minutes',
    inputs: ['gpa', 'sat_score', 'class_rank_percentile', 'leadership_positions', 'volunteer_hours', 'sports_activities', 'family_income', 'first_generation_college', 'underrepresented_minority', 'hardship_circumstances'],
    outputs: ['academic_score', 'extracurricular_score', 'total_score', 'scholarship_tier', 'award_amount', 'special_consideration']
  },
  examples: [
    {
      name: 'Full scholarship candidate',
      description: 'Outstanding student with high need',
      inputs: { 
        gpa: 3.9, 
        sat_score: 1450, 
        class_rank_percentile: 98, 
        leadership_positions: 3, 
        volunteer_hours: 250, 
        sports_activities: 2,
        family_income: 25000,
        first_generation_college: true,
        underrepresented_minority: false,
        hardship_circumstances: 0,
        check_special: true
      },
      expectedOutputs: { 
        total_score: 110, 
        scholarship_tier: 'Full Scholarship',
        award_amount: 50000,
        special_consideration: 'Eligible for Additional Review'
      }
    },
    {
      name: 'Merit award candidate',
      description: 'Good student with moderate qualifications',
      inputs: { 
        gpa: 3.3, 
        sat_score: 1150, 
        class_rank_percentile: 85, 
        leadership_positions: 1, 
        volunteer_hours: 75, 
        sports_activities: 1,
        family_income: 80000,
        first_generation_college: false,
        underrepresented_minority: false,
        hardship_circumstances: 0,
        check_special: true
      },
      expectedOutputs: { 
        total_score: 55, 
        scholarship_tier: 'Merit Award',
        award_amount: 10000,
        special_consideration: 'Standard Review'
      }
    }
  ]
};