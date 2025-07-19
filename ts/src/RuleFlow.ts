import { RuleFlowConfig } from './types';
import { FormulaProcessor } from './core/FormulaProcessor';
import { ConfigValidator } from './validators/ConfigValidator';
import { FunctionRegistry } from './functions/FunctionRegistry';
import { RuleFlowException } from './exceptions/RuleFlowException';
import * as Templates from './templates/index';

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

  /**
   * Get all available templates
   */
  getAvailableTemplates(): string[] {
    return Templates.getAvailableTemplates();
  }

  /**
   * Get template config for evaluation
   */
  getTemplate(name: string): RuleFlowConfig | null {
    return Templates.getTemplateConfig(name as any);
  }

  /**
   * Get templates by category
   */
  getTemplatesByCategory(category: string): string[] {
    return Templates.getTemplatesByCategory(category as any);
  }

  /**
   * Get template metadata
   */
  getTemplateInfo(name: string): any {
    return Templates.getTemplateMetadata(name as any);
  }

  /**
   * Get template examples
   */
  getTemplateExamples(name: string): any[] {
    return Templates.getTemplateExamples(name as any);
  }

  /**
   * Search templates
   */
  searchTemplates(keyword: string): string[] {
    return Templates.searchTemplates(keyword);
  }

  /**
   * Get template categories
   */
  getTemplateCategories(): string[] {
    return Templates.getAvailableCategories();
  }

  /**
   * Evaluate using template
   */
  async evaluateTemplate(templateName: string, inputs: Record<string, any>): Promise<Record<string, any>> {
    const config = this.getTemplate(templateName);
    if (!config) {
      throw new RuleFlowException(`Template '${templateName}' not found`);
    }
    return this.evaluate(config, inputs);
  }

  /**
   * Test template with example data
   */
  async testTemplate(templateName: string, exampleIndex: number = 0): Promise<any> {
    const examples = this.getTemplateExamples(templateName);
    if (!examples[exampleIndex]) {
      throw new RuleFlowException(`Example ${exampleIndex} not found for template '${templateName}'`);
    }

    const example = examples[exampleIndex];
    const result = await this.evaluateTemplate(templateName, example.inputs);
    
    return {
      template: templateName,
      example: example.name,
      inputs: example.inputs,
      outputs: result,
      expected: example.expectedOutputs
    };
  }


}