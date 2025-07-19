import { RuleFlowConfig, Formula } from '../types.js';

export interface ValidationResult {
  valid: boolean;
  errors: string[];
  warnings: string[];
}

export class ConfigValidator {
  /**
   * Validate complete RuleFlow configuration
   */
  validateConfig(config: RuleFlowConfig): ValidationResult {
    const errors: string[] = [];
    const warnings: string[] = [];

    // Check if config exists
    if (!config) {
      errors.push('Configuration is required');
      return { valid: false, errors, warnings };
    }

    // Check formulas array
    if (!config.formulas) {
      errors.push('Configuration must have formulas array');
      return { valid: false, errors, warnings };
    }

    if (!Array.isArray(config.formulas)) {
      errors.push('Formulas must be an array');
      return { valid: false, errors, warnings };
    }

    // Check for empty formulas (warning, not error)
    if (config.formulas.length === 0) {
      warnings.push('No formulas defined in configuration');
      return { valid: true, errors, warnings }; // Empty is valid but has warning
    }

    // Validate each formula
    config.formulas.forEach((formula, index) => {
      const formulaErrors = this.validateFormula(formula, `formulas[${index}]`);
      errors.push(...formulaErrors);
    });

    // Check for duplicate formula IDs
    const ids = config.formulas.map(f => f.id).filter(id => id);
    const duplicateIds = ids.filter((id, index) => ids.indexOf(id) !== index);
    if (duplicateIds.length > 0) {
      errors.push(`Duplicate formula IDs found: ${duplicateIds.join(', ')}`);
    }

    return {
      valid: errors.length === 0,
      errors,
      warnings
    };
  }

  /**
   * Validate individual formula
   */
  private validateFormula(formula: Formula, prefix: string): string[] {
    const errors: string[] = [];

    if (!formula) {
      errors.push(`${prefix}: Formula is required`);
      return errors;
    }

    // Check ID
    if (!formula.id || typeof formula.id !== 'string') {
      errors.push(`${prefix}: Formula must have a valid 'id'`);
    }

    // Check that formula has at least one execution method
    if (!formula.formula && !formula.switch && !formula.when && !formula.set_vars && !formula.rules && !formula.scoring) {
      errors.push(`${prefix}: Must have 'formula', 'switch', 'when', or 'set_vars'`);
    }

    // Validate formula structure
    if (formula.formula) {
      errors.push(...this.validateFormulaExpression(formula, prefix));
    }

    if (formula.switch) {
      errors.push(...this.validateSwitchFormula(formula, prefix));
    }

    if (formula.inputs) {
      errors.push(...this.validateInputsArray(formula.inputs, prefix));
    }

    if (formula.rules) {
      errors.push(...this.validateRules(formula.rules, prefix));
    }

    if (formula.scoring) {
      errors.push(...this.validateScoring(formula.scoring, prefix));
    }

    return errors;
  }

  /**
   * Validate formula expression
   */
  private validateFormulaExpression(formula: Formula, prefix: string): string[] {
    const errors: string[] = [];

    if (typeof formula.formula !== 'string' || formula.formula.trim() === '') {
      errors.push(`${prefix}: 'formula' must be a non-empty string`);
    }

    if (formula.formula) {
      // Unmatched parentheses
      const openParens = (formula.formula.match(/\(/g) || []).length;
      const closeParens = (formula.formula.match(/\)/g) || []).length;
      if (openParens !== closeParens) {
        errors.push(`${prefix}: Unmatched parentheses in formula`);
      }

      // ✅ Fixed: รองรับ ** operator
      if (/[\+\-\/]{2,}|\*{3,}/.test(formula.formula)) {
        errors.push(`${prefix}: Invalid operator sequence in formula`);
      }

      // Check for incomplete expressions
      if (/[+\-*/]\s*$/.test(formula.formula.trim())) {
        errors.push(`${prefix}: Formula ends with operator`);
      }
    }

    return errors;
  }

  /**
   * Validate switch formula
   */
  private validateSwitchFormula(formula: Formula, prefix: string): string[] {
    const errors: string[] = [];

    if (!formula.switch || typeof formula.switch !== 'string') {
      errors.push(`${prefix}: 'switch' must be a non-empty string`);
    }

    if (!formula.when || !Array.isArray(formula.when)) {
      errors.push(`${prefix}: Switch formula must have 'when' array`);
    } else if (formula.when.length === 0) {
      errors.push(`${prefix}: Switch formula 'when' array cannot be empty`);
    } else {
      // Validate when conditions
      formula.when.forEach((condition, condIndex) => {
        if (!condition.if) {
          errors.push(`${prefix}: when[${condIndex}] must have 'if' condition`);
        }
        if (condition.result === undefined && condition.result === null) {
          errors.push(`${prefix}: when[${condIndex}] must have 'result'`);
        }
      });
    }

    return errors;
  }

  /**
   * Validate inputs array
   */
  private validateInputsArray(inputs: any, prefix: string): string[] {
    const errors: string[] = [];

    if (!Array.isArray(inputs)) {
      errors.push(`${prefix}: 'inputs' must be an array`);
    } else {
      inputs.forEach((input, index) => {
        if (typeof input !== 'string' || input.trim() === '') {
          errors.push(`${prefix}: inputs[${index}] must be a non-empty string`);
        }
      });
    }

    return errors;
  }

  /**
   * Validate rules (for scoring) - Updated to be more flexible
   */
  private validateRules(rules: any, prefix: string): string[] {
    const errors: string[] = [];

    if (!Array.isArray(rules)) {
      errors.push(`${prefix}: 'rules' must be an array`);
    } else {
      rules.forEach((rule, index) => {
        // ต้องมี var หรือ variable อย่างน้อย
        const hasVar = rule.var || rule.variable;
        if (!hasVar) {
          errors.push(`${prefix}: rules[${index}] must have 'var' or 'variable'`);
        }
        // ไม่บังคับให้มี condition และ score เสมอไป
        // เพราะ rules บางอันอาจมีโครงสร้างแตกต่างกัน
      });
    }

    return errors;
  }

  /**
   * Validate scoring configuration
   */
  private validateScoring(scoring: any, prefix: string): string[] {
    const errors: string[] = [];

    if (typeof scoring !== 'object' || scoring === null) {
      errors.push(`${prefix}: 'scoring' must be an object`);
    }

    // Add more specific scoring validation as needed

    return errors;
  }
}