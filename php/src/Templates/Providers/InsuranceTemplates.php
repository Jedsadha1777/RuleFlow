<?php

require_once __DIR__ . '/../TemplateProviderInterface.php';

class InsuranceTemplates implements TemplateProviderInterface
{
    public function getCategory(): string
    {
        return 'insurance';
    }
    
    public function getTemplateNames(): array
    {
        return ['auto_insurance_risk'];
    }
    
    public function getTemplates(): array
    {
        return [
            'auto_insurance_risk' => [
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
                    'description' => 'Calculate insurance premiums based on driver risk factors',
                    'author' => 'RuleFlow',
                    'version' => '1.0.0',
                    'inputs' => [
                        'driver_age' => 'Driver age in years',
                        'driving_years' => 'Years of driving experience',
                        'accidents_5_years' => 'Number of accidents in last 5 years',
                        'base_rate' => 'Base insurance rate'
                    ],
                    'outputs' => [
                        'age_risk_factor' => 'Age-based risk factor',
                        'experience_factor' => 'Experience-based factor',
                        'accident_factor' => 'Accident history factor',
                        'base_premium' => 'Calculated premium amount',
                        'risk_category' => 'Risk category classification'
                    ]
                ]
            ]
        ];
    }
}