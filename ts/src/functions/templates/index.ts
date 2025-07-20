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

// Import individual templates
export { HOTEL_TEMPLATE } from './hotel.js';
export { FINANCIAL_TEMPLATE } from './financial.js';  
export { ECOMMERCE_TEMPLATE } from './ecommerce.js';
export { HEALTHCARE_TEMPLATE } from './healthcare.js';

// Import new templates
export { DATE_TEMPLATE } from './date.js';
export { BUSINESS_TEMPLATE } from './business.js';
export { EDUCATION_TEMPLATE } from './education.js';

// Re-import for local use
import { HOTEL_TEMPLATE } from './hotel.js';
import { FINANCIAL_TEMPLATE } from './financial.js';
import { ECOMMERCE_TEMPLATE } from './ecommerce.js';
import { HEALTHCARE_TEMPLATE } from './healthcare.js';
import { DATE_TEMPLATE } from './date.js';
import { BUSINESS_TEMPLATE } from './business.js';
import { EDUCATION_TEMPLATE } from './education.js';

// Template Registry
export const FUNCTION_TEMPLATES = {
  hotel: HOTEL_TEMPLATE,
  financial: FINANCIAL_TEMPLATE,
  ecommerce: ECOMMERCE_TEMPLATE,
  healthcare: HEALTHCARE_TEMPLATE,
  date: DATE_TEMPLATE,
  business: BUSINESS_TEMPLATE,
  education: EDUCATION_TEMPLATE
} as const;

export type TemplateType = keyof typeof FUNCTION_TEMPLATES;

// Template Manager Class
export class FunctionTemplateManager {
  private loadedTemplates: Set<TemplateType> = new Set();

  /**
   * Get all available template names
   */
  getAvailableTemplates(): TemplateType[] {
    return Object.keys(FUNCTION_TEMPLATES) as TemplateType[];
  }

  /**
   * Get template by name
   */
  getTemplate(name: TemplateType): FunctionTemplate | null {
    return FUNCTION_TEMPLATES[name] || null;
  }

  /**
   * Get template info only
   */
  getTemplateInfo(name: TemplateType): FunctionTemplate['info'] | null {
    const template = this.getTemplate(name);
    return template?.info || null;
  }

  /**
   * Get loaded templates
   */
  getLoadedTemplates(): TemplateType[] {
    return Array.from(this.loadedTemplates);
  }

  /**
   * Check if template is loaded
   */
  isTemplateLoaded(name: TemplateType): boolean {
    return this.loadedTemplates.has(name);
  }

  /**
   * Mark template as loaded (for tracking)
   */
  markAsLoaded(name: TemplateType): void {
    this.loadedTemplates.add(name);
  }

  /**
   * Get all functions from loaded templates
   */
  getAllLoadedFunctions(): Array<{
    name: string;
    template: TemplateType;
    category: string;
    description: string;
  }> {
    const functions: Array<{
      name: string;
      template: TemplateType;
      category: string;
      description: string;
    }> = [];

    for (const templateName of this.loadedTemplates) {
      const template = this.getTemplate(templateName);
      if (template) {
        Object.entries(template.info.functions).forEach(([funcName, funcInfo]) => {
          functions.push({
            name: funcName,
            template: templateName,
            category: template.info.category,
            description: funcInfo.description
          });
        });
      }
    }

    return functions;
  }

  /**
   * Search functions across all templates
   */
  searchFunctions(keyword: string): Array<{
    name: string;
    template: TemplateType;
    description: string;
    category: string;
  }> {
    const results: Array<{
      name: string;
      template: TemplateType;
      description: string;
      category: string;
    }> = [];

    const searchTerm = keyword.toLowerCase();

    for (const [templateName, template] of Object.entries(FUNCTION_TEMPLATES)) {
      Object.entries(template.info.functions).forEach(([funcName, funcInfo]) => {
        if (
          funcName.toLowerCase().includes(searchTerm) ||
          funcInfo.description.toLowerCase().includes(searchTerm)
        ) {
          results.push({
            name: funcName,
            template: templateName as TemplateType,
            description: funcInfo.description,
            category: template.info.category
          });
        }
      });
    }

    return results;
  }

  /**
   * Get template summary for CLI/UI
   */
  getTemplateSummary(): Array<{
    name: TemplateType;
    title: string;
    category: string;
    functionCount: number;
    description: string;
    loaded: boolean;
  }> {
    return Object.entries(FUNCTION_TEMPLATES).map(([name, template]) => ({
      name: name as TemplateType,
      title: template.info.name,
      category: template.info.category,
      functionCount: Object.keys(template.functions).length,
      description: template.info.description,
      loaded: this.isTemplateLoaded(name as TemplateType)
    }));
  }
}

// Create default manager instance
export const templateManager = new FunctionTemplateManager();