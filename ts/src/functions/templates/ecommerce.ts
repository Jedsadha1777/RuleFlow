import type { FunctionTemplate } from './index';

export const ECOMMERCE_TEMPLATE: FunctionTemplate = {
  functions: {
    shipping_cost: (weight: number, distance: number, shippingMethod: string = 'standard') => {
      const baseCost = weight * 2.5;
      const distanceCost = distance * 0.1;
      
      const methodMultiplier = {
        'standard': 1.0,
        'express': 1.8,
        'overnight': 2.5,
        'international': 3.0
      };
      
      const multiplier = methodMultiplier[shippingMethod as keyof typeof methodMultiplier] || 1.0;
      return Math.round((baseCost + distanceCost) * multiplier * 100) / 100;
    },

    loyalty_points: (purchaseAmount: number, memberLevel: string = 'basic', isPromotion: boolean = false) => {
      const levelMultipliers = {
        'basic': 1,
        'silver': 1.5,
        'gold': 2,
        'platinum': 3
      };
      
      const basePoints = purchaseAmount * 0.01; // 1 point per dollar
      const multiplier = levelMultipliers[memberLevel as keyof typeof levelMultipliers] || 1;
      const promotionMultiplier = isPromotion ? 2 : 1;
      
      return Math.floor(basePoints * multiplier * promotionMultiplier);
    },

    dynamic_pricing: (basePrice: number, demand: number, supply: number, competitorPrice?: number) => {
      const demandSupplyRatio = demand / supply;
      let priceMultiplier = 1;
      
      // Demand-supply adjustment
      if (demandSupplyRatio > 2) priceMultiplier = 1.2;
      else if (demandSupplyRatio > 1.5) priceMultiplier = 1.1;
      else if (demandSupplyRatio < 0.3) priceMultiplier = 0.8;  // แก้เงื่อนไข
      else if (demandSupplyRatio < 0.5) priceMultiplier = 0.9;
      
      let adjustedPrice = basePrice * priceMultiplier;
      
      // Competitor price adjustment
      if (competitorPrice && competitorPrice > 0) {
        if (adjustedPrice > competitorPrice * 1.1) {
          adjustedPrice = competitorPrice * 0.95; // Undercut by 5%
        }
      }
      
      return Math.round(adjustedPrice * 100) / 100; // Round to 2 decimals
    },

    cart_abandonment_score: (timeInCart: number, itemsCount: number, totalValue: number, hasAccount: boolean) => {
      let score = 0;
      
      // Time factor (minutes)
      if (timeInCart > 60) score += 30;
      else if (timeInCart > 30) score += 20;
      else if (timeInCart > 10) score += 10;
      
      // Items count
      if (itemsCount > 5) score += 20;
      else if (itemsCount > 2) score += 15;
      else score += 10;
      
      // Value factor
      if (totalValue > 500) score += 25;
      else if (totalValue > 100) score += 20;
      else score += 15;
      
      // Account factor
      if (hasAccount) score += 15;
      else score += 5;
      
      return Math.min(score, 100);
    },

    conversion_rate: (visitors: number, conversions: number) => {
      if (visitors <= 0) return 0;
      return Math.round((conversions / visitors) * 100 * 100) / 100;
    },

    customer_lifetime_value: (avgOrderValue: number, purchaseFrequency: number, customerLifespan: number) => {
      return Math.round(avgOrderValue * purchaseFrequency * customerLifespan * 100) / 100;
    },

    inventory_turnover: (costOfGoodsSold: number, averageInventory: number) => {
      if (averageInventory <= 0) throw new Error('Average inventory must be positive');
      return Math.round((costOfGoodsSold / averageInventory) * 100) / 100;
    },

    profit_margin: (revenue: number, costs: number) => {
      if (revenue <= 0) return 0;
      return Math.round(((revenue - costs) / revenue) * 100 * 100) / 100;
    }
  },

  info: {
    name: 'E-commerce Functions',
    category: 'E-commerce',
    version: '1.0.0',
    description: 'Business logic functions for online retail operations',
    functions: {
      shipping_cost: {
        description: 'Calculate shipping cost based on weight, distance, and method',
        parameters: ['weight', 'distance', 'shippingMethod?'],
        returnType: 'number',
        examples: [
          { code: "shipping_cost(5, 100, 'standard')", description: 'Standard shipping', result: 22.50 },
          { code: "shipping_cost(5, 100, 'express')", description: 'Express shipping', result: 40.50 }
        ]
      },
      loyalty_points: {
        description: 'Calculate loyalty points earned from purchase',
        parameters: ['purchaseAmount', 'memberLevel?', 'isPromotion?'],
        returnType: 'number',
        examples: [
          { code: "loyalty_points(100, 'gold', false)", description: 'Gold member purchase', result: 2 },
          { code: "loyalty_points(100, 'basic', true)", description: 'Basic member promo', result: 2 }
        ]
      },
      dynamic_pricing: {
        description: 'Calculate dynamic price based on demand, supply, and competition',
        parameters: ['basePrice', 'demand', 'supply', 'competitorPrice?'],
        returnType: 'number',
        examples: [
          { code: "dynamic_pricing(100, 150, 100)", description: 'High demand pricing', result: 110.00 },
          { code: "dynamic_pricing(100, 50, 200)", description: 'Low demand pricing', result: 90.00 }
        ]
      },
      cart_abandonment_score: {
        description: 'Calculate likelihood of cart abandonment (0-100)',
        parameters: ['timeInCart', 'itemsCount', 'totalValue', 'hasAccount'],
        returnType: 'number',
        examples: [
          { code: "cart_abandonment_score(45, 3, 250, true)", description: 'Medium risk cart', result: 65 }
        ]
      },
      conversion_rate: {
        description: 'Calculate conversion rate percentage from visitors to conversions',
        parameters: ['visitors', 'conversions'],
        returnType: 'number',
        examples: [
          { code: "conversion_rate(1000, 25)", description: '2.5% conversion rate', result: 2.50 }
        ]
      },
      customer_lifetime_value: {
        description: 'Calculate total value a customer brings over their lifetime',
        parameters: ['avgOrderValue', 'purchaseFrequency', 'customerLifespan'],
        returnType: 'number',
        examples: [
          { code: "customer_lifetime_value(150, 4, 3)", description: 'CLV over 3 years', result: 1800.00 }
        ]
      },
      inventory_turnover: {
        description: 'Calculate how many times inventory is sold per period',
        parameters: ['costOfGoodsSold', 'averageInventory'],
        returnType: 'number',
        examples: [
          { code: "inventory_turnover(500000, 100000)", description: '5x turnover rate', result: 5.00 }
        ]
      },
      profit_margin: {
        description: 'Calculate profit margin percentage',
        parameters: ['revenue', 'costs'],
        returnType: 'number',
        examples: [
          { code: "profit_margin(1000, 700)", description: '30% profit margin', result: 30.00 }
        ]
      }
    }
  }
};