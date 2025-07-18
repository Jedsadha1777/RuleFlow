export { RuleFlow } from './RuleFlow.js';
export { RuleFlowException } from './exceptions/RuleFlowException.js';
export { 
  InputValidator, 
  type FieldValidationResult, 
  type ValidationStatus,
  type SecurityValidationResult,
  type SanitizationOptions
} from './validators/InputValidator.js';
export { ConfigValidator, type ConfigValidationResult } from './validators/ConfigValidator.js';
export { SchemaGenerator, type JSONSchema } from './generators/SchemaGenerator.js';
export * from './types.js';

// Export for browser usage
if (typeof window !== 'undefined') {
  const { RuleFlow } = await import('./RuleFlow.js');
  const { RuleFlowException } = await import('./exceptions/RuleFlowException.js');
  const { InputValidator } = await import('./validators/InputValidator.js');
  const { ConfigValidator } = await import('./validators/ConfigValidator.js');
  const { SchemaGenerator } = await import('./generators/SchemaGenerator.js');
  
  (window as any).RuleFlow = RuleFlow;
  (window as any).RuleFlowException = RuleFlowException;
  (window as any).InputValidator = InputValidator;
  (window as any).ConfigValidator = ConfigValidator;
  (window as any).SchemaGenerator = SchemaGenerator;
}