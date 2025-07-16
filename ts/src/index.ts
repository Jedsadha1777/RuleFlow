export { RuleFlow } from './RuleFlow.js';
export { RuleFlowException } from './exceptions/RuleFlowException.js';
export * from './types.js';

// Export for browser usage
if (typeof window !== 'undefined') {
  const { RuleFlow } = await import('./RuleFlow.js');
  const { RuleFlowException } = await import('./exceptions/RuleFlowException.js');
  
  (window as any).RuleFlow = RuleFlow;
  (window as any).RuleFlowException = RuleFlowException;
}