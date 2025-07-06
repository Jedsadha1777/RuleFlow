<?php

require_once __DIR__ . '/../TemplateProviderInterface.php';

class HRTemplates implements TemplateProviderInterface
{
    public function getCategory(): string
    {
        return 'hr';
    }
    
    public function getTemplateNames(): array
    {
        return ['performance_review', 'candidate_scoring'];
    }
    
    public function getTemplates(): array
    {
        return [
            'performance_review' => [
                'config' => [
                    'formulas' => [
                        [
                            'id' => 'performance_score',
                            'formula' => 'avg(quality_score, productivity_score, teamwork_score, communication_score)',
                            'inputs' => ['quality_score', 'productivity_score', 'teamwork_score', 'communication_score']
                        ],
                        [
                            'id' => 'goal_achievement_bonus',
                            'switch' => 'goals_achieved',
                            'when' => [
                                ['if' => ['op' => '>=', 'value' => 90], 'result' => 20],
                                ['if' => ['op' => '>=', 'value' => 75], 'result' => 15],
                                ['if' => ['op' => '>=', 'value' => 60], 'result' => 10]
                            ],
                            'default' => 0
                        ],
                        [
                            'id' => 'total_score',
                            'formula' => 'performance_score + goal_achievement_bonus + tenure_bonus',
                            'inputs' => ['performance_score', 'goal_achievement_bonus', 'tenure_bonus']
                        ],
                        [
                            'id' => 'rating',
                            'switch' => 'total_score',
                            'when' => [
                                ['if' => ['op' => '>=', 'value' => 90], 'result' => 'Exceptional'],
                                ['if' => ['op' => '>=', 'value' => 80], 'result' => 'Exceeds Expectations'],
                                ['if' => ['op' => '>=', 'value' => 70], 'result' => 'Meets Expectations'],
                                ['if' => ['op' => '>=', 'value' => 60], 'result' => 'Below Expectations']
                            ],
                            'default' => 'Unsatisfactory'
                        ],
                        [
                            'id' => 'salary_increase_percent',
                            'switch' => 'rating',
                            'when' => [
                                ['if' => ['op' => '==', 'value' => 'Exceptional'], 'result' => 8],
                                ['if' => ['op' => '==', 'value' => 'Exceeds Expectations'], 'result' => 5],
                                ['if' => ['op' => '==', 'value' => 'Meets Expectations'], 'result' => 3]
                            ],
                            'default' => 0
                        ]
                    ]
                ],
                'metadata' => [
                    'name' => 'Employee Performance Review',
                    'category' => 'hr',
                    'description' => 'Comprehensive employee evaluation with rating and salary increase calculation',
                    'author' => 'RuleFlow',
                    'version' => '1.0.0',
                    'inputs' => [
                        'quality_score' => 'Work quality score (0-100)',
                        'productivity_score' => 'Productivity score (0-100)',
                        'teamwork_score' => 'Teamwork score (0-100)',
                        'communication_score' => 'Communication score (0-100)',
                        'goals_achieved' => 'Percentage of goals achieved (0-100)',
                        'tenure_bonus' => 'Tenure bonus points'
                    ],
                    'outputs' => [
                        'performance_score' => 'Average performance score',
                        'total_score' => 'Total performance score',
                        'rating' => 'Performance rating',
                        'salary_increase_percent' => 'Recommended salary increase percentage'
                    ]
                ]
            ],
            
            'candidate_scoring' => [
                'config' => [
                    'formulas' => [
                        [
                            'id' => 'experience_score',
                            'rules' => [
                                [
                                    'var' => 'years_experience',
                                    'ranges' => [
                                        ['if' => ['op' => '>=', 'value' => 10], 'score' => 30],
                                        ['if' => ['op' => '>=', 'value' => 5], 'score' => 25],
                                        ['if' => ['op' => '>=', 'value' => 3], 'score' => 20],
                                        ['if' => ['op' => '>=', 'value' => 1], 'score' => 15]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 'education_score',
                            'switch' => 'education_level',
                            'when' => [
                                ['if' => ['op' => '==', 'value' => 'PhD'], 'result' => 25],
                                ['if' => ['op' => '==', 'value' => 'Masters'], 'result' => 20],
                                ['if' => ['op' => '==', 'value' => 'Bachelors'], 'result' => 15],
                                ['if' => ['op' => '==', 'value' => 'Associates'], 'result' => 10]
                            ],
                            'default' => 5
                        ],
                        [
                            'id' => 'skills_score',
                            'formula' => 'technical_skills * 0.4 + soft_skills * 0.3 + domain_knowledge * 0.3',
                            'inputs' => ['technical_skills', 'soft_skills', 'domain_knowledge']
                        ],
                        [
                            'id' => 'total_candidate_score',
                            'formula' => 'experience_score + education_score + skills_score',
                            'inputs' => ['experience_score', 'education_score', 'skills_score']
                        ],
                        [
                            'id' => 'recommendation',
                            'switch' => 'total_candidate_score',
                            'when' => [
                                ['if' => ['op' => '>=', 'value' => 80], 'result' => 'Strong Hire'],
                                ['if' => ['op' => '>=', 'value' => 70], 'result' => 'Hire'],
                                ['if' => ['op' => '>=', 'value' => 60], 'result' => 'Borderline'],
                                ['if' => ['op' => '>=', 'value' => 50], 'result' => 'Weak Hire']
                            ],
                            'default' => 'No Hire'
                        ]
                    ]
                ],
                'metadata' => [
                    'name' => 'Candidate Evaluation System',
                    'category' => 'hr',
                    'description' => 'Structured candidate assessment for hiring decisions',
                    'author' => 'RuleFlow',
                    'version' => '1.0.0',
                    'inputs' => [
                        'years_experience' => 'Years of relevant experience',
                        'education_level' => 'Highest education level',
                        'technical_skills' => 'Technical skills score (0-100)',
                        'soft_skills' => 'Soft skills score (0-100)',
                        'domain_knowledge' => 'Domain knowledge score (0-100)'
                    ],
                    'outputs' => [
                        'experience_score' => 'Experience score',
                        'education_score' => 'Education score',
                        'skills_score' => 'Combined skills score',
                        'total_candidate_score' => 'Total candidate score',
                        'recommendation' => 'Hiring recommendation'
                    ]
                ]
            ]
        ];
    }
}