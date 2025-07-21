import type { FunctionTemplate } from './index.js';

export const BUSINESS_TEMPLATE: FunctionTemplate = {
  functions: {
    // ================================
    // SHIPPING & LOGISTICS
    // ================================
    
    'shipping_cost': (weight: number, distance: number, serviceType: string = 'standard'): number => {
      if (weight <= 0 || distance <= 0) {
        throw new Error('Weight and distance must be positive');
      }
      
      const baseCost = weight * 2.5;
      const distanceCost = distance * 0.1;
      
      const multipliers: Record<string, number> = {
        'express': 2.0,
        'overnight': 3.0,
        'same_day': 4.0,
        'standard': 1.0
      };
      
      const multiplier = multipliers[serviceType] || 1.0;
      return (baseCost + distanceCost) * multiplier;
    },

    'tax_amount': (amount: number, rate: number, taxType: string = 'vat'): number => {
      if (amount < 0 || rate < 0) {
        throw new Error('Amount and rate must be non-negative');
      }
      
      switch (taxType.toLowerCase()) {
        case 'vat':
          return amount * (rate / 100);
        case 'sales':
          return amount * (rate / 100);
        case 'luxury':
          return amount > 100000 ? amount * (rate / 100) * 1.5 : amount * (rate / 100);
        case 'withholding':
          return amount * (rate / 100) * 0.75; // Reduced rate
        default:
          return amount * (rate / 100);
      }
    },

    'loyalty_points': (amount: number, tierLevel: string = 'bronze', bonusMultiplier: number = 1): number => {
      if (amount < 0) {
        throw new Error('Amount must be non-negative');
      }
      
      const tierMultipliers: Record<string, number> = {
        'bronze': 1.0,
        'silver': 1.5,
        'gold': 2.0,
        'platinum': 2.5,
        'diamond': 3.0
      };
      
      const basePoints = Math.floor(amount / 10); // 1 point per 10 units
      const tierMultiplier = tierMultipliers[tierLevel.toLowerCase()] || 1.0;
      
      return Math.floor(basePoints * tierMultiplier * bonusMultiplier);
    },

    // ================================
    // PRICING & DISCOUNTS
    // ================================
    
    'seasonal_multiplier': (date: string | Date, basePrice: number): number => {
      const dt = new Date(date);
      if (isNaN(dt.getTime())) {
        throw new Error(`Invalid date: ${date}`);
      }
      
      const month = dt.getMonth(); // 0-based
      
      // Seasonal pricing multipliers
      const seasonMultipliers: Record<string, number> = {
        'high_season': 1.5,    // Dec, Jan, Feb
        'peak_season': 2.0,    // Apr (Songkran)
        'low_season': 0.8,     // May, Jun, Sep, Oct
        'normal_season': 1.0   // Mar, Jul, Aug, Nov
      };
      
      let season: string;
      if ([11, 0, 1].includes(month)) season = 'high_season';  // Dec, Jan, Feb
      else if (month === 3) season = 'peak_season';            // April
      else if ([4, 5, 8, 9].includes(month)) season = 'low_season'; // May, Jun, Sep, Oct
      else season = 'normal_season';                           // Mar, Jul, Aug, Nov
      
      return basePrice * seasonMultipliers[season];
    },

    'tier_discount': (amount: number, customerTier: string, yearsActive: number = 0): number => {
      if (amount < 0) {
        throw new Error('Amount must be non-negative');
      }
      
      const tierDiscounts: Record<string, number> = {
        'bronze': 0.05,   // 5%
        'silver': 0.10,   // 10%
        'gold': 0.15,     // 15%
        'platinum': 0.20, // 20%
        'diamond': 0.25   // 25%
      };
      
      let discount = tierDiscounts[customerTier.toLowerCase()] || 0;
      
      // Loyalty bonus: +1% per year, max 10%
      const loyaltyBonus = Math.min(yearsActive * 0.01, 0.10);
      discount += loyaltyBonus;
      
      // Cap at 35% total discount
      discount = Math.min(discount, 0.35);
      
      return amount * discount;
    },

    'bulk_discount': (quantity: number, unitPrice: number): { total: number, discount: number, discountPercent: number } => {
      if (quantity <= 0 || unitPrice <= 0) {
        throw new Error('Quantity and unit price must be positive');
      }
      
      let discountPercent = 0;
      
      // Bulk discount tiers
      if (quantity >= 1000) discountPercent = 0.20;      // 20% for 1000+
      else if (quantity >= 500) discountPercent = 0.15;  // 15% for 500-999
      else if (quantity >= 100) discountPercent = 0.10;  // 10% for 100-499
      else if (quantity >= 50) discountPercent = 0.05;   // 5% for 50-99
      
      const subtotal = quantity * unitPrice;
      const discount = subtotal * discountPercent;
      const total = subtotal - discount;
      
      return {
        total: Math.round(total * 100) / 100,
        discount: Math.round(discount * 100) / 100,
        discountPercent: discountPercent * 100
      };
    },

    // ================================
    // TIME-BASED FUNCTIONS
    // ================================
    
    'working_hours_multiplier': (time: string, isWeekend: boolean = false): number => {
      const [hours, minutes] = time.split(':').map(Number);
      if (isNaN(hours) || isNaN(minutes) || hours < 0 || hours > 23 || minutes < 0 || minutes > 59) {
        throw new Error('Invalid time format. Use HH:MM');
      }
      
      const timeInMinutes = hours * 60 + minutes;
      
      // Weekend surcharge
      if (isWeekend) return 1.5;
      
      // Working hours: 8:00-17:00 = normal rate
      // Early hours: 6:00-8:00 = 1.2x
      // Late hours: 17:00-22:00 = 1.3x
      // Night hours: 22:00-6:00 = 1.8x
      
      if (timeInMinutes >= 480 && timeInMinutes < 1020) return 1.0;  // 8:00-17:00
      else if (timeInMinutes >= 360 && timeInMinutes < 480) return 1.2; // 6:00-8:00
      else if (timeInMinutes >= 1020 && timeInMinutes < 1320) return 1.3; // 17:00-22:00
      else return 1.8; // Night hours
    },

    'payment_processing_fee': (amount: number, paymentMethod: string): number => {
      if (amount <= 0) {
        throw new Error('Amount must be positive');
      }
      
      const feeStructures: Record<string, { fixed: number, percent: number, max?: number }> = {
        'credit_card': { fixed: 0, percent: 0.029, max: 500 },  // 2.9% with 500 cap
        'debit_card': { fixed: 0, percent: 0.019, max: 200 },   // 1.9% with 200 cap
        'bank_transfer': { fixed: 15, percent: 0.005 },          // 15 + 0.5%
        'e_wallet': { fixed: 0, percent: 0.015, max: 100 },     // 1.5% with 100 cap
        'cash': { fixed: 0, percent: 0 },                       // No fee
        'crypto': { fixed: 25, percent: 0.01 }                  // 25 + 1%
      };
      
      const structure = feeStructures[paymentMethod.toLowerCase()];
      if (!structure) {
        throw new Error(`Unknown payment method: ${paymentMethod}`);
      }
      
      const percentFee = amount * structure.percent;
      const totalFee = structure.fixed + percentFee;
      
      return structure.max ? Math.min(totalFee, structure.max) : totalFee;
    },

    // ================================
    // BUSINESS METRICS
    // ================================
    
    'customer_lifetime_value': (
      avgOrderValue: number, 
      purchaseFrequency: number, 
      customerLifespan: number,
      retentionRate: number = 0.8
    ): number => {
      if (avgOrderValue <= 0 || purchaseFrequency <= 0 || customerLifespan <= 0) {
        throw new Error('All values must be positive');
      }
      
      if (retentionRate <= 0 || retentionRate > 1) {
        throw new Error('Retention rate must be between 0 and 1');
      }
      
      // CLV = (Average Order Value × Purchase Frequency × Customer Lifespan) × Retention Rate
      return avgOrderValue * purchaseFrequency * customerLifespan * retentionRate;
    },

    'profit_margin': (revenue: number, costs: number): { profit: number, marginPercent: number, markup: number } => {
      if (revenue < 0 || costs < 0) {
        throw new Error('Revenue and costs must be non-negative');
      }
      
      if (revenue === 0) {
        return { profit: -costs, marginPercent: -100, markup: 0 };
      }
      
      const profit = revenue - costs;
      const marginPercent = (profit / revenue) * 100;
      const markup = costs > 0 ? (profit / costs) * 100 : 0;
      
      return {
        profit: Math.round(profit * 100) / 100,
        marginPercent: Math.round(marginPercent * 100) / 100,
        markup: Math.round(markup * 100) / 100
      };
    }
  },

  info: {
    name: 'Advanced Business Functions',
    category: 'Business',
    version: '1.0.0',
    description: 'Comprehensive business logic functions for e-commerce, logistics, and financial calculations',
    functions: {
      'shipping_cost': {
        description: 'Calculate shipping cost based on weight, distance, and service type',
        parameters: ['weight', 'distance', 'serviceType?'],
        returnType: 'number',
        examples: [
          {
            code: "shipping_cost(5, 100, 'express')",
            description: '5kg package, 100km, express delivery',
            result: 35
          }
        ]
      },

      'tax_amount': {
        description: 'Calculate tax amount based on amount, rate, and tax type',
        parameters: ['amount', 'rate', 'taxType?'],
        returnType: 'number',
        examples: [
          {
            code: "tax_amount(1000, 7, 'vat')",
            description: '1000 amount with 7% VAT',
            result: 70
          }
        ]
      },

      'loyalty_points': {
        description: 'Calculate loyalty points based on amount, tier level, and bonus multiplier',
        parameters: ['amount', 'tierLevel?', 'bonusMultiplier?'],
        returnType: 'number',
        examples: [
          {
            code: "loyalty_points(500, 'gold', 2)",
            description: '500 amount, gold tier, 2x bonus',
            result: 100
          }
        ]
      },

      'seasonal_multiplier': {
        description: 'Apply seasonal pricing multiplier based on date',
        parameters: ['date', 'basePrice'],
        returnType: 'number',
        examples: [
          {
            code: "seasonal_multiplier('2025-04-13', 1000)",
            description: 'Songkran peak season pricing',
            result: 2000
          }
        ]
      },

      'tier_discount': {
        description: 'Calculate tier-based discount with loyalty bonus',
        parameters: ['amount', 'customerTier', 'yearsActive?'],
        returnType: 'number',
        examples: [
          {
            code: "tier_discount(1000, 'gold', 3)",
            description: 'Gold tier with 3 years loyalty',
            result: 180
          }
        ]
      },

      'bulk_discount': {
        description: 'Calculate bulk discount with detailed breakdown',
        parameters: ['quantity', 'unitPrice'],
        returnType: 'object',
        examples: [
          {
            code: "bulk_discount(150, 10)",
            description: '150 units at 10 each',
            result: { total: 1350, discount: 150, discountPercent: 10 }
          }
        ]
      },

      'working_hours_multiplier': {
        description: 'Calculate time-based pricing multiplier',
        parameters: ['time', 'isWeekend?'],
        returnType: 'number',
        examples: [
          {
            code: "working_hours_multiplier('18:30', false)",
            description: 'Evening hours on weekday',
            result: 1.3
          }
        ]
      },

      'payment_processing_fee': {
        description: 'Calculate payment processing fees by method',
        parameters: ['amount', 'paymentMethod'],
        returnType: 'number',
        examples: [
          {
            code: "payment_processing_fee(1000, 'credit_card')",
            description: 'Credit card fee for 1000',
            result: 29
          }
        ]
      },

      'customer_lifetime_value': {
        description: 'Calculate customer lifetime value with retention',
        parameters: ['avgOrderValue', 'purchaseFrequency', 'customerLifespan', 'retentionRate?'],
        returnType: 'number',
        examples: [
          {
            code: "customer_lifetime_value(100, 12, 3, 0.8)",
            description: 'CLV calculation',
            result: 2880
          }
        ]
      },

      'profit_margin': {
        description: 'Calculate profit, margin percentage, and markup',
        parameters: ['revenue', 'costs'],
        returnType: 'object',
        examples: [
          {
            code: "profit_margin(1000, 600)",
            description: 'Profit analysis',
            result: { profit: 400, marginPercent: 40, markup: 66.67 }
          }
        ]
      }
    }
  }
};