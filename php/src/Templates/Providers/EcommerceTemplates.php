<?php

require_once __DIR__ . '/../TemplateProviderInterface.php';

class EcommerceTemplates implements TemplateProviderInterface
{
    public function getCategory(): string
    {
        return 'ecommerce';
    }
    
    public function getTemplateNames(): array
    {
        return ['dynamic_pricing', 'customer_ltv'];
    }
    
    public function getTemplates(): array
    {
        return [
            'dynamic_pricing' => [
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
                    'description' => 'AI-driven pricing based on demand, inventory, and competition',
                    'author' => 'RuleFlow',
                    'version' => '1.0.0',
                    'inputs' => [
                        'base_price' => 'Base product price',
                        'demand_level' => 'Demand level (High/Medium/Low)',
                        'inventory_level' => 'Current inventory quantity',
                        'price_vs_competitor' => 'Price ratio vs competitor',
                        'customer_tier' => 'Customer tier (VIP/Gold/Silver/Bronze)'
                    ],
                    'outputs' => [
                        'dynamic_price' => 'Price after demand/inventory adjustments',
                        'discount_eligible' => 'Customer discount percentage',
                        'final_price' => 'Final price after all adjustments'
                    ]
                ]
            ],
            
            'customer_ltv' => [
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
                    'description' => 'Calculate and categorize customer lifetime value',
                    'author' => 'RuleFlow',
                    'version' => '1.0.0',
                    'inputs' => [
                        'avg_order_value' => 'Average order value',
                        'orders_per_month' => 'Number of orders per month',
                        'customer_segment' => 'Customer segment (Premium/Standard/Basic)'
                    ],
                    'outputs' => [
                        'monthly_value' => 'Monthly customer value',
                        'annual_value' => 'Annual customer value',
                        'customer_ltv' => 'Customer lifetime value',
                        'ltv_category' => 'LTV category classification'
                    ]
                ]
            ]
        ];
    }
}