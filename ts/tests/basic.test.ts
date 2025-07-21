import { describe, it, expect } from 'vitest'
import { RuleFlow } from '../src/index'

describe('RuleFlow', () => {
  it('should evaluate basic expression', async () => {
    const engine = new RuleFlow()
    
    const config = {
      formulas: [{
        id: 'add',
        formula: 'a + b',
        inputs: ['a', 'b']
      }]
    }
    
    const result = await engine.evaluate(config, { a: 1, b: 2 })
    expect(result.add).toBe(3)
  })
})