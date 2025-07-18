import { RuleFlowConfig, Formula } from '../types.js';
import { RuleFlowException } from '../exceptions/RuleFlowException.js';

// Try to import advanced types (might be in functions/types.ts)
type AccumulativeRule = any; // Fallback type
type ScoringConfig = any; // Fallback type

export interface ConfigValidationResult {
  valid: boolean;
  errors: string[];
  warnings: string[];
}

export class ConfigValidator {
  
  /**
   * 🆕 Enhanced validate with detailed errors and warnings (เหมือน PHP version)
   */
  validateConfig(config: RuleFlowConfig): ConfigValidationResult {
    const errors: string[] = [];
    const warnings: string[] = [];

    // Basic structure validation
    if (!config || typeof config !== 'object') {
      return {
        valid: false,
        errors: ['Configuration must be an object'],
        warnings: []
      };
    }

    if (!config.formulas || !Array.isArray(config.formulas)) {
      return {
        valid: false,
        errors: ['Configuration must have formulas array'],
        warnings: []
      };
    }

    if (config.formulas.length === 0) {
      warnings.push('No formulas defined in configuration');
    }

    // Validate each formula
    config.formulas.forEach((formula, index) => {
      const formulaErrors = this.validateSingleFormula(formula, index);
      errors.push(...formulaErrors);
    });

    // Check for duplicate IDs
    const duplicateErrors = this.checkDuplicateIds(config.formulas);
    errors.push(...duplicateErrors);

    // Check for circular dependencies
    try {
      this.checkCircularDependencies(config.formulas);
    } catch (error) {
      errors.push(`Circular dependency detected: ${error.message}`);
    }

    return {
      valid: errors.length === 0,
      errors,
      warnings
    };
  }

  /**
   * Validate single formula (internal method that returns errors)
   */
  private validateSingleFormula(formula: Formula, index: number): string[] {
    const errors: string[] = [];
    const prefix = `Formula at index ${index}`;

    // Required fields
    if (!formula.id) {
      errors.push(`${prefix}: Missing required 'id' field`);
    } else if (typeof formula.id !== 'string' || formula.id.trim() === '') {
      errors.push(`${prefix}: 'id' must be a non-empty string`);
    }

    // Must have some execution logic
    if (!formula.formula && !formula.switch && !formula.when && !formula.set_vars && !formula.rules && !formula.scoring) {
      errors.push(`${prefix}: Must have 'formula', 'switch', 'when', 'set_vars', 'rules', or 'scoring'`);
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

    // Check for common syntax errors
    if (formula.formula) {
      // Unmatched parentheses
      const openParens = (formula.formula.match(/\(/g) || []).length;
      const closeParens = (formula.formula.match(/\)/g) || []).length;
      if (openParens !== closeParens) {
        errors.push(`${prefix}: Unmatched parentheses in formula`);
      }

      // Invalid operators
      if (/[\+\-\*\/]{2,}/.test(formula.formula)) {
        errors.push(`${prefix}: Invalid operator sequence in formula`);
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
        if (condition.result === undefined) {
          errors.push(`${prefix}: when[${condIndex}] must have 'result' value`);
        }
      });
    }

    return errors;
  }

  /**
   * Validate inputs array
   */
  private validateInputsArray(inputs: string[], prefix: string): string[] {
    const errors: string[] = [];

    if (!Array.isArray(inputs)) {
      errors.push(`${prefix}: 'inputs' must be an array`);
      return errors;
    }

    inputs.forEach((input, index) => {
      if (typeof input !== 'string' || input.trim() === '') {
        errors.push(`${prefix}: inputs[${index}] must be a non-empty string`);
      }
    });

    // Check for duplicates
    const duplicates = inputs.filter((item, index) => inputs.indexOf(item) !== index);
    if (duplicates.length > 0) {
      errors.push(`${prefix}: Duplicate inputs: ${duplicates.join(', ')}`);
    }

    return errors;
  }

  /**
   * Validate rules array (for accumulative scoring)
   */
  private validateRules(rules: any[], prefix: string): string[] {
    const errors: string[] = [];

    if (!Array.isArray(rules)) {
      errors.push(`${prefix}: 'rules' must be an array`);
      return errors;
    }

    rules.forEach((rule, index) => {
      if (!rule.var) {
        errors.push(`${prefix}: rules[${index}] must have 'var' property`);
      }

      // Must have either ranges, if condition, or direct score/result
      if (!rule.ranges && !rule.if && rule.score === undefined && rule.result === undefined) {
        errors.push(`${prefix}: rules[${index}] must have 'ranges', 'if', 'score', or 'result'`);
      }

      // Validate ranges if present
      if (rule.ranges && Array.isArray(rule.ranges)) {
        rule.ranges.forEach((range: any, rangeIndex: number) => {
          if (!range.if) {
            errors.push(`${prefix}: rules[${index}].ranges[${rangeIndex}] must have 'if' condition`);
          }
          if (range.score === undefined && range.result === undefined) {
            errors.push(`${prefix}: rules[${index}].ranges[${rangeIndex}] must have 'score' or 'result'`);
          }
        });
      }
    });

    return errors;
  }

  /**
   * Validate scoring configuration
   */
  private validateScoring(scoring: any, prefix: string): string[] {
    const errors: string[] = [];

    if (typeof scoring !== 'object' || scoring === null) {
      errors.push(`${prefix}: 'scoring' must be an object`);
      return errors;
    }

    // Multi-dimensional scoring validation
    if (scoring.ifs) {
      if (!scoring.ifs.vars || !Array.isArray(scoring.ifs.vars) || scoring.ifs.vars.length === 0) {
        errors.push(`${prefix}: Multi-dimensional scoring must have 'vars' array`);
      }

      if (!scoring.ifs.tree || !Array.isArray(scoring.ifs.tree) || scoring.ifs.tree.length === 0) {
        errors.push(`${prefix}: Multi-dimensional scoring must have 'tree' array`);
      } else {
        // Validate tree nodes
        scoring.ifs.tree.forEach((node: any, nodeIndex: number) => {
          if (!node.if) {
            errors.push(`${prefix}: scoring.ifs.tree[${nodeIndex}] must have 'if' condition`);
          }

          if (node.ranges) {
            node.ranges.forEach((range: any, rangeIndex: number) => {
              if (!range.if) {
                errors.push(`${prefix}: scoring.ifs.tree[${nodeIndex}].ranges[${rangeIndex}] must have 'if' condition`);
              }
              if (range.score === undefined && range.result === undefined) {
                errors.push(`${prefix}: scoring.ifs.tree[${nodeIndex}].ranges[${rangeIndex}] must have 'score' or 'result'`);
              }
            });
          } else {
            // If no ranges, must have direct score/result
            if (node.score === undefined && node.result === undefined) {
              errors.push(`${prefix}: scoring.ifs.tree[${nodeIndex}] without ranges must have 'score' or 'result'`);
            }
          }
        });
      }
    }
    // Simple scoring validation
    else if (scoring.if) {
      if (scoring.score === undefined && scoring.result === undefined) {
        errors.push(`${prefix}: Simple scoring must have 'score' or 'result'`);
      }
    }
    // Invalid scoring configuration
    else {
      if (scoring.score === undefined && scoring.result === undefined) {
        errors.push(`${prefix}: Scoring configuration must have either 'ifs' for multi-dimensional or 'if' for simple scoring, or direct 'score'/'result'`);
      }
    }

    return errors;
  }
  private checkDuplicateIds(formulas: Formula[]): string[] {
    const errors: string[] = [];
    const ids = formulas.map(f => f.id).filter(id => id); // Filter out undefined/null
    const duplicateIds = ids.filter((id, index) => ids.indexOf(id) !== index);
    
    if (duplicateIds.length > 0) {
      const uniqueDuplicates = [...new Set(duplicateIds)];
      errors.push(`Duplicate formula IDs found: ${uniqueDuplicates.join(', ')}`);
    }

    return errors;
  }

  /**
   * Check for circular dependencies (basic implementation)
   */
  private checkCircularDependencies(formulas: Formula[]): void {
    const dependencies: Record<string, string[]> = {};
    
    // Build dependency graph
    formulas.forEach(formula => {
      if (!formula.id) return;
      
      dependencies[formula.id] = [];
      
      // Check formula dependencies
      if (formula.formula) {
        const refs = formula.formula.match(/\$[a-zA-Z_]\w*/g) || [];
        refs.forEach(ref => {
          const refId = ref.substring(1); // Remove $
          dependencies[formula.id].push(refId);
        });
      }
    });

    // Simple circular dependency check
    const visited = new Set<string>();
    const visiting = new Set<string>();

    const visit = (id: string): void => {
      if (visiting.has(id)) {
        throw new Error(`Circular dependency involving ${id}`);
      }
      if (visited.has(id)) return;

      visiting.add(id);
      (dependencies[id] || []).forEach(depId => {
        if (dependencies[depId]) { // Only check if dependency exists in formulas
          visit(depId);
        }
      });
      visiting.delete(id);
      visited.add(id);
    };

    Object.keys(dependencies).forEach(id => {
      if (!visited.has(id)) {
        visit(id);
      }
    });
  }

  /**
   * Original validate method (เก็บไว้เพื่อ backward compatibility)
   */
  validate(config: RuleFlowConfig): void {
    const result = this.validateConfig(config);
    if (!result.valid) {
      throw new RuleFlowException(`Configuration validation failed: ${result.errors.join(', ')}`);
    }
  }

  /**
   * Helper method for validating single formula (backward compatibility - public method)
   */
  validateFormula(formula: Formula): void {
    const errors = this.validateSingleFormula(formula, 0); // Call internal method
    if (errors.length > 0) {
      throw new RuleFlowException(errors[0]); // Throw first error
    }
  }

  private validateSwitchFormulaLegacy(formula: Formula): void {
    if (!formula.when || formula.when.length === 0) {
      throw new RuleFlowException(`Switch formula '${formula.id}' must have when conditions`);
    }
  }
}