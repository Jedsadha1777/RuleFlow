export interface FunctionTemplate {
  functions: Record<string, (...args: any[]) => any>;
  info: {
    name: string;
    category: string;
    version: string;
    description: string;
    functions: Record<string, {
      description: string;
      parameters: string[];
      returnType: string;
      examples?: Array<{
        code: string;
        description: string;
        result: any;
      }>;
    }>;
  };
}

export const HOTEL_TEMPLATE: FunctionTemplate = {
  functions: {
    is_weekend: (date: string | Date) => {
      const d = new Date(date);
      if (isNaN(d.getTime())) throw new Error('Invalid date format');
      const dayOfWeek = d.getDay();
      return dayOfWeek === 0 || dayOfWeek === 6;
    },

    is_holiday: (date: string | Date, country: string = 'US') => {
      const d = new Date(date);
      if (isNaN(d.getTime())) throw new Error('Invalid date format');
      
      const month = d.getMonth() + 1;
      const day = d.getDate();
      
      // Common holidays (simplified)
      const holidays = {
        US: [[1, 1], [7, 4], [12, 25]], // New Year, July 4th, Christmas
        TH: [[1, 1], [12, 5], [12, 10], [12, 31]] // Thailand holidays
      };
      
      const countryHolidays = holidays[country as keyof typeof holidays] || holidays.US;
      return countryHolidays.some(([m, d]) => month === m && day === d);
    },

    get_season: (date: string | Date) => {
      const d = new Date(date);
      if (isNaN(d.getTime())) throw new Error('Invalid date format');
      
      const month = d.getMonth() + 1;
      if (month >= 12 || month <= 2) return 'winter';
      if (month >= 3 && month <= 5) return 'spring';
      if (month >= 6 && month <= 8) return 'summer';
      return 'fall';
    },

    days_until_checkin: (checkinDate: string | Date) => {
      const checkin = new Date(checkinDate);
      const today = new Date();
      if (isNaN(checkin.getTime())) throw new Error('Invalid checkin date');
      
      const diffTime = checkin.getTime() - today.getTime();
      return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    },

    room_occupancy_rate: (occupiedRooms: number, totalRooms: number) => {
      if (totalRooms <= 0) throw new Error('Total rooms must be positive');
      return Math.round((occupiedRooms / totalRooms) * 100 * 100) / 100; // 2 decimal places
    },

    calculate_room_tax: (basePrice: number, taxRate: number, location: string = 'city') => {
      const locationMultiplier = location === 'resort' ? 1.2 : 1.0;
      return Math.round(basePrice * (taxRate / 100) * locationMultiplier * 100) / 100;
    },

    weekend_pricing: (basePrice: number, weekendMultiplier: number = 1.5) => {
      return Math.round(basePrice * weekendMultiplier * 100) / 100;
    },

    seasonal_pricing: (basePrice: number, season: string) => {
      const seasonMultipliers = {
        'winter': 0.8,
        'spring': 1.0,
        'summer': 1.3,
        'fall': 1.1
      };
      
      const multiplier = seasonMultipliers[season as keyof typeof seasonMultipliers] || 1.0;
      return Math.round(basePrice * multiplier * 100) / 100;
    }
  },

  info: {
    name: 'Hotel Management Functions',
    category: 'Hotel',
    version: '1.0.0',
    description: 'Date calculations and business logic for hotel booking systems',
    functions: {
      is_weekend: {
        description: 'Check if a date falls on weekend (Saturday or Sunday)',
        parameters: ['date'],
        returnType: 'boolean',
        examples: [
          { code: "is_weekend('2024-01-06')", description: 'Saturday check', result: true },
          { code: "is_weekend('2024-01-08')", description: 'Monday check', result: false }
        ]
      },
      is_holiday: {
        description: 'Check if date is a holiday for specified country',
        parameters: ['date', 'country?'],
        returnType: 'boolean',
        examples: [
          { code: "is_holiday('2024-07-04', 'US')", description: 'July 4th check', result: true },
          { code: "is_holiday('2024-12-05', 'TH')", description: 'King\'s Birthday (Thailand)', result: true }
        ]
      },
      get_season: {
        description: 'Get season from date (winter, spring, summer, fall)',
        parameters: ['date'],
        returnType: 'string',
        examples: [
          { code: "get_season('2024-07-15')", description: 'Summer date', result: 'summer' },
          { code: "get_season('2024-12-15')", description: 'Winter date', result: 'winter' }
        ]
      },
      days_until_checkin: {
        description: 'Calculate days until check-in (can be negative if past)',
        parameters: ['checkinDate'],
        returnType: 'number',
        examples: [
          { code: "days_until_checkin('2024-12-25')", description: 'Christmas checkin', result: 30 }
        ]
      },
      room_occupancy_rate: {
        description: 'Calculate room occupancy percentage',
        parameters: ['occupiedRooms', 'totalRooms'],
        returnType: 'number',
        examples: [
          { code: "room_occupancy_rate(85, 100)", description: '85% occupancy', result: 85.00 }
        ]
      },
      calculate_room_tax: {
        description: 'Calculate room tax with location modifier (resort = 20% extra)',
        parameters: ['basePrice', 'taxRate', 'location?'],
        returnType: 'number',
        examples: [
          { code: "calculate_room_tax(100, 10, 'city')", description: 'City tax', result: 10.00 },
          { code: "calculate_room_tax(100, 10, 'resort')", description: 'Resort tax', result: 12.00 }
        ]
      },
      weekend_pricing: {
        description: 'Apply weekend pricing multiplier',
        parameters: ['basePrice', 'weekendMultiplier?'],
        returnType: 'number',
        examples: [
          { code: "weekend_pricing(100, 1.5)", description: '50% weekend markup', result: 150.00 }
        ]
      },
      seasonal_pricing: {
        description: 'Apply seasonal pricing based on season',
        parameters: ['basePrice', 'season'],
        returnType: 'number',
        examples: [
          { code: "seasonal_pricing(100, 'summer')", description: 'Summer pricing', result: 130.00 },
          { code: "seasonal_pricing(100, 'winter')", description: 'Winter pricing', result: 80.00 }
        ]
      }
    }
  }
};