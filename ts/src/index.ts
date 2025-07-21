export { RuleFlow } from './RuleFlow';
export { RuleFlowException } from './exceptions/RuleFlowException';
export { 
  InputValidator, 
  type FieldValidationResult, 
  type ValidationStatus,
  type SecurityValidationResult,
  type SanitizationOptions
} from './validators/InputValidator.js';
export { ConfigValidator, type ConfigValidationResult } from './validators/ConfigValidator';
export { SchemaGenerator, type JSONSchema } from './generators/SchemaGenerator';
export * from './types.js';

// Export for browser usage
if (typeof window !== 'undefined') {
  const { RuleFlow } = await import('./RuleFlow.js');
  const { RuleFlowException } = await import('./exceptions/RuleFlowException');
  const { InputValidator } = await import('./validators/InputValidator');
  const { ConfigValidator } = await import('./validators/ConfigValidator');
  const { SchemaGenerator } = await import('./generators/SchemaGenerator');
  
  (window as any).RuleFlow = RuleFlow;
  (window as any).RuleFlowException = RuleFlowException;
  (window as any).InputValidator = InputValidator;
  (window as any).ConfigValidator = ConfigValidator;
  (window as any).SchemaGenerator = SchemaGenerator;
}