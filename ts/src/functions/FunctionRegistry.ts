import { FunctionHandler, FunctionInfo, FunctionCategories } from '../types';
import { RuleFlowException } from '../exceptions/RuleFlowException';

export class FunctionRegistry {
  private functions: Map<string, FunctionHandler> = new Map();
  private functionInfo: Map<string, FunctionInfo> = new Map();

  constructor() {
    this.registerBuiltInFunctions();
  }

  register(name: string, handler: FunctionHandler, info?: Partial<FunctionInfo>): void {
    this.functions.set(name, handler);
    
    if (info) {
      this.functionInfo.set(name, {
        name,
        category: info.category || 'Custom',
        description: info.description,
        parameters: info.parameters,
        returnType: info.returnType
      });
    }
  }

  call(name: string, args: any[]): any {
    const handler = this.functions.get(name);
    
    if (!handler) {
      throw new RuleFlowException(`Unknown function: ${name}. Available functions: ${Array.from(this.functions.keys()).join(', ')}`);
    }

    try {
      return handler(...args);
    } catch (error: any) {
      throw new RuleFlowException(`Error calling function '${name}': ${error.message}`);
    }
  }

  getAvailableFunctions(): string[] {
    return Array.from(this.functions.keys());
  }

  getFunctionsByCategory(): FunctionCategories {
    const categories: FunctionCategories = {
      Math: [],
      Statistics: [],
      Business: [],
      Utility: []
    };

    for (const [name, info] of this.functionInfo.entries()) {
      const category = info.category || 'Utility';
      if (!categories[category]) {
        categories[category] = [];
      }
      categories[category].push(name);
    }

    return categories;
  }

  getFunctionInfo(name: string): FunctionInfo | undefined {
    return this.functionInfo.get(name);
  }

  hasFunction(name: string): boolean {
    return this.functions.has(name);
  }

  listFunctions(): Array<{ name: string; category?: string; description?: string; }> {
    const functions: Array<{ name: string; category?: string; description?: string; }> = [];
    
    for (const [name] of this.functions.entries()) {
      const info = this.functionInfo.get(name);
      functions.push({
        name,
        category: info?.category,
        description: info?.description
      });
    }
    
    return functions;
  }

  unregister(name: string): boolean {
    const removed = this.functions.delete(name);
    this.functionInfo.delete(name);
    return removed;
  }

  private registerBuiltInFunctions(): void {
    this.registerMathFunctions();
    this.registerStatisticsFunctions();
    this.registerBusinessFunctions();
    this.registerUtilityFunctions();
  }

  // ====================================
  // ENHANCED MATH FUNCTIONS
  // ====================================
  private registerMathFunctions(): void {
    // Basic math
    this.register('abs', (x: number) => Math.abs(x), {
      category: 'Math',
      description: 'Absolute value',
      parameters: ['number'],
      returnType: 'number'
    });

    this.register('min', (...args: number[]) => Math.min(...args), {
      category: 'Math',
      description: 'Minimum value',
      parameters: ['...numbers'],
      returnType: 'number'
    });

    this.register('max', (...args: number[]) => Math.max(...args), {
      category: 'Math',
      description: 'Maximum value',
      parameters: ['...numbers'],
      returnType: 'number'
    });

    this.register('sqrt', (x: number) => {
      if (x < 0) {
        throw new Error(`Cannot calculate square root of negative number: ${x}`);
      }
      return Math.sqrt(x);
    }, {
      category: 'Math',
      description: 'Square root',
      parameters: ['number'],
      returnType: 'number'
    });

    // Rounding functions
    this.register('round', (x: number, precision: number = 0) => {
      const factor = Math.pow(10, precision);
      return Math.round(x * factor) / factor;
    }, {
      category: 'Math',
      description: 'Round to precision',
      parameters: ['number', 'precision?'],
      returnType: 'number'
    });

    this.register('ceil', (x: number) => Math.ceil(x), {
      category: 'Math',
      description: 'Round up',
      parameters: ['number'],
      returnType: 'number'
    });

    this.register('floor', (x: number) => Math.floor(x), {
      category: 'Math',
      description: 'Round down',
      parameters: ['number'],
      returnType: 'number'
    });

    this.register('pow', (x: number, y: number) => Math.pow(x, y), {
      category: 'Math',
      description: 'Power function',
      parameters: ['base', 'exponent'],
      returnType: 'number'
    });

    this.register('log', (x: number, base?: number) => {
      if (x <= 0) {
        throw new Error(`Cannot calculate logarithm of non-positive number: ${x}`);
      }
      if (base !== undefined) {
        return Math.log(x) / Math.log(base);
      }
      return Math.log(x);
    }, {
      category: 'Math',
      description: 'Natural logarithm (or with base)',
      parameters: ['number', 'base?'],
      returnType: 'number'
    });

    this.register('exp', (x: number) => Math.exp(x), {
      category: 'Math',
      description: 'Exponential function',
      parameters: ['number'],
      returnType: 'number'
    });

    this.register('sin', (x: number) => Math.sin(x), {
      category: 'Math',
      description: 'Sine function',
      parameters: ['radians'],
      returnType: 'number'
    });

    this.register('cos', (x: number) => Math.cos(x), {
      category: 'Math',
      description: 'Cosine function',
      parameters: ['radians'],
      returnType: 'number'
    });

    this.register('tan', (x: number) => Math.tan(x), {
      category: 'Math',
      description: 'Tangent function',
      parameters: ['radians'],
      returnType: 'number'
    });
  }

  // ====================================
  // STATISTICS FUNCTIONS (same as before)
  // ====================================
  private registerStatisticsFunctions(): void {
    this.register('avg', (...args: number[]) => {
      if (args.length === 0) return 0;
      return args.reduce((sum, x) => sum + x, 0) / args.length;
    }, {
      category: 'Statistics',
      description: 'Average/mean',
      parameters: ['...numbers'],
      returnType: 'number'
    });

    this.register('sum', (...args: number[]) => {
      return args.reduce((sum, x) => sum + x, 0);
    }, {
      category: 'Statistics',
      description: 'Sum of numbers',
      parameters: ['...numbers'],
      returnType: 'number'
    });

    this.register('count', (...args: any[]) => args.length, {
      category: 'Statistics',
      description: 'Count of arguments',
      parameters: ['...values'],
      returnType: 'number'
    });

    this.register('median', (...args: number[]) => {
      if (args.length === 0) return 0;
      const sorted = [...args].sort((a, b) => a - b);
      const mid = Math.floor(sorted.length / 2);
      return sorted.length % 2 === 0
        ? (sorted[mid - 1] + sorted[mid]) / 2
        : sorted[mid];
    }, {
      category: 'Statistics',
      description: 'Median value',
      parameters: ['...numbers'],
      returnType: 'number'
    });

    this.register('variance', (...args: number[]) => {
      if (args.length === 0) return 0;
      const mean = args.reduce((sum, x) => sum + x, 0) / args.length;
      const squaredDiffs = args.map(x => Math.pow(x - mean, 2));
      return squaredDiffs.reduce((sum, x) => sum + x, 0) / args.length;
    }, {
      category: 'Statistics',
      description: 'Variance',
      parameters: ['...numbers'],
      returnType: 'number'
    });

    this.register('stddev', (...args: number[]) => {
      if (args.length === 0) return 0;
      const mean = args.reduce((sum, x) => sum + x, 0) / args.length;
      const squaredDiffs = args.map(x => Math.pow(x - mean, 2));
      const variance = squaredDiffs.reduce((sum, x) => sum + x, 0) / args.length;
      return Math.sqrt(variance);
    }, {
      category: 'Statistics',
      description: 'Standard deviation',
      parameters: ['...numbers'],
      returnType: 'number'
    });
  }

  // ====================================
  // BUSINESS FUNCTIONS
  // ====================================
  private registerBusinessFunctions(): void {
    // Fixed percentage function
    this.register('percentage', (value: number, percent: number) => {
      return (value * percent) / 100;
    }, {
      category: 'Business',
      description: 'Calculate percentage amount of value',
      parameters: ['value', 'percent'],
      returnType: 'number'
    });

    this.register('percentage_of', (part: number, total: number) => {
      if (total === 0) return 0;
      return (part / total) * 100;
    }, {
      category: 'Business',
      description: 'Calculate what percentage part is of total',
      parameters: ['part', 'total'],
      returnType: 'number'
    });

    this.register('compound_interest', (principal: number, rate: number, time: number, frequency: number = 1) => {
      if (principal < 0 || rate < 0 || time < 0) {
        throw new Error('Principal, rate, and time must be non-negative');
      }
      if (rate === 0) return principal;
      return principal * Math.pow(1 + rate / frequency, frequency * time);
    }, {
      category: 'Business',
      description: 'Compound interest calculation',
      parameters: ['principal', 'rate', 'time', 'frequency?'],
      returnType: 'number'
    });

    this.register('simple_interest', (principal: number, rate: number, time: number) => {
      if (principal < 0 || rate < 0 || time < 0) {
        throw new Error('Principal, rate, and time must be non-negative');
      }
      return principal * (1 + rate * time);
    }, {
      category: 'Business',
      description: 'Simple interest calculation',
      parameters: ['principal', 'rate', 'time'],
      returnType: 'number'
    });

    this.register('discount', (originalPrice: number, discountPercent: number) => {
      if (discountPercent < 0 || discountPercent > 100) {
        throw new Error('Discount percentage must be between 0 and 100');
      }
      return originalPrice * (1 - discountPercent / 100);
    }, {
      category: 'Business',
      description: 'Apply discount percentage to price',
      parameters: ['originalPrice', 'discountPercent'],
      returnType: 'number'
    });

    this.register('markup', (cost: number, markupPercent: number) => {
      if (markupPercent < 0) {
        throw new Error('Markup percentage must be non-negative');
      }
      return cost * (1 + markupPercent / 100);
    }, {
      category: 'Business',
      description: 'Apply markup percentage to cost',
      parameters: ['cost', 'markupPercent'],
      returnType: 'number'
    });

    //  PMT (Payment) function for loan calculations
    this.register('pmt', (principal: number, rate: number, nper: number) => {
      if (principal <= 0 || rate < 0 || nper <= 0) {
        throw new Error('Invalid loan parameters: principal > 0, rate >= 0, nper > 0');
      }
      if (rate === 0) {
        return principal / nper;
      }
      return principal * (rate * Math.pow(1 + rate, nper)) / (Math.pow(1 + rate, nper) - 1);
    }, {
      category: 'Business',
      description: 'Calculate loan payment amount',
      parameters: ['principal', 'monthlyRate', 'numberOfPayments'],
      returnType: 'number'
    });

    // percentage_of function - Calculate what percentage part is of total
    this.register('percentage_of', (part: number, total: number) => {
      if (total === 0) return 0;
      return (part / total) * 100;
    }, {
      category: 'Business',
      description: 'Calculate what percentage part is of total',
      parameters: ['part', 'total'],
      returnType: 'number'
    });

    
  }  

  // ====================================
  // UTILITY FUNCTIONS
  // ====================================
  private registerUtilityFunctions(): void {
    this.register('clamp', (value: number, min: number, max: number) => {
      return Math.min(Math.max(value, min), max);
    }, {
      category: 'Utility',
      description: 'Clamp value between min and max',
      parameters: ['value', 'min', 'max'],
      returnType: 'number'
    });

    this.register('normalize', (value: number, min: number, max: number) => {
      if (max === min) return 0;
      return (value - min) / (max - min);
    }, {
      category: 'Utility',
      description: 'Normalize value to 0-1 range',
      parameters: ['value', 'min', 'max'],
      returnType: 'number'
    });

    this.register('coalesce', (...args: any[]) => {
      for (const arg of args) {
        if (arg !== null && arg !== undefined) {
          return arg;
        }
      }
      return null;
    }, {
      category: 'Utility',
      description: 'Return first non-null value',
      parameters: ['...values'],
      returnType: 'any'
    });

    this.register('if_null', (value: any, defaultValue: any) => {
      return (value === null || value === undefined) ? defaultValue : value;
    }, {
      category: 'Utility',
      description: 'Return default if value is null/undefined',
      parameters: ['value', 'defaultValue'],
      returnType: 'any'
    });

    // Utility functions
    this.register('bmi', (weight: number, heightInMeters: number) => {
      if (weight <= 0 || heightInMeters <= 0) {
        throw new Error('Weight and height must be positive numbers');
      }
      return weight / (heightInMeters * heightInMeters);
    }, {
      category: 'Utility',
      description: 'Calculate BMI',
      parameters: ['weight', 'height'],
      returnType: 'number'
    });

    this.register('age', (birthDate: string | Date) => {
      const birth = new Date(birthDate);
      const now = new Date();
      
      if (isNaN(birth.getTime())) {
        throw new Error('Invalid birth date format');
      }
      
      return Math.floor((now.getTime() - birth.getTime()) / (365.25 * 24 * 60 * 60 * 1000));
    }, {
      category: 'Utility',
      description: 'Calculate age from birth date',
      parameters: ['birthDate'],
      returnType: 'number'
    });

    // Date utility functions
    this.register('days_between', (date1: string | Date, date2: string | Date) => {
      const d1 = new Date(date1);
      const d2 = new Date(date2);
      
      if (isNaN(d1.getTime()) || isNaN(d2.getTime())) {
        throw new Error('Invalid date format');
      }
      
      const diffTime = Math.abs(d2.getTime() - d1.getTime());
      return Math.floor(diffTime / (1000 * 60 * 60 * 24));
    }, {
      category: 'Utility',
      description: 'Calculate days between two dates',
      parameters: ['date1', 'date2'],
      returnType: 'number'
    });

    this.register('is_weekend', (date: string | Date) => {
      const d = new Date(date);
      if (isNaN(d.getTime())) {
        throw new Error('Invalid date format');
      }
      const dayOfWeek = d.getDay();
      return dayOfWeek === 0 || dayOfWeek === 6; // Sunday = 0, Saturday = 6
    }, {
      category: 'Utility',
      description: 'Check if date is weekend',
      parameters: ['date'],
      returnType: 'boolean'
    });
  }
}