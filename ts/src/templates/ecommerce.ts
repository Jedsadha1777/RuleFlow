import { Template } from '../types';

export const DYNAMIC_PRICING: Template = {
  config: {
    formulas: [
      {
        id: 'demand_multiplier',
        switch: '$demand_level',
        when: [
          { if: { op: '==', value: 'very_high' }, result: 1.5 },
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
          { if: { op: '<', value: 10 }, result: 1.2 },
          { if: { op: '<', value: 50 }, result: 1.1 },
          { if: { op: '<', value: 100 }, result: 1.0 },
          { if: { op: '>=', value: 100 }, result: 0.9 }
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
        id: 'seasonal_adjustment',
        switch: '$season',
        when: [
          { if: { op: '==', value: 'holiday' }, result: 1.2 },
          { if: { op: '==', value: 'peak' }, result: 1.1 },
          { if: { op: '==', value: 'off_season' }, result: 0.9 }
        ],
        default: 1.0
      },
      {
        id: 'base_adjusted_price',
        formula: 'base_price * demand_multiplier * inventory_multiplier * seasonal_adjustment',
        inputs: ['base_price']
      },
      {
        id: 'final_price',
        formula: 'base_adjusted_price * (1 - customer_tier_discount)'
      },
      {
        id: 'price_category',
        switch: '$final_price',
        when: [
          { if: { op: '>', var: 'base_price', value: 1.3 }, result: 'Premium' },
          { if: { op: '>', var: 'base_price', value: 1.1 }, result: 'High' },
          { if: { op: '>', var: 'base_price', value: 0.9 }, result: 'Standard' }
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
    outputs: ['demand_multiplier', 'inventory_multiplier', 'final_price', 'price_category']
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
        final_price: 133.44, // 100 * 1.3 * 1.2 * 1.2 * 0.85
        price_category: 'Premium'
      }
    },
    {
      name: 'Low demand regular customer',
      description: 'Off-season with high inventory',
      inputs: { 
        base_price: 100, 
        demand_level: 'low', 
        inventory_level: 150, 
        customer_tier: 'Regular', 
        season: 'off_season' 
      },
      expectedOutputs: { 
        final_price: 64.8, // 100 * 0.8 * 0.9 * 0.9 * 1.0
        price_category: 'Discounted'
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
          { if: { op: '<=', value: 1 }, result: 5.00 },
          { if: { op: '<=', value: 5 }, result: 8.00 },
          { if: { op: '<=', value: 10 }, result: 15.00 },
          { if: { op: '<=', value: 20 }, result: 25.00 }
        ],
        default: 40.00
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
          { if: { op: '==', value: true }, result: 5.00 }
        ],
        default: 0.0
      },
      {
        id: 'insurance_cost',
        formula: 'order_value > 100 ? order_value * 0.01 : 0',
        inputs: ['order_value']
      },
      {
        id: 'base_shipping_cost',
        formula: 'weight_cost * distance_multiplier * speed_multiplier'
      },
      {
        id: 'total_shipping_cost',
        formula: 'base_shipping_cost + fragile_surcharge + insurance_cost'
      },
      {
        id: 'free_shipping_eligible',
        switch: 'check_free_shipping',
        when: [
          {
            if: {
              and: [
                { op: '>=', var: 'order_value', value: 50 },
                { op: '==', var: 'delivery_speed', value: 'standard' },
                { op: '!=', var: 'shipping_zone', value: 'international' }
              ]
            },
            result: true,
            set_vars: { '$final_shipping_cost': 0 }
          }
        ],
        default: false,
        set_vars: { '$final_shipping_cost': '$total_shipping_cost' }
      }
    ]
  },
  metadata: {
    name: 'Shipping Cost Calculator',
    description: 'Calculate shipping costs based on weight, distance, speed, and special handling',
    category: 'ecommerce',
    author: 'RuleFlow Team',
    version: '1.0.0',
    tags: ['shipping', 'logistics', 'calculator', 'ecommerce'],
    difficulty: 'intermediate',
    estimatedTime: '8 minutes',
    inputs: ['weight_kg', 'shipping_zone', 'delivery_speed', 'is_fragile', 'order_value'],
    outputs: ['weight_cost', 'distance_multiplier', 'total_shipping_cost', 'free_shipping_eligible', 'final_shipping_cost']
  },
  examples: [
    {
      name: 'Free shipping eligible',
      description: 'Large order with standard shipping',
      inputs: { 
        weight_kg: 2, 
        shipping_zone: 'regional', 
        delivery_speed: 'standard', 
        is_fragile: false, 
        order_value: 75,
        check_free_shipping: true
      },
      expectedOutputs: { 
        free_shipping_eligible: true, 
        final_shipping_cost: 0 
      }
    },
    {
      name: 'Express international shipping',
      description: 'Heavy fragile item with express international delivery',
      inputs: { 
        weight_kg: 15, 
        shipping_zone: 'international', 
        delivery_speed: 'express', 
        is_fragile: true, 
        order_value: 200,
        check_free_shipping: true
      },
      expectedOutputs: { 
        free_shipping_eligible: false, 
        total_shipping_cost: 112, // (25 * 3.5 * 2) + 5 + 2
        final_shipping_cost: 112
      }
    }
  ]
};