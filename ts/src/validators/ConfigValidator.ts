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

    // Must have either formula, switch, rules, or scoring
    if (!formula.formula && !formula.switch && !formula.rules && !formula.scoring) {
      throw new RuleFlowException(`Formula '${formula.id}' must have either 'formula', 'switch', 'rules', or 'scoring'`);
    }

    // Validate specific formula types
    if (formula.switch) {
      this.validateSwitchFormula(formula);
    }

    if (formula.rules) {
      this.validateAccumulativeRules(formula);
    }

    if (formula.scoring) {
      this.validateScoringConfig(formula);
    }
  }

  private validateSwitchFormula(formula: Formula): void {
    if (!formula.when || formula.when.length === 0) {
      throw new RuleFlowException(`Switch formula '${formula.id}' must have when conditions`);
    }
  }

  private validateAccumulativeRules(formula: Formula): void {
    if (!formula.rules || formula.rules.length === 0) {
      throw new RuleFlowException(`Accumulative scoring formula '${formula.id}' must have rules`);
    }

    for (const rule of formula.rules) {
      if (!rule.var) {
        throw new RuleFlowException(`Rule in formula '${formula.id}' must have a var property`);
      }

      // Must have either ranges, if condition, or direct score
      if (!rule.ranges && !rule.if && rule.score === undefined && rule.result === undefined) {
        throw new RuleFlowException(`Rule for '${rule.var}' in formula '${formula.id}' must have ranges, if condition, or score/result`);
      }

      // Validate ranges if present
      if (rule.ranges) {
        for (const range of rule.ranges) {
          if (!range.if) {
            throw new RuleFlowException(`Range in rule '${rule.var}' must have if condition`);
          }
          if (range.score === undefined && range.result === undefined) {
            throw new RuleFlowException(`Range in rule '${rule.var}' must have score or result`);
          }
        }
      }
    }
  }

  private validateScoringConfig(formula: Formula): void {
    if (!formula.scoring) {
      throw new RuleFlowException(`Scoring formula '${formula.id}' must have scoring configuration`);
    }

    const scoring = formula.scoring;

    // Multi-dimensional scoring validation
    if (scoring.ifs) {
      if (!scoring.ifs.vars || !Array.isArray(scoring.ifs.vars) || scoring.ifs.vars.length === 0) {
        throw new RuleFlowException(`Multi-dimensional scoring in formula '${formula.id}' must have vars array`);
      }

      if (!scoring.ifs.tree || !Array.isArray(scoring.ifs.tree) || scoring.ifs.tree.length === 0) {
        throw new RuleFlowException(`Multi-dimensional scoring in formula '${formula.id}' must have tree array`);
      }

      // Validate tree nodes
      for (const node of scoring.ifs.tree) {
        if (!node.if) {
          throw new RuleFlowException(`Tree node in formula '${formula.id}' must have if condition`);
        }

        // Validate ranges if present
        if (node.ranges) {
          for (const range of node.ranges) {
            if (!range.if) {
              throw new RuleFlowException(`Range in tree node must have if condition`);
            }
            if (range.score === undefined && range.result === undefined) {
              throw new RuleFlowException(`Range in tree node must have score or result`);
            }
          }
        } else {
          // If no ranges, must have direct score/result
          if (node.score === undefined && node.result === undefined) {
            throw new RuleFlowException(`Tree node without ranges must have score or result`);
          }
        }
      }
    }
    // Simple scoring validation
    else if (scoring.if) {
      if (scoring.score === undefined && scoring.result === undefined) {
        throw new RuleFlowException(`Simple scoring in formula '${formula.id}' must have score or result`);
      }
    }
    // Invalid scoring configuration
    else {
      throw new RuleFlowException(`Invalid scoring configuration in formula '${formula.id}': must have either 'ifs' for multi-dimensional or 'if' for simple scoring`);
    }
  }
}