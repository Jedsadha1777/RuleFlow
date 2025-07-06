<?php

require_once __DIR__ . '/../TemplateProviderInterface.php';

class RealEstateTemplates implements TemplateProviderInterface
{
    public function getCategory(): string
    {
        return 'real_estate';
    }
    
    public function getTemplateNames(): array
    {
        return ['property_valuation'];
    }
    
    public function getTemplates(): array
    {
        return [
            'property_valuation' => [
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
                    'description' => 'Estimate property value based on size, location, condition, and amenities',
                    'author' => 'RuleFlow',
                    'version' => '1.0.0',
                    'inputs' => [
                        'square_feet' => 'Property size in square feet',
                        'price_per_sqft' => 'Price per square foot in area',
                        'location_rating' => 'Location rating (Prime/Excellent/Good/Average/Below Average)',
                        'property_condition' => 'Property condition (Excellent/Good/Fair/Poor)',
                        'has_garage' => 'Has garage (1/0)',
                        'has_pool' => 'Has pool (1/0)',
                        'has_fireplace' => 'Has fireplace (1/0)',
                        'updated_kitchen' => 'Has updated kitchen (1/0)'
                    ],
                    'outputs' => [
                        'size_value' => 'Base value from size',
                        'estimated_value' => 'Total estimated property value',
                        'value_category' => 'Property value category'
                    ]
                ]
            ]
        ];
    }
}