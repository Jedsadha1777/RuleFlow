import { RuleFlowException } from '../exceptions/RuleFlowException.js';

export class InputValidator {
  validate(inputs: Record<string, any>, requiredInputs: string[]): void {
    for (const required of requiredInputs) {
      if (!(required in inputs)) {
        throw new RuleFlowException(`Required input '${required}' is missing`);
      }
    }
  }
}