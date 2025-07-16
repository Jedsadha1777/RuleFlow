import { RuleFlowConfig } from './types.js';
import { FormulaProcessor } from './core/FormulaProcessor.js';
import { ConfigValidator } from './validators/ConfigValidator.js';
import { FunctionRegistry } from './functions/FunctionRegistry.js';

export class RuleFlow {
  private processor: FormulaProcessor;
  private validator: ConfigValidator;
  private functionRegistry: FunctionRegistry;

  constructor() {
    this.functionRegistry = new FunctionRegistry();
    this.processor = new FormulaProcessor(this.functionRegistry);
    this.validator = new ConfigValidator();
  }

  async evaluate(config: RuleFlowConfig, inputs: Record<string, any>): Promise<Record<string, any>> {
    // Validate configuration
    this.validator.validate(config);

    // Process formulas
    return this.processor.process(config.formulas, inputs);
  }

  /**
   * Get function registry for custom function management
   */
  getFunctionRegistry(): FunctionRegistry {
    return this.functionRegistry;
  }

  /**
   * Register a custom function
   */
  registerFunction(name: string, handler: (...args: any[]) => any, info?: {
    category?: string;
    description?: string;
    parameters?: string[];
    returnType?: string;
  }): void {
    this.functionRegistry.register(name, handler, info);
  }

  /**
   * Get available functions
   */
  getAvailableFunctions(): {
    functions: string[];
    categories: Record<string, string[]>;
  } {
    return {
      functions: this.functionRegistry.getAvailableFunctions(),
      categories: this.functionRegistry.getFunctionsByCategory()
    };
  }

  getSystemInfo(): Record<string, any> {
    return {
      version: '1.0.0-typescript',
      engine: 'TypeScript',
      functions: {
        total: this.functionRegistry.getAvailableFunctions().length,
        categories: Object.keys(this.functionRegistry.getFunctionsByCategory()).length
      }
    };
  }
}