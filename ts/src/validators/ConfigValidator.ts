import { RuleFlowConfig, Formula } from '../types.js';
import { RuleFlowException } from '../exceptions/RuleFlowException.js';

export class ConfigValidator {
  validate(config: RuleFlowConfig): void {
    if (!config.formulas || !Array.isArray(config.formulas)) {
      throw new RuleFlowException('Configuration must have formulas array');
    }

    for (const formula of config.formulas) {
      this.validateFormula(formula);
    }
  }

  private validateFormula(formula: Formula): void {
    if (!formula.id) {
      throw new RuleFlowException('Formula must have an id');
    }

    // Must have either formula or switch
    if (!formula.formula && !formula.switch) {
      throw new RuleFlowException(`Formula '${formula.id}' must have either 'formula' or 'switch'`);
    }

    // If has switch, must have when conditions
    if (formula.switch && (!formula.when || formula.when.length === 0)) {
      throw new RuleFlowException(`Switch formula '${formula.id}' must have when conditions`);
    }
  }
}