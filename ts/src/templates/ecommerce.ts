import { Template } from '../types';

export const DYNAMIC_PRICING: Template = {
  config: {
    formulas: [
      {
        id: 'demand_multiplier',
        switch: '$demand_level',
        when: [
          { if: { op: '==', value: 'high' }, result: 1.3 },
          { if: { op: '==', value: 'medium' }, result: 1.0 },
          { if: { op: '==', value: 'low' }, result: 0.8 }
        ],
        default: 1.0
      },
      {
        id: 'inventory_multiplier',
        switch: '$inventory_level',
        when: [
          { if: { op: '<=', value: 10 }, result: 1.2 },
          { if: { op: '<=', value: 50 }, result: 1.0 },
          { if: { op: '>=', value: 100 }, result: 0.9 }
        ],
        default: 1.0
      },
      {
        id: 'season_multiplier',
        switch: '$season',
        when: [
          { if: { op: '==', value: 'holiday' }, result: 1.2 },
          { if: { op: '==', value: 'peak' }, result: 1.1 },
          { if: { op: '==', value: 'off_season' }, result: 0.9 }
        ],
        default: 1.0
      },
      {
        id: 'customer_tier_discount',
        switch: '$customer_tier',
        when: [
          { if: { op: '==', value: 'VIP' }, result: 0.15 },
          { if: { op: '==', value: 'Gold' }, result: 0.10 },
          { if: { op: '==', value: 'Silver' }, result: 0.05 }
        ],
        default: 0.0
      },
      {
        id: 'base_adjusted_price',
        formula: 'base_price * demand_multiplier * inventory_multiplier * season_multiplier',
        inputs: ['base_price', 'demand_multiplier', 'inventory_multiplier', 'season_multiplier']
      },
      {
        id: 'final_price',
        formula: 'base_adjusted_price * (1 - customer_tier_discount)',
        inputs: ['base_adjusted_price', 'customer_tier_discount']
      },
      {
        id: 'price_category',
        switch: '$final_price',
        when: [
          { if: { op: '>', value: 130 }, result: 'Premium' },
          { if: { op: '>', value: 110 }, result: 'High' },
          { if: { op: '>', value: 90 }, result: 'Standard' }
        ],
        default: 'Discounted'
      }
    ]
  },
  metadata: {
    name: 'Dynamic Pricing Engine',
    description: 'AI-powered dynamic pricing based on demand, inventory, and customer tier',
    category: 'ecommerce',
    author: 'RuleFlow Team',
    version: '1.0.0',
    tags: ['pricing', 'dynamic', 'ecommerce', 'ai', 'demand'],
    difficulty: 'advanced',
    estimatedTime: '15 minutes',
    inputs: ['base_price', 'demand_level', 'inventory_level', 'customer_tier', 'season'],
    outputs: ['demand_multiplier', 'inventory_multiplier', 'season_multiplier', 'customer_tier_discount', 'final_price', 'price_category']
  },
  examples: [
    {
      name: 'High demand VIP customer',
      description: 'Peak season with low inventory for VIP customer',
      inputs: { 
        base_price: 100, 
        demand_level: 'high', 
        inventory_level: 5, 
        customer_tier: 'VIP', 
        season: 'holiday' 
      },
      expectedOutputs: { 
        demand_multiplier: 1.3,
        inventory_multiplier: 1.2,
        season_multiplier: 1.2,
        customer_tier_discount: 0.15,
        final_price: 133.44, // 100 * 1.3 * 1.2 * 1.2 * (1 - 0.15) = 156 * 0.85 = 132.6
        price_category: 'Premium'
      }
    }
  ]
};

export const SHIPPING_CALCULATOR: Template = {
  config: {
    formulas: [
      {
        id: 'weight_cost',
        switch: '$weight_kg',
        when: [
          { if: { op: '<=', value: 1 }, result: 5 },
          { if: { op: '<=', value: 5 }, result: 8 },
          { if: { op: '<=', value: 10 }, result: 15 },
          { if: { op: '<=', value: 20 }, result: 25 }
        ],
        default: 40
      },
      {
        id: 'distance_multiplier',
        switch: '$shipping_zone',
        when: [
          { if: { op: '==', value: 'local' }, result: 1.0 },
          { if: { op: '==', value: 'regional' }, result: 1.5 },
          { if: { op: '==', value: 'national' }, result: 2.0 },
          { if: { op: '==', value: 'international' }, result: 3.5 }
        ],
        default: 2.0
      },
      {
        id: 'speed_multiplier',
        switch: '$delivery_speed',
        when: [
          { if: { op: '==', value: 'overnight' }, result: 3.0 },
          { if: { op: '==', value: 'express' }, result: 2.0 },
          { if: { op: '==', value: 'standard' }, result: 1.0 },
          { if: { op: '==', value: 'economy' }, result: 0.8 }
        ],
        default: 1.0
      },
      {
        id: 'fragile_surcharge',
        switch: '$is_fragile',
        when: [
          { if: { op: '==', value: true }, result: 5 }
        ],
        default: 0
      },
      {
        id: 'insurance_cost',
        switch: '$order_value',
        when: [
          { if: { op: '>', value: 100 }, result: 'order_value * 0.01' }
        ],
        default: 0
      },
      {
        id: 'base_shipping_cost',
        formula: 'weight_cost * distance_multiplier * speed_multiplier',
        inputs: ['weight_cost', 'distance_multiplier', 'speed_multiplier']
      },
      {
        id: 'total_shipping_cost',
        formula: 'base_shipping_cost + fragile_surcharge + insurance_cost',
        inputs: ['base_shipping_cost', 'fragile_surcharge', 'insurance_cost']
      },
      {
        id: 'free_shipping_eligible',
        switch: '$check_free_shipping',
        when: [
          { 
            if: { 
              and: [
                { op: '==', value: true },
                { op: '>=', var: 'order_value', value: 50 },
                { op: '!=', var: 'shipping_zone', value: 'international' }
              ]
            }, 
            result: true 
          }
        ],
        default: false
      },
      {
        id: 'final_shipping_cost',
        switch: '$free_shipping_eligible',
        when: [
          { if: { op: '==', value: true }, result: 0 }
        ],
        default: '$total_shipping_cost'
      }
    ]
  },
  metadata: {
    name: 'Shipping Cost Calculator',
    description: 'Dynamic shipping cost calculation with multiple factors',
    category: 'ecommerce',
    author: 'RuleFlow Team',
    version: '1.0.0',
    tags: ['shipping', 'logistics', 'cost', 'calculator'],
    difficulty: 'intermediate',
    estimatedTime: '10 minutes',
    inputs: ['weight_kg', 'shipping_zone', 'delivery_speed', 'is_fragile', 'order_value', 'check_free_shipping'],
    outputs: ['weight_cost', 'distance_multiplier', 'speed_multiplier', 'total_shipping_cost', 'free_shipping_eligible', 'final_shipping_cost']
  },
  examples: [
    {
      name: 'International express shipping',
      description: 'Heavy fragile item with express international shipping',
      inputs: { 
        weight_kg: 15,
        shipping_zone: 'international',
        delivery_speed: 'express',
        is_fragile: true,
        order_value: 200,
        check_free_shipping: true
      },
      expectedOutputs: { 
        weight_cost: 25,
        distance_multiplier: 3.5,
        speed_multiplier: 2.0,
        total_shipping_cost: 182, // (25 * 3.5 * 2) + 5 + 2 = 175 + 5 + 2 = 182
        free_shipping_eligible: false,
        final_shipping_cost: 182
      }
    }
  ]
};