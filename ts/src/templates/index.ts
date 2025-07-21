import { TEMPLATES, CATEGORIES, TemplateCategory, TemplateName } from './config';
import { Template, RuleFlowConfig } from '../types';

/**
 * Get all available template names
 */
export function getAvailableTemplates(): string[] {
  return Object.keys(TEMPLATES);
}

/**
 * Get template by name
 */
export function getTemplate(name: TemplateName): Template | null {
  return TEMPLATES[name] || null;
}

/**
 * Get template config only (RuleFlowConfig)
 */
export function getTemplateConfig(name: TemplateName): RuleFlowConfig | null {
  const template = TEMPLATES[name];
  return template?.config || null;
}

/**
 * Get templates by category
 */
export function getTemplatesByCategory(category: TemplateCategory): string[] {
  return CATEGORIES[category] || [];
}

/**
 * Get all available categories
 */
export function getAvailableCategories(): TemplateCategory[] {
  return Object.keys(CATEGORIES) as TemplateCategory[];
}

/**
 * Search templates by keyword
 */
export function searchTemplates(keyword: string): string[] {
  const results: string[] = [];
  const searchTerm = keyword.toLowerCase();

  for (const [name, template] of Object.entries(TEMPLATES)) {
    const metadata = template.metadata;
    if (
      name.toLowerCase().includes(searchTerm) ||
      metadata.name.toLowerCase().includes(searchTerm) ||
      metadata.description.toLowerCase().includes(searchTerm) ||
      metadata.tags.some(tag => tag.toLowerCase().includes(searchTerm))
    ) {
      results.push(name);
    }
  }

  return results;
}

/**
 * Get template metadata
 */
export function getTemplateMetadata(name: TemplateName): any {
  const template = TEMPLATES[name];
  return template?.metadata || null;
}

/**
 * Get template examples
 */
export function getTemplateExamples(name: TemplateName): any[] {
  const template = TEMPLATES[name];
  return template?.examples || [];
}

/**
 * Check if template exists
 */
export function hasTemplate(name: string): boolean {
  return name in TEMPLATES;
}

/**
 * Get templates count by category
 */
export function getTemplateCounts(): Record<string, number> {
  const counts: Record<string, number> = {};
  
  for (const [category, templates] of Object.entries(CATEGORIES)) {
    counts[category] = templates.length;
  }
  
  return counts;
}

/**
 * Get template summary for listing
 */
export function getTemplateSummary(): Array<{
  name: string;
  title: string;
  category: string;
  difficulty: string;
  description: string;
}> {
  return Object.entries(TEMPLATES).map(([name, template]) => ({
    name,
    title: template.metadata.name,
    category: template.metadata.category,
    difficulty: template.metadata.difficulty,
    description: template.metadata.description
  }));
}