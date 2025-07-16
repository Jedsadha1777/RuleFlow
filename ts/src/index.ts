export interface RuleFlowConfig {
  formulas: Formula[]
}

export interface Formula {
  id: string
  formula?: string
  inputs?: string[]
}

export class RuleFlow {
  async evaluate(config: RuleFlowConfig, inputs: Record<string, any>): Promise<Record<string, any>> {
    console.log('Evaluating config:', config)
    console.log('With inputs:', inputs)
    
    // Simple implementation for testing
    const result = { ...inputs }
    
    for (const formula of config.formulas) {
      if (formula.formula && formula.inputs) {
        // Very basic formula evaluation (just for testing)
        if (formula.formula === 'a + b' && formula.inputs.includes('a') && formula.inputs.includes('b')) {
          result[formula.id] = inputs.a + inputs.b
        }
      }
    }
    
    return result
  }
}

// Export for browser usage
if (typeof window !== 'undefined') {
  (window as any).RuleFlow = RuleFlow
}