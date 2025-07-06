<?php

require_once __DIR__ . '/../TemplateProviderInterface.php';

class EducationTemplates implements TemplateProviderInterface
{
    public function getCategory(): string
    {
        return 'education';
    }
    
    public function getTemplateNames(): array
    {
        return ['student_grading'];
    }
    
    public function getTemplates(): array
    {
        return [
            'student_grading' => [
                'config' => [
                    'formulas' => [
                        [
                            'id' => 'weighted_average',
                            'formula' => '(exam_score * 0.4) + (assignment_avg * 0.3) + (participation * 0.2) + (attendance * 0.1)',
                            'inputs' => ['exam_score', 'assignment_avg', 'participation', 'attendance']
                        ],
                        [
                            'id' => 'letter_grade',
                            'switch' => 'weighted_average',
                            'when' => [
                                ['if' => ['op' => '>=', 'value' => 97], 'result' => 'A+'],
                                ['if' => ['op' => '>=', 'value' => 93], 'result' => 'A'],
                                ['if' => ['op' => '>=', 'value' => 90], 'result' => 'A-'],
                                ['if' => ['op' => '>=', 'value' => 87], 'result' => 'B+'],
                                ['if' => ['op' => '>=', 'value' => 83], 'result' => 'B'],
                                ['if' => ['op' => '>=', 'value' => 80], 'result' => 'B-'],
                                ['if' => ['op' => '>=', 'value' => 77], 'result' => 'C+'],
                                ['if' => ['op' => '>=', 'value' => 73], 'result' => 'C'],
                                ['if' => ['op' => '>=', 'value' => 70], 'result' => 'C-'],
                                ['if' => ['op' => '>=', 'value' => 67], 'result' => 'D+'],
                                ['if' => ['op' => '>=', 'value' => 65], 'result' => 'D'],
                                ['if' => ['op' => '>=', 'value' => 60], 'result' => 'D-']
                            ],
                            'default' => 'F'
                        ],
                        [
                            'id' => 'gpa_points',
                            'switch' => 'letter_grade',
                            'when' => [
                                ['if' => ['op' => '==', 'value' => 'A+'], 'result' => 4.0],
                                ['if' => ['op' => '==', 'value' => 'A'], 'result' => 4.0],
                                ['if' => ['op' => '==', 'value' => 'A-'], 'result' => 3.7],
                                ['if' => ['op' => '==', 'value' => 'B+'], 'result' => 3.3],
                                ['if' => ['op' => '==', 'value' => 'B'], 'result' => 3.0],
                                ['if' => ['op' => '==', 'value' => 'B-'], 'result' => 2.7],
                                ['if' => ['op' => '==', 'value' => 'C+'], 'result' => 2.3],
                                ['if' => ['op' => '==', 'value' => 'C'], 'result' => 2.0],
                                ['if' => ['op' => '==', 'value' => 'C-'], 'result' => 1.7],
                                ['if' => ['op' => '==', 'value' => 'D+'], 'result' => 1.3],
                                ['if' => ['op' => '==', 'value' => 'D'], 'result' => 1.0],
                                ['if' => ['op' => '==', 'value' => 'D-'], 'result' => 0.7]
                            ],
                            'default' => 0.0
                        ],
                        [
                            'id' => 'pass_fail',
                            'switch' => 'weighted_average',
                            'when' => [
                                ['if' => ['op' => '>=', 'value' => 60], 'result' => 'Pass']
                            ],
                            'default' => 'Fail'
                        ]
                    ]
                ],
                'metadata' => [
                    'name' => 'Student Grade Calculation',
                    'category' => 'education',
                    'description' => 'Calculate weighted grades, letter grades, and GPA points',
                    'author' => 'RuleFlow',
                    'version' => '1.0.0',
                    'inputs' => [
                        'exam_score' => 'Exam score (0-100)',
                        'assignment_avg' => 'Assignment average (0-100)',
                        'participation' => 'Participation score (0-100)',
                        'attendance' => 'Attendance score (0-100)'
                    ],
                    'outputs' => [
                        'weighted_average' => 'Weighted average score',
                        'letter_grade' => 'Letter grade (A+ to F)',
                        'gpa_points' => 'GPA points (0.0-4.0)',
                        'pass_fail' => 'Pass/Fail status'
                    ]
                ]
            ]
        ];
    }
}