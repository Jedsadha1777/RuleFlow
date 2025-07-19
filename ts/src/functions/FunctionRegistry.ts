import { FunctionHandler, FunctionInfo, FunctionCategories } from '../types';
import { RuleFlowException } from '../exceptions/RuleFlowException';

export class FunctionRegistry {
  private functions: Map<string, FunctionHandler> = new Map();
  private functionInfo: Map<string, FunctionInfo> = new Map();

  constructor() {
    this.registerBuiltInFunctions();
  }

  /**
   * Register a custom function
   */
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

  /**
   * Call a registered function
   */
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

  /**
   * Get list of available functions
   */
  getAvailableFunctions(): string[] {
    return Array.from(this.functions.keys());
  }

  /**
   * Get functions grouped by category
   */
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

  /**
   * Get function info
   */
  getFunctionInfo(name: string): FunctionInfo | undefined {
    return this.functionInfo.get(name);
  }

  /**
   * Check if function exists
   */
  hasFunction(name: string): boolean {
    return this.functions.has(name);
  }

  /**
   * List all registered functions
   */
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

  /**
   * Unregister a function (for testing/development)
   */
  unregister(name: string): boolean {
    const removed = this.functions.delete(name);
    this.functionInfo.delete(name);
    return removed;
  }

  /**
   * Register all built-in functions
   */
  private registerBuiltInFunctions(): void {
    this.registerMathFunctions();
    this.registerStatisticsFunctions();
    this.registerBusinessFunctions();
    this.registerUtilityFunctions();
  }

  /**
   * Register math functions
   */
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

    // Rounding
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

    this.register('log', (x: number) => {
      if (x <= 0) {
        throw new Error(`Cannot calculate logarithm of non-positive number: ${x}`);
      }
      return Math.log(x);
    }, {
      category: 'Math',
      description: 'Natural logarithm',
      parameters: ['number'],
      returnType: 'number'
    });

    this.register('exp', (x: number) => Math.exp(x), {
      category: 'Math',
      description: 'Exponential function',
      parameters: ['number'],
      returnType: 'number'
    });
  }

  /**
   * Register statistics functions
   */
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

  /**
   * Register business functions
   */
  private registerBusinessFunctions(): void {
  // แก้ไข percentage function
    this.register('percentage', (value: number, percent: number) => {
        return (value * percent) / 100;
    }, {
        category: 'Business',
        description: 'Calculate percentage amount of value',
        parameters: ['value', 'percent'],
        returnType: 'number'
    });

    // เพิ่ม percentage_of สำหรับการคำนวณแบบเดิม
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
        if (rate === 0) return principal;
        return principal * Math.pow(1 + rate / frequency, frequency * time);
    }, {
        category: 'Business',
        description: 'Compound interest calculation',
        parameters: ['principal', 'rate', 'time', 'frequency?'],
        returnType: 'number'
    });

    this.register('simple_interest', (principal: number, rate: number, time: number) => {
        return principal * (1 + rate * time);
    }, {
        category: 'Business',
        description: 'Simple interest calculation',
        parameters: ['principal', 'rate', 'time'],
        returnType: 'number'
    });

    this.register('discount', (price: number, discountRate: number) => {
        return price * (1 - discountRate);
    }, {
        category: 'Business',
        description: 'Apply discount to price',
        parameters: ['price', 'discountRate'],
        returnType: 'number'
    });

    this.register('markup', (cost: number, markupRate: number) => {
        return cost * (1 + markupRate);
    }, {
        category: 'Business',
        description: 'Apply markup to cost',
        parameters: ['cost', 'markupRate'],
        returnType: 'number'
    });
  }

  /**
   * Register utility functions
   */
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
      return args.find(arg => arg != null) ?? null;
    }, {
      category: 'Utility',
      description: 'Return first non-null value',
      parameters: ['...values'],
      returnType: 'any'
    });

    this.register('if_null', (value: any, defaultValue: any) => {
      return value != null ? value : defaultValue;
    }, {
      category: 'Utility',
      description: 'Return default if value is null',
      parameters: ['value', 'defaultValue'],
      returnType: 'any'
    });

    // Common business calculations
    this.register('bmi', (weight: number, height: number) => {
      if (height <= 0) throw new Error('Height must be greater than 0');
      const heightInMeters = height / 100; // Assume height in cm
      return Math.round((weight / (heightInMeters * heightInMeters)) * 100) / 100;
    }, {
      category: 'Utility',
      description: 'Calculate BMI',
      parameters: ['weight', 'height'],
      returnType: 'number'
    });
    this.register('age', (birthDate: string | Date) => {
      const birth = new Date(birthDate);
      const today = new Date();
      let age = today.getFullYear() - birth.getFullYear();
      const monthDiff = today.getMonth() - birth.getMonth();
      
      if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
      }
      
      return age;
    }, {
      category: 'Utility',
      description: 'Calculate age from birth date',
      parameters: ['birthDate'],
      returnType: 'number'
    });
  }

    
}