import { RuleFlowConfig } from './types';
import { FormulaProcessor } from './core/FormulaProcessor';
import { ConfigValidator } from './validators/ConfigValidator';
import { FunctionRegistry } from './functions/FunctionRegistry';
import { RuleFlowException } from './exceptions/RuleFlowException';

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
    // Validate configuration first
    const validation = this.validator.validateConfig(config);
    if (!validation.valid) {
      throw new RuleFlowException(`Configuration validation failed: ${validation.errors.join(', ')}`);
    }

    // Process formulas
    return this.processor.process(config.formulas, inputs);
  }

  /**
   * Test configuration with sample data (เหมือน PHP testConfig)
   */
  async testConfig(config: RuleFlowConfig, sampleInputs: Record<string, any>): Promise<{
    valid: boolean;
    errors: string[];
    warnings: string[];
    test_result?: Record<string, any>;
    execution_time?: number;
  }> {
    const startTime = Date.now();

    try {
      // 1. Validate configuration first
      const configValidation = this.validator.validateConfig(config);

      if (!configValidation.valid) {

        return {
          valid: false,
          errors: configValidation.errors,
          warnings: configValidation.warnings || []
        };
      }

      // 2. Try to evaluate with sample inputs
      try {
        const result = await this.evaluate(config, sampleInputs);
        const executionTime = Date.now() - startTime;

        return {
          valid: true,
          errors: [],
          warnings: configValidation.warnings || [],
          test_result: result,
          execution_time: executionTime
        };

      } catch (evalError: any) {


        return {
          valid: false,
          errors: [`Test execution failed: ${evalError.message}`],
          warnings: configValidation.warnings || []
        };
      }

    } catch (configError: any) {


      return {
        valid: false,
        errors: [`Configuration test failed: ${configError.message}`],
        warnings: []
      };
    }
  }

  /**
   * Validate multiple configurations (batch validation)
   */
  validateBatch(configs: RuleFlowConfig[]): Array<{
    index: number;
    valid: boolean;
    errors: string[];
    warnings: string[];
  }> {
    return configs.map((config, index) => {
      const result = this.validator.validateConfig(config);
      return {
        index,
        valid: result.valid,
        errors: result.errors,
        warnings: result.warnings
      };
    });
  }

  /**
   * Get function registry for custom function management
   */
  getFunctionRegistry(): FunctionRegistry {
    return this.functionRegistry;
  }

  /**
   * Get available functions (alias for getFunctionRegistry().listFunctions())
   */
  getAvailableFunctions(): Array<{ name: string; category?: string; description?: string; }> {
    return this.functionRegistry.listFunctions();
  }

  /**
   * Get system information
   */
  getSystemInfo(): { version: string; engine: string; features: string[] } {
    return {
      version: '1.0.0-typescript',
      engine: 'TypeScript',
      features: [
        'Formula Evaluation',
        'Switch Logic',
        'Nested Conditions',
        'Custom Functions',
        'Input Validation',
        'Configuration Testing',
        'Batch Processing'
      ]
    };
  }

  /**
   * Register a custom function
   */
  registerFunction(name: string, handler: (...args: any[]) => any, info?: {
    category?: string;
    description?: string;
    examples?: string[];
  }): void {
    this.functionRegistry.register(name, handler, info);
  }


}