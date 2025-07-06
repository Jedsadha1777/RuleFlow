<?php

declare(strict_types=1);

/**
 * Pre-built configuration templates for common business use cases
 */
class ConfigTemplateManager
{
    private array $templates = [];
    
    public function __construct()
    {
        $this->loadBuiltInTemplates();
    }
    
    /**
     * Get available template names
     */
    public function getAvailableTemplates(): array
    {
        return array_keys($this->templates);
    }
    
    /**
     * Get template by name
     */
    public function getTemplate(string $name): array
    {
        if (!isset($this->templates[$name])) {
            throw new RuleFlowException("Template '$name' not found", [
                'available_templates' => $this->getAvailableTemplates()
            ]);
        }
        
        return $this->templates[$name];
    }
    
    /**
     * Get template with custom parameters
     */
    public function getTemplateWithParams(string $name, array $params = []): array
    {
        $template = $this->getTemplate($name);
        $result = $this->applyParameters($template, $params);
        
        // Add parameters to the result for testing/debugging
        $result['parameters'] = $params;
        
        return $result;
    }
    
    /**
     * Register custom template
     */
    public function registerTemplate(string $name, array $config, array $metadata = []): void
    {
        // Validate config before registering
        if (!$this->validateTemplate($config)) {
            throw new RuleFlowException("Template validation failed", [
                'template_name' => $name,
                'validation_error' => 'Template configuration does not meet requirements'
            ]);
        }
        
        $this->templates[$name] = [
            'config' => $config,
            'metadata' => array_merge([
                'name' => $name,
                'category' => 'custom',
                'description' => "Custom template: $name",
                'author' => 'User',
                'version' => '1.0.0'
            ], $metadata)
        ];
    }
    
    /**
     * Get templates by category
     */
    public function getTemplatesByCategory(string $category): array
    {
        $filtered = [];
        foreach ($this->templates as $name => $template) {
            if (($template['metadata']['category'] ?? 'general') === $category) {
                $filtered[$name] = $template;
            }
        }
        return $filtered;
    }
    
    /**
     * Validate template configuration
     */
    public function validateTemplate(array $config): bool
    {
        // Basic structure validation
        if (!isset($config['formulas']) || !is_array($config['formulas'])) {
            return false;
        }
        
        if (empty($config['formulas'])) {
            return false;
        }
        
        // Validate each formula has required fields
        foreach ($config['formulas'] as $formula) {
            if (!isset($formula['id']) || empty($formula['id'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Clone template with new name
     */
    public function cloneTemplate(string $originalName, string $newName): array
    {
        $original = $this->getTemplate($originalName);
        
        // Deep copy the template
        $cloned = [
            'config' => $original['config'],
            'metadata' => $original['metadata']
        ];
        
        // Update metadata
        $cloned['metadata']['name'] = $newName;
        $cloned['metadata']['description'] = "Cloned from {$originalName}";
        $cloned['metadata']['version'] = '1.0.0';
        
        // Register the cloned template
        $this->registerTemplate($newName, $cloned['config'], $cloned['metadata']);
        
        return $cloned;
    }
    
    /**
     * Modify template with path-based changes
     */
    public function modifyTemplate(string $name, array $modifications): array
    {
        $template = $this->getTemplate($name);
        
        foreach ($modifications as $path => $value) {
            $this->setNestedValue($template, $path, $value);
        }
        
        // Update the stored template
        $this->templates[$name] = $template;
        
        return $template;
    }
    
    /**
     * Get available categories
     */
    public function getAvailableCategories(): array
    {
        $categories = [];
        foreach ($this->templates as $template) {
            $category = $template['metadata']['category'] ?? 'general';
            if (!in_array($category, $categories)) {
                $categories[] = $category;
            }
        }
        return $categories;
    }
    
    /**
     * Get template counts per category
     */
    public function getTemplateCounts(): array
    {
        $counts = [];
        foreach ($this->templates as $template) {
            $category = $template['metadata']['category'] ?? 'general';
            $counts[$category] = ($counts[$category] ?? 0) + 1;
        }
        return $counts;
    }
    
    /**
     * Export template with version info (returns array)
     */
    public function exportTemplateData(string $name): array
    {
        $template = $this->getTemplate($name);
        return [
            'config' => $template['config'],
            'metadata' => $template['metadata'],
            'version' => '1.0.0',
            'exported_at' => date('c')
        ];
    }
    
    /**
     * Import template from data
     */
    public function importTemplateData(string $name, array $templateData): array
    {
        if (!isset($templateData['config']) || !isset($templateData['metadata'])) {
            throw new RuleFlowException("Invalid template data format", [
                'required_fields' => ['config', 'metadata']
            ]);
        }
        
        $this->registerTemplate($name, $templateData['config'], $templateData['metadata']);
        
        return $this->getTemplate($name);
    }
    
    /**
     * Set nested value using dot notation
     */
    private function setNestedValue(array &$array, string $path, $value): void
    {
        $keys = explode('.', $path);
        $current = &$array;
        
        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        
        $current = $value;
    }
    
    /**
     * Load all built-in templates
     */
    private function loadBuiltInTemplates(): void
    {
        $this->loadFinancialTemplates();
        $this->loadHRTemplates();
        $this->loadInsuranceTemplates();
        $this->loadHealthcareTemplates();
        $this->loadEducationTemplates();
        $this->loadEcommerceTemplates();
        $this->loadRealEstateTemplates();
    }
    
    /**
     * Financial & Lending Templates
     */
    private function loadFinancialTemplates(): void
    {
        // Loan Application Assessment
        $this->templates['loan_application'] = [
            'config' => [
                'formulas' => [
                    [
                        'id' => 'monthly_income',
                        'formula' => 'annual_income / 12',
                        'inputs' => ['annual_income'],
                        'as' => '$monthly'
                    ],
                    [
                        'id' => 'debt_to_income_ratio',
                        'formula' => 'percentage(monthly_debt, monthly)',
                        'inputs' => ['monthly_debt', 'monthly'],
                        'as' => '$dti_ratio'
                    ],
                    [
                        'id' => 'credit_score_points',
                        'rules' => [
                            [
                                'var' => 'credit_score',
                                'ranges' => [
                                    ['if' => ['op' => '>=', 'value' => 750], 'score' => 100],
                                    ['if' => ['op' => '>=', 'value' => 700], 'score' => 80],
                                    ['if' => ['op' => '>=', 'value' => 650], 'score' => 60],
                                    ['if' => ['op' => '>=', 'value' => 600], 'score' => 40],
                                    ['if' => ['op' => '>=', 'value' => 550], 'score' => 20]
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'income_points',
                        'rules' => [
                            [
                                'var' => 'monthly',
                                'ranges' => [
                                    ['if' => ['op' => '>=', 'value' => 100000], 'score' => 50],
                                    ['if' => ['op' => '>=', 'value' => 75000], 'score' => 40],
                                    ['if' => ['op' => '>=', 'value' => 50000], 'score' => 30],
                                    ['if' => ['op' => '>=', 'value' => 30000], 'score' => 20],
                                    ['if' => ['op' => '>=', 'value' => 20000], 'score' => 10]
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'employment_points',
                        'rules' => [
                            [
                                'var' => 'employment_years',
                                'ranges' => [
                                    ['if' => ['op' => '>=', 'value' => 5], 'score' => 30],
                                    ['if' => ['op' => '>=', 'value' => 3], 'score' => 20],
                                    ['if' => ['op' => '>=', 'value' => 1], 'score' => 10],
                                    ['if' => ['op' => '>=', 'value' => 0.5], 'score' => 5]
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'total_score',
                        'formula' => 'credit_score_points + income_points + employment_points',
                        'inputs' => ['credit_score_points', 'income_points', 'employment_points']
                    ],
                    [
                        'id' => 'loan_decision',
                        'switch' => 'total_score',
                        'when' => [
                            [
                                'if' => ['op' => '>=', 'value' => 150],
                                'result' => 'Approved',
                                'set_vars' => [
                                    '$interest_rate' => 3.5,
                                    '$max_amount' => 1000000,
                                    '$term_years' => 30
                                ]
                            ],
                            [
                                'if' => ['op' => '>=', 'value' => 120],
                                'result' => 'Approved',
                                'set_vars' => [
                                    '$interest_rate' => 4.5,
                                    '$max_amount' => 500000,
                                    '$term_years' => 25
                                ]
                            ],
                            [
                                'if' => ['op' => '>=', 'value' => 80],
                                'result' => 'Conditional',
                                'set_vars' => [
                                    '$interest_rate' => 6.5,
                                    '$max_amount' => 200000,
                                    '$term_years' => 15
                                ]
                            ]
                        ],
                        'default' => 'Rejected'
                    ],
                    [
                        'id' => 'monthly_payment',
                        'formula' => 'pmt(max_amount, interest_rate / 12, term_years * 12)',
                        'inputs' => ['max_amount', 'interest_rate', 'term_years']
                    ]
                ]
            ],
            'metadata' => [
                'name' => 'Loan Application Assessment',
                'category' => 'financial',
                'description' => 'Comprehensive loan approval system with credit scoring',
                'author' => 'RuleFlow',
                'version' => '2.0.0',
                'inputs' => [
                    'annual_income' => 'Annual gross income (number)',
                    'monthly_debt' => 'Existing monthly debt payments (number)',
                    'credit_score' => 'Credit score 300-850 (number)',
                    'employment_years' => 'Years at current job (number)'
                ],
                'outputs' => [
                    'loan_decision' => 'Approved/Conditional/Rejected',
                    'interest_rate' => 'Approved interest rate (%)',
                    'max_amount' => 'Maximum loan amount',
                    'monthly_payment' => 'Estimated monthly payment'
                ]
            ]
        ];
        
        // Credit Card Approval
        $this->templates['credit_card_approval'] = [
            'config' => [
                'formulas' => [
                    [
                        'id' => 'credit_utilization_score',
                        'switch' => 'credit_utilization',
                        'when' => [
                            ['if' => ['op' => '<=', 'value' => 10], 'result' => 50],
                            ['if' => ['op' => '<=', 'value' => 30], 'result' => 30],
                            ['if' => ['op' => '<=', 'value' => 50], 'result' => 10],
                            ['if' => ['op' => '<=', 'value' => 75], 'result' => -10]
                        ],
                        'default' => -20
                    ],
                    [
                        'id' => 'payment_history_score',
                        'switch' => 'late_payments_12m',
                        'when' => [
                            ['if' => ['op' => '==', 'value' => 0], 'result' => 50],
                            ['if' => ['op' => '<=', 'value' => 1], 'result' => 30],
                            ['if' => ['op' => '<=', 'value' => 3], 'result' => 10]
                        ],
                        'default' => -30
                    ],
                    [
                        'id' => 'final_score',
                        'formula' => 'credit_score + credit_utilization_score + payment_history_score + income_score',
                        'inputs' => ['credit_score', 'credit_utilization_score', 'payment_history_score', 'income_score']
                    ],
                    [
                        'id' => 'approval_decision',
                        'switch' => 'final_score',
                        'when' => [
                            [
                                'if' => ['op' => '>=', 'value' => 750],
                                'result' => 'Approved',
                                'set_vars' => ['$credit_limit' => 50000, '$apr' => 12.99]
                            ],
                            [
                                'if' => ['op' => '>=', 'value' => 650],
                                'result' => 'Approved',
                                'set_vars' => ['$credit_limit' => 25000, '$apr' => 18.99]
                            ],
                            [
                                'if' => ['op' => '>=', 'value' => 600],
                                'result' => 'Approved',
                                'set_vars' => ['$credit_limit' => 10000, '$apr' => 24.99]
                            ]
                        ],
                        'default' => 'Rejected'
                    ]
                ]
            ],
            'metadata' => [
                'name' => 'Credit Card Approval System',
                'category' => 'financial',
                'description' => 'Credit card application assessment with limit determination',
                'inputs' => [
                    'credit_score' => 'FICO credit score',
                    'credit_utilization' => 'Current credit utilization %',
                    'late_payments_12m' => 'Late payments in last 12 months',
                    'income_score' => 'Income-based score'
                ]
            ]
        ];
    }
    
    /**
     * HR & Employee Evaluation Templates
     */
    private function loadHRTemplates(): void
    {
        // Employee Performance Review
        $this->templates['performance_review'] = [
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
                'description' => 'Comprehensive employee evaluation with rating and salary increase calculation'
            ]
        ];
        
        // Candidate Scoring
        $this->templates['candidate_scoring'] = [
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
                'description' => 'Structured candidate assessment for hiring decisions'
            ]
        ];
    }
    
    /**
     * Insurance Risk Assessment Templates
     */
    private function loadInsuranceTemplates(): void
    {
        // Auto Insurance Risk
        $this->templates['auto_insurance_risk'] = [
            'config' => [
                'formulas' => [
                    [
                        'id' => 'age_risk_factor',
                        'switch' => 'driver_age',
                        'when' => [
                            ['if' => ['op' => '<', 'value' => 25], 'result' => 1.5],
                            ['if' => ['op' => '<', 'value' => 35], 'result' => 1.2],
                            ['if' => ['op' => '<=', 'value' => 65], 'result' => 1.0],
                            ['if' => ['op' => '>', 'value' => 65], 'result' => 1.3]
                        ],
                        'default' => 1.0
                    ],
                    [
                        'id' => 'experience_factor',
                        'switch' => 'driving_years',
                        'when' => [
                            ['if' => ['op' => '<', 'value' => 3], 'result' => 1.4],
                            ['if' => ['op' => '<', 'value' => 10], 'result' => 1.1],
                            ['if' => ['op' => '>=', 'value' => 10], 'result' => 0.9]
                        ],
                        'default' => 1.0
                    ],
                    [
                        'id' => 'accident_factor',
                        'switch' => 'accidents_5_years',
                        'when' => [
                            ['if' => ['op' => '==', 'value' => 0], 'result' => 0.8],
                            ['if' => ['op' => '==', 'value' => 1], 'result' => 1.2],
                            ['if' => ['op' => '>=', 'value' => 2], 'result' => 1.8]
                        ],
                        'default' => 1.0
                    ],
                    [
                        'id' => 'base_premium',
                        'formula' => 'base_rate * age_risk_factor * experience_factor * accident_factor',
                        'inputs' => ['base_rate', 'age_risk_factor', 'experience_factor', 'accident_factor']
                    ],
                    [
                        'id' => 'risk_category',
                        'switch' => 'base_premium',
                        'when' => [
                            ['if' => ['op' => '>=', 'value' => 2000], 'result' => 'High Risk'],
                            ['if' => ['op' => '>=', 'value' => 1200], 'result' => 'Medium Risk'],
                            ['if' => ['op' => '<', 'value' => 1200], 'result' => 'Low Risk']
                        ],
                        'default' => 'Standard Risk'
                    ]
                ]
            ],
            'metadata' => [
                'name' => 'Auto Insurance Risk Assessment',
                'category' => 'insurance',
                'description' => 'Calculate insurance premiums based on driver risk factors'
            ]
        ];
    }
    
    /**
     * Healthcare Templates
     */
    private function loadHealthcareTemplates(): void
    {
        // BMI Health Assessment
        $this->templates['bmi_health_assessment'] = [
            'config' => [
                'formulas' => [
                    [
                        'id' => 'bmi_calculation',
                        'formula' => 'bmi(weight, height)',
                        'inputs' => ['weight', 'height'],
                        'as' => '$bmi'
                    ],
                    [
                        'id' => 'bmi_category',
                        'switch' => '$bmi',
                        'when' => [
                            ['if' => ['op' => '<', 'value' => 18.5], 'result' => 'Underweight'],
                            ['if' => ['op' => 'between', 'value' => [18.5, 24.9]], 'result' => 'Normal'],
                            ['if' => ['op' => 'between', 'value' => [25, 29.9]], 'result' => 'Overweight'],
                            ['if' => ['op' => '>=', 'value' => 30], 'result' => 'Obese']
                        ],
                        'default' => 'Unknown'
                    ],
                    [
                        'id' => 'health_risk_score',
                        'switch' => 'bmi_category',
                        'when' => [
                            ['if' => ['op' => '==', 'value' => 'Normal'], 'result' => 1],
                            ['if' => ['op' => '==', 'value' => 'Underweight'], 'result' => 2],
                            ['if' => ['op' => '==', 'value' => 'Overweight'], 'result' => 3],
                            ['if' => ['op' => '==', 'value' => 'Obese'], 'result' => 5]
                        ],
                        'default' => 3
                    ],
                    [
                        'id' => 'health_recommendations',
                        'switch' => 'bmi_category',
                        'when' => [
                            ['if' => ['op' => '==', 'value' => 'Underweight'], 'result' => 'Consider increasing caloric intake'],
                            ['if' => ['op' => '==', 'value' => 'Normal'], 'result' => 'Maintain current healthy lifestyle'],
                            ['if' => ['op' => '==', 'value' => 'Overweight'], 'result' => 'Consider diet and exercise modifications'],
                            ['if' => ['op' => '==', 'value' => 'Obese'], 'result' => 'Consult healthcare provider for weight management']
                        ],
                        'default' => 'Consult healthcare provider'
                    ]
                ]
            ],
            'metadata' => [
                'name' => 'BMI Health Assessment',
                'category' => 'healthcare',
                'description' => 'Calculate BMI and provide health category with recommendations'
            ]
        ];
    }
    
    /**
     * Education Templates
     */
    private function loadEducationTemplates(): void
    {
        // Student Grade Calculation
        $this->templates['student_grading'] = [
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
                'description' => 'Calculate weighted grades, letter grades, and GPA points'
            ]
        ];
    }
    
    /**
     * E-commerce Templates
     */
    private function loadEcommerceTemplates(): void
    {
        // Product Pricing Strategy
        $this->templates['dynamic_pricing'] = [
            'config' => [
                'formulas' => [
                    [
                        'id' => 'demand_multiplier',
                        'switch' => 'demand_level',
                        'when' => [
                            ['if' => ['op' => '==', 'value' => 'High'], 'result' => 1.2],
                            ['if' => ['op' => '==', 'value' => 'Medium'], 'result' => 1.0],
                            ['if' => ['op' => '==', 'value' => 'Low'], 'result' => 0.8]
                        ],
                        'default' => 1.0
                    ],
                    [
                        'id' => 'inventory_multiplier',
                        'switch' => 'inventory_level',
                        'when' => [
                            ['if' => ['op' => '<', 'value' => 10], 'result' => 1.15],
                            ['if' => ['op' => '<', 'value' => 50], 'result' => 1.0],
                            ['if' => ['op' => '>=', 'value' => 100], 'result' => 0.9]
                        ],
                        'default' => 1.0
                    ],
                    [
                        'id' => 'competitor_adjustment',
                        'switch' => 'price_vs_competitor',
                        'when' => [
                            ['if' => ['op' => '>', 'value' => 1.1], 'result' => 0.95],
                            ['if' => ['op' => '<', 'value' => 0.9], 'result' => 1.05]
                        ],
                        'default' => 1.0
                    ],
                    [
                        'id' => 'dynamic_price',
                        'formula' => 'base_price * demand_multiplier * inventory_multiplier * competitor_adjustment',
                        'inputs' => ['base_price', 'demand_multiplier', 'inventory_multiplier', 'competitor_adjustment']
                    ],
                    [
                        'id' => 'discount_eligible',
                        'switch' => 'customer_tier',
                        'when' => [
                            ['if' => ['op' => '==', 'value' => 'VIP'], 'result' => 0.15],
                            ['if' => ['op' => '==', 'value' => 'Gold'], 'result' => 0.10],
                            ['if' => ['op' => '==', 'value' => 'Silver'], 'result' => 0.05]
                        ],
                        'default' => 0.0
                    ],
                    [
                        'id' => 'final_price',
                        'formula' => 'dynamic_price * (1 - discount_eligible)',
                        'inputs' => ['dynamic_price', 'discount_eligible']
                    ]
                ]
            ],
            'metadata' => [
                'name' => 'Dynamic Pricing Strategy',
                'category' => 'ecommerce',
                'description' => 'AI-driven pricing based on demand, inventory, and competition'
            ]
        ];
        
        // Customer Lifetime Value
        $this->templates['customer_ltv'] = [
            'config' => [
                'formulas' => [
                    [
                        'id' => 'monthly_value',
                        'formula' => 'avg_order_value * orders_per_month',
                        'inputs' => ['avg_order_value', 'orders_per_month']
                    ],
                    [
                        'id' => 'annual_value',
                        'formula' => 'monthly_value * 12',
                        'inputs' => ['monthly_value']
                    ],
                    [
                        'id' => 'retention_factor',
                        'switch' => 'customer_segment',
                        'when' => [
                            ['if' => ['op' => '==', 'value' => 'Premium'], 'result' => 0.9],
                            ['if' => ['op' => '==', 'value' => 'Standard'], 'result' => 0.7],
                            ['if' => ['op' => '==', 'value' => 'Basic'], 'result' => 0.5]
                        ],
                        'default' => 0.6
                    ],
                    [
                        'id' => 'lifetime_months',
                        'formula' => '1 / (1 - retention_factor)',
                        'inputs' => ['retention_factor']
                    ],
                    [
                        'id' => 'customer_ltv',
                        'formula' => 'monthly_value * lifetime_months',
                        'inputs' => ['monthly_value', 'lifetime_months']
                    ],
                    [
                        'id' => 'ltv_category',
                        'switch' => 'customer_ltv',
                        'when' => [
                            ['if' => ['op' => '>=', 'value' => 10000], 'result' => 'High Value'],
                            ['if' => ['op' => '>=', 'value' => 5000], 'result' => 'Medium Value'],
                            ['if' => ['op' => '>=', 'value' => 1000], 'result' => 'Standard Value']
                        ],
                        'default' => 'Low Value'
                    ]
                ]
            ],
            'metadata' => [
                'name' => 'Customer Lifetime Value Calculator',
                'category' => 'ecommerce',
                'description' => 'Calculate and categorize customer lifetime value'
            ]
        ];
    }
    
    /**
     * Real Estate Templates
     */
    private function loadRealEstateTemplates(): void
    {
        // Property Valuation
        $this->templates['property_valuation'] = [
            'config' => [
                'formulas' => [
                    [
                        'id' => 'size_value',
                        'formula' => 'square_feet * price_per_sqft',
                        'inputs' => ['square_feet', 'price_per_sqft']
                    ],
                    [
                        'id' => 'location_multiplier',
                        'switch' => 'location_rating',
                        'when' => [
                            ['if' => ['op' => '==', 'value' => 'Prime'], 'result' => 1.3],
                            ['if' => ['op' => '==', 'value' => 'Excellent'], 'result' => 1.2],
                            ['if' => ['op' => '==', 'value' => 'Good'], 'result' => 1.1],
                            ['if' => ['op' => '==', 'value' => 'Average'], 'result' => 1.0],
                            ['if' => ['op' => '==', 'value' => 'Below Average'], 'result' => 0.9]
                        ],
                        'default' => 1.0
                    ],
                    [
                        'id' => 'condition_multiplier',
                        'switch' => 'property_condition',
                        'when' => [
                            ['if' => ['op' => '==', 'value' => 'Excellent'], 'result' => 1.15],
                            ['if' => ['op' => '==', 'value' => 'Good'], 'result' => 1.05],
                            ['if' => ['op' => '==', 'value' => 'Fair'], 'result' => 0.95],
                            ['if' => ['op' => '==', 'value' => 'Poor'], 'result' => 0.8]
                        ],
                        'default' => 1.0
                    ],
                    [
                        'id' => 'amenity_bonus',
                        'rules' => [
                            ['var' => 'has_garage', 'if' => ['op' => '==', 'value' => 1], 'score' => 5000],
                            ['var' => 'has_pool', 'if' => ['op' => '==', 'value' => 1], 'score' => 15000],
                            ['var' => 'has_fireplace', 'if' => ['op' => '==', 'value' => 1], 'score' => 3000],
                            ['var' => 'updated_kitchen', 'if' => ['op' => '==', 'value' => 1], 'score' => 10000]
                        ]
                    ],
                    [
                        'id' => 'estimated_value',
                        'formula' => '(size_value * location_multiplier * condition_multiplier) + amenity_bonus',
                        'inputs' => ['size_value', 'location_multiplier', 'condition_multiplier', 'amenity_bonus']
                    ],
                    [
                        'id' => 'value_category',
                        'switch' => 'estimated_value',
                        'when' => [
                            ['if' => ['op' => '>=', 'value' => 1000000], 'result' => 'Luxury'],
                            ['if' => ['op' => '>=', 'value' => 500000], 'result' => 'Premium'],
                            ['if' => ['op' => '>=', 'value' => 250000], 'result' => 'Standard'],
                            ['if' => ['op' => '>=', 'value' => 100000], 'result' => 'Affordable']
                        ],
                        'default' => 'Budget'
                    ]
                ]
            ],
            'metadata' => [
                'name' => 'Property Valuation Model',
                'category' => 'real_estate',
                'description' => 'Estimate property value based on size, location, condition, and amenities'
            ]
        ];
    }
    
    /**
     * Apply parameters to template
     */
    private function applyParameters(array $template, array $params): array
    {
        if (empty($params)) {
            return $template;
        }
        
        $config = $template['config'];
        
        // Replace parameter placeholders in formulas
        $configStr = json_encode($config);
        foreach ($params as $key => $value) {
            $placeholder = "{{$key}}";
            $configStr = str_replace($placeholder, (string)$value, $configStr);
        }
        
        $template['config'] = json_decode($configStr, true);
        return $template;
    }
    
    /**
     * Get template metadata
     */
    public function getTemplateMetadata(string $name): array
    {
        $template = $this->getTemplate($name);
        return $template['metadata'];
    }
    
    /**
     * Search templates by keyword
     */
    public function searchTemplates(string $keyword): array
    {
        $results = [];
        $keyword = strtolower($keyword);
        
        foreach ($this->templates as $name => $template) {
            $metadata = $template['metadata'];
            
            if (
                strpos(strtolower($name), $keyword) !== false ||
                strpos(strtolower($metadata['name'] ?? ''), $keyword) !== false ||
                strpos(strtolower($metadata['description'] ?? ''), $keyword) !== false ||
                strpos(strtolower($metadata['category'] ?? ''), $keyword) !== false
            ) {
                $results[$name] = $template;
            }
        }
        
        return $results;
    }
    
    /**
     * Export template to JSON
     */
    public function exportTemplate(string $name): string
    {
        $template = $this->getTemplate($name);
        return json_encode($template, JSON_PRETTY_PRINT);
    }
    
    /**
     * Import template from JSON
     */
    public function importTemplate(string $json): void
    {
        $template = json_decode($json, true);
        
        if (!isset($template['metadata']['name'])) {
            throw new RuleFlowException("Template must have metadata.name field");
        }
        
        $name = strtolower(str_replace(' ', '_', $template['metadata']['name']));
        $this->registerTemplate($name, $template['config'], $template['metadata']);
    }
}