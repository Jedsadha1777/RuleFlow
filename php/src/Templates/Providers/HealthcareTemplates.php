<?php

require_once __DIR__ . '/../TemplateProviderInterface.php';

class HealthcareTemplates implements TemplateProviderInterface
{
    public function getCategory(): string
    {
        return 'healthcare';
    }
    
    public function getTemplateNames(): array
    {
        return ['bmi_health_assessment'];
    }
    
    public function getTemplates(): array
    {
        return [
            'bmi_health_assessment' => [
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
                    'description' => 'Calculate BMI and provide health category with recommendations',
                    'author' => 'RuleFlow',
                    'version' => '1.0.0',
                    'inputs' => [
                        'weight' => 'Weight in kilograms (number)',
                        'height' => 'Height in meters (number)'
                    ],
                    'outputs' => [
                        'bmi' => 'Body Mass Index value',
                        'bmi_category' => 'BMI category (Underweight/Normal/Overweight/Obese)',
                        'health_risk_score' => 'Health risk score (1-5)',
                        'health_recommendations' => 'Health recommendations based on BMI'
                    ]
                ]
            ]
        ];
    }
}