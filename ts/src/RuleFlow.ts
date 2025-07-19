// ts/src/RuleFlow.ts - Enhanced with Function Templates

import { RuleFlowConfig } from './types';
import { FormulaProcessor } from './core/FormulaProcessor';
import { ConfigValidator } from './validators/ConfigValidator';
import { FunctionRegistry } from './functions/FunctionRegistry';
import { RuleFlowException } from './exceptions/RuleFlowException';
import * as Templates from './templates/index';


import { 
  FunctionTemplateManager, 
  type TemplateType, 
  FUNCTION_TEMPLATES 
} from './functions/templates/index';

export class RuleFlow {
  private processor: FormulaProcessor;
  private validator: ConfigValidator;
  private functionRegistry: FunctionRegistry;
  private templateManager: FunctionTemplateManager; 

  constructor() {
    this.functionRegistry = new FunctionRegistry();
    this.processor = new FormulaProcessor(this.functionRegistry);
    this.validator = new ConfigValidator();
    this.templateManager = new FunctionTemplateManager(); 
  }

  // ====================================
  // CORE METHODS (unchanged)
  // ====================================
  
  async evaluate(config: RuleFlowConfig, inputs: Record<string, any>): Promise<Record<string, any>> {
    const validation = this.validator.validateConfig(config);
    if (!validation.valid) {
      throw new RuleFlowException(`Configuration validation failed: ${validation.errors.join(', ')}`);
    }
    return this.processor.process(config.formulas, inputs);
  }

  async testConfig(config: RuleFlowConfig, sampleInputs: Record<string, any>): Promise<{
    valid: boolean;
    errors: string[];
    warnings: string[];
    test_result?: Record<string, any>;
    execution_time?: number;
  }> {
    const startTime = Date.now();

    try {
      const configValidation = this.validator.validateConfig(config);

      if (!configValidation.valid) {
        return {
          valid: false,
          errors: configValidation.errors,
          warnings: configValidation.warnings || []
        };
      }

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

  getFunctionRegistry(): FunctionRegistry {
    return this.functionRegistry;
  }

  getAvailableFunctions(): Array<{ name: string; category?: string; description?: string; }> {
    return this.functionRegistry.listFunctions();
  }

  registerFunction(name: string, handler: (...args: any[]) => any, info?: {
    category?: string;
    description?: string;
    examples?: string[];
  }): void {
    this.functionRegistry.register(name, handler, info);
  }

  getSystemInfo(): { version: string; engine: string; features: string[] } {
    return {
      version: '1.0.0-typescript',
      engine: 'TypeScript',
      features: [
        'Formula Evaluation',
        'Switch Logic',
        'Nested Conditions',
        'Custom Functions',
        'Function Templates', 
        'Input Validation',
        'Configuration Testing',
        'Batch Processing'
      ]
    };
  }

  // ====================================
  // FUNCTION TEMPLATE METHODS
  // ====================================

  /**
   * Load a function template and register its functions
   */
  loadFunctionTemplate(templateName: TemplateType): void {
    const template = FUNCTION_TEMPLATES[templateName];
    if (!template) {
      throw new RuleFlowException(`Template '${templateName}' not found`);
    }

    // Register all functions from the template
    Object.entries(template.functions).forEach(([funcName, funcHandler]) => {
      const funcInfo = template.info.functions[funcName];
      this.functionRegistry.register(funcName, funcHandler, {
        category: template.info.category,
        description: funcInfo?.description,
        parameters: funcInfo?.parameters,
        returnType: funcInfo?.returnType
      });
    });

    // Mark as loaded
    this.templateManager.markAsLoaded(templateName);
  }

  /**
   * Load multiple function templates
   */
  loadFunctionTemplates(templateNames: TemplateType[]): void {
    templateNames.forEach(name => this.loadFunctionTemplate(name));
  }

  /**
   * Get all available function templates
   */
  getAvailableFunctionTemplates(): TemplateType[] {
    return this.templateManager.getAvailableTemplates();
  }

  /**
   * Get loaded function templates
   */
  getLoadedFunctionTemplates(): TemplateType[] {
    return this.templateManager.getLoadedTemplates();
  }

  /**
   * Get template information
   */
  getFunctionTemplateInfo(templateName: TemplateType): any {
    return this.templateManager.getTemplateInfo(templateName);
  }

  /**
   * Check if template is loaded
   */
  isFunctionTemplateLoaded(templateName: TemplateType): boolean {
    return this.templateManager.isTemplateLoaded(templateName);
  }

  /**
   * Search functions across all templates
   */
  searchTemplateFunctions(keyword: string): Array<{
    name: string;
    template: TemplateType;
    description: string;
    category: string;
  }> {
    return this.templateManager.searchFunctions(keyword);
  }

  /**
   * Get template summary for display
   */
  getFunctionTemplateSummary(): Array<{
    name: TemplateType;
    title: string;
    category: string;
    functionCount: number;
    description: string;
    loaded: boolean;
  }> {
    return this.templateManager.getTemplateSummary();
  }

  /**
   * Register a custom function template
   */
  registerFunctionTemplate(name: string, template: {
    functions: Record<string, (...args: any[]) => any>;
    info: {
      name: string;
      category: string;
      description: string;
      functions: Record<string, {
        description: string;
        parameters: string[];
        returnType: string;
      }>;
    };
  }): void {
    // Register all functions from the custom template
    Object.entries(template.functions).forEach(([funcName, funcHandler]) => {
      const funcInfo = template.info.functions[funcName];
      this.functionRegistry.register(funcName, funcHandler, {
        category: template.info.category,
        description: funcInfo?.description,
        parameters: funcInfo?.parameters,
        returnType: funcInfo?.returnType
      });
    });
  }

  // ====================================
  // TEMPLATE METHODS (unchanged)
  // ====================================
  
  getAvailableTemplates(): string[] {
    return Templates.getAvailableTemplates();
  }

  getTemplate(name: string): RuleFlowConfig | null {
    return Templates.getTemplateConfig(name as any);
  }

  getTemplatesByCategory(category: string): string[] {
    return Templates.getTemplatesByCategory(category as any);
  }

  getTemplateInfo(name: string): any {
    return Templates.getTemplateMetadata(name as any);
  }

  getTemplateExamples(name: string): any[] {
    return Templates.getTemplateExamples(name as any);
  }

  searchTemplates(keyword: string): string[] {
    return Templates.searchTemplates(keyword);
  }

  getTemplateCategories(): string[] {
    return Templates.getAvailableCategories();
  }

  async evaluateTemplate(templateName: string, inputs: Record<string, any>): Promise<Record<string, any>> {
    const config = this.getTemplate(templateName);
    if (!config) {
      throw new RuleFlowException(`Template '${templateName}' not found`);
    }
    return this.evaluate(config, inputs);
  }

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