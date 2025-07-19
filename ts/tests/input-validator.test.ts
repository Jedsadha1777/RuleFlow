// ไฟล์: ts/src/validators/InputValidator.ts

import { RuleFlowConfig, Formula } from '../types.js';
import { RuleFlowException } from '../exceptions/RuleFlowException.js';

export interface FieldValidationResult {
  valid: boolean;
  converted_value: any;
  type: string;
  errors: string[];
  warnings: string[];
}

export interface ValidationStatus {
  ready_to_submit: boolean;
  validation_score: number;
  field_validation: {
    valid: boolean;
    missing_required: string[];
    overall_progress: number;
  };
  summary: {
    total_fields: number;
    provided_fields: number;
    missing_fields: number;
    invalid_fields: number;
  };
}

export interface SecurityValidationResult {
  safe: boolean;
  threats: Array<{
    field: string;
    threat_type: string;
    message: string;
  }>;
}

export interface SanitizationOptions {
  trimStrings?: boolean;
  removeHtml?: boolean;
  maxStringLength?: number;
  allowedKeys?: string[];
}

export class InputValidator {
  /**
   * Extract required inputs from configuration
   */
  extractRequiredInputs(config: RuleFlowConfig): string[] {
    const requiredInputs: string[] = [];

    config.formulas.forEach(formula => {
      // Extract from inputs array
      if (formula.inputs) {
        requiredInputs.push(...formula.inputs);
      }
      
      // Extract from formula string - parse variables
      if (formula.formula) {
        const variables = this.extractVariablesFromFormula(formula.formula);
        requiredInputs.push(...variables);
      }
      
      // Extract from switch variable
      if (formula.switch) {
        const switchVar = formula.switch.replace('$', '');
        requiredInputs.push(switchVar);
      }

      // Extract from rules (scoring)
      if ((formula as any).rules) {
        (formula as any).rules.forEach((rule: any) => {
          if (rule.var) {
            requiredInputs.push(rule.var);
          }
        });
      }

      // Extract from scoring configuration
      if ((formula as any).scoring?.ifs?.vars) {
        requiredInputs.push(...(formula as any).scoring.ifs.vars);
      }

      // Extract variables from when conditions
      if (formula.when) {
        formula.when.forEach(whenCondition => {
          if (whenCondition.if && typeof whenCondition.if === 'object') {
            const conditionVars = this.extractVariablesFromCondition(whenCondition.if);
            requiredInputs.push(...conditionVars);
          }
        });
      }
    });

    // Remove duplicates and filter reserved words
    const reserved = ['true', 'false', 'null', 'undefined', 'max', 'min', 'abs', 'sqrt', 'round'];
    return [...new Set(requiredInputs)].filter(input => 
      input && !reserved.includes(input.toLowerCase())
    );
  }

  /**
   * Extract only base inputs (not calculated fields)
   */
  private extractBaseInputs(config: RuleFlowConfig): string[] {
    const baseInputs: string[] = [];
    const calculatedFields: string[] = [];

    config.formulas.forEach(formula => {
      // Formula ID is a calculated field
      if (formula.id) {
        calculatedFields.push(formula.id);
      }

      // Extract from inputs array (these are base inputs)
      if (formula.inputs) {
        baseInputs.push(...formula.inputs);
      }
      
      // Extract from formula string - parse variables
      if (formula.formula) {
        const variables = this.extractVariablesFromFormula(formula.formula);
        variables.forEach(variable => {
          if (!calculatedFields.includes(variable)) {
            baseInputs.push(variable);
          }
        });
      }

      // Extract from rules (scoring) - these are base inputs
      if ((formula as any).rules) {
        (formula as any).rules.forEach((rule: any) => {
          if (rule.var && !calculatedFields.includes(rule.var)) {
            baseInputs.push(rule.var);
          }
        });
      }

      // Extract from scoring configuration
      if ((formula as any).scoring?.ifs?.vars) {
        (formula as any).scoring.ifs.vars.forEach((variable: string) => {
          if (!calculatedFields.includes(variable)) {
            baseInputs.push(variable);
          }
        });
      }

      // Extract variables from when conditions
      if (formula.when) {
        formula.when.forEach(whenCondition => {
          if (whenCondition.if && typeof whenCondition.if === 'object') {
            const conditionVars = this.extractVariablesFromCondition(whenCondition.if);
            conditionVars.forEach(variable => {
              if (!calculatedFields.includes(variable)) {
                baseInputs.push(variable);
              }
            });
          }
        });
      }
    });

    // Remove duplicates and filter reserved words
    const reserved = ['true', 'false', 'null', 'undefined', 'max', 'min', 'abs', 'sqrt', 'round'];
    return [...new Set(baseInputs)].filter(input => 
      input && !reserved.includes(input.toLowerCase()) && !calculatedFields.includes(input)
    );
  }

  /**
   * Extract variables from formula string
   */
  private extractVariablesFromFormula(formula: string): string[] {
    // Simple regex to find variable names (letters, numbers, underscore)
    // Exclude function calls by checking for parentheses
    const variableRegex = /\b([a-zA-Z_][a-zA-Z0-9_]*)\b(?!\s*\()/g;
    const variables: string[] = [];
    let match;
    
    while ((match = variableRegex.exec(formula)) !== null) {
      const variable = match[1];
      // Filter out mathematical functions and operators
      if (!this.isMathFunction(variable)) {
        variables.push(variable);
      }
    }
    
    return variables;
  }

  /**
   * Extract variables from condition object
   */
  private extractVariablesFromCondition(condition: any): string[] {
    const variables: string[] = [];
    
    if (condition.var) {
      variables.push(condition.var);
    }
    
    if (condition.and && Array.isArray(condition.and)) {
      condition.and.forEach((subCondition: any) => {
        variables.push(...this.extractVariablesFromCondition(subCondition));
      });
    }
    
    if (condition.or && Array.isArray(condition.or)) {
      condition.or.forEach((subCondition: any) => {
        variables.push(...this.extractVariablesFromCondition(subCondition));
      });
    }
    
    return variables;
  }

  /**
   * Check if a string is a mathematical function
   */
  private isMathFunction(str: string): boolean {
    const mathFunctions = [
      'abs', 'acos', 'asin', 'atan', 'atan2', 'ceil', 'cos', 'exp', 'floor',
      'log', 'max', 'min', 'pow', 'random', 'round', 'sin', 'sqrt', 'tan',
      'parseInt', 'parseFloat', 'isNaN', 'isFinite', 'number', 'string'
    ];
    return mathFunctions.includes(str.toLowerCase());
  }

  /**
   * Get missing inputs
   */
  getMissingInputs(userInputs: Record<string, any>, config: RuleFlowConfig): string[] {
    const requiredInputs = this.extractRequiredInputs(config);
    return requiredInputs.filter(input => !(input in userInputs) || userInputs[input] === null || userInputs[input] === undefined || userInputs[input] === '');
  }

  /**
   * Convert string values to appropriate types
   */
  convertValue(value: any): any {
    if (value === null || value === undefined) {
      return value;
    }

    if (typeof value === 'string') {
      const trimmed = value.trim();
      
      // Try to convert to number
      if (/^-?\d+\.?\d*$/.test(trimmed)) {
        const num = parseFloat(trimmed);
        return isNaN(num) ? trimmed : num;
      }
      
      // Try to convert to boolean
      if (trimmed.toLowerCase() === 'true') return true;
      if (trimmed.toLowerCase() === 'false') return false;
      
      return trimmed;
    }

    return value;
  }

  /**
   * 🆕 Validate single field (เหมือน PHP validateField)
   */
  validateField(fieldName: string, value: any, config: RuleFlowConfig): FieldValidationResult {
    const requiredInputs = this.extractRequiredInputs(config);
    const errors: string[] = [];
    const warnings: string[] = [];

    // Check if field is required by configuration
    if (!requiredInputs.includes(fieldName)) {
      warnings.push(`Field '${fieldName}' is not required by configuration`);
    }

    // Convert value
    let convertedValue: any;
    try {
      convertedValue = this.convertValue(value);
    } catch (error) {
      errors.push(`Cannot convert '${fieldName}' value: ${error}`);
      convertedValue = value;
    }

    // ตรวจสอบค่าว่าง (สำหรับ required fields)
    if (requiredInputs.includes(fieldName)) {
      if (value === null || value === undefined || value === '') {
        errors.push(`Field '${fieldName}' is required and cannot be empty`);
      }
    }

    return {
      valid: errors.length === 0,
      converted_value: convertedValue,
      type: typeof convertedValue,
      errors,
      warnings
    };
  }

  /**
   * 🆕 Validate multiple fields at once (เหมือน PHP validateFields)
   */
  validateFields(userInputs: Record<string, any>, config: RuleFlowConfig): Record<string, FieldValidationResult> {
    const results: Record<string, FieldValidationResult> = {};
    
    Object.entries(userInputs).forEach(([fieldName, value]) => {
      results[fieldName] = this.validateField(fieldName, value, config);
    });
    
    return results;
  }

  /**
   * 🆕 Validate partial inputs (เหมือน PHP validatePartial)
   */
  validatePartial(userInputs: Record<string, any>, config: RuleFlowConfig): {
    valid: boolean;
    missing_required: string[];
    overall_progress: number;
  } {
    const requiredInputs = this.extractRequiredInputs(config);
    const missingInputs = this.getMissingInputs(userInputs, config);
    const providedCount = requiredInputs.length - missingInputs.length;
    const progress = requiredInputs.length > 0 ? 
      Math.round((providedCount / requiredInputs.length) * 100) : 100;

    return {
      valid: missingInputs.length === 0,
      missing_required: missingInputs,
      overall_progress: progress
    };
  }

  /**
   * 🆕 Check if inputs are complete
   */
  isComplete(userInputs: Record<string, any>, config: RuleFlowConfig): boolean {
    const baseInputs = this.extractBaseInputs(config);
    const missingBaseInputs = baseInputs.filter(input => 
      !(input in userInputs) || userInputs[input] === null || userInputs[input] === undefined || userInputs[input] === ''
    );
    return missingBaseInputs.length === 0;
  }

  /**
   * 🆕 Get completion percentage
   */
  getCompletionPercentage(userInputs: Record<string, any>, config: RuleFlowConfig): number {
    const requiredInputs = this.extractRequiredInputs(config);
    if (requiredInputs.length === 0) return 100;
    
    const missingInputs = this.getMissingInputs(userInputs, config);
    const providedCount = requiredInputs.length - missingInputs.length;
    return Math.round((providedCount / requiredInputs.length) * 100);
  }

  /**
   * 🆕 Get validation status for UI (use base inputs for UI calculation)
   */
  getValidationStatus(userInputs: Record<string, any>, config: RuleFlowConfig): ValidationStatus {
    const baseInputs = this.extractBaseInputs(config);
    const allInputs = this.extractRequiredInputs(config);
    
    // Calculate progress based on base inputs for UI
    const missingBaseInputs = baseInputs.filter(input => 
      !(input in userInputs) || userInputs[input] === null || userInputs[input] === undefined || userInputs[input] === ''
    );
    
    const baseProgress = baseInputs.length > 0 ? 
      Math.round(((baseInputs.length - missingBaseInputs.length) / baseInputs.length) * 100) : 100;

    const fieldResults = this.validateFields(userInputs, config);
    
    const providedInputs = Object.keys(userInputs).filter(key => 
      userInputs[key] !== null && userInputs[key] !== undefined && userInputs[key] !== ''
    );
    
    const invalidFields = Object.values(fieldResults).filter(result => !result.valid).length;
    const validFieldsCount = Object.values(fieldResults).filter(result => result.valid).length;
    
    const validationScore = baseInputs.length > 0 ? 
      Math.max(0, ((baseInputs.length - missingBaseInputs.length) / baseInputs.length) * 100)
      : 100;

    return {
      ready_to_submit: missingBaseInputs.length === 0 && invalidFields === 0,
      validation_score: Math.round(validationScore),
      field_validation: {
        valid: missingBaseInputs.length === 0,
        missing_required: missingBaseInputs,
        overall_progress: baseProgress
      },
      summary: {
        total_fields: allInputs.length,
        provided_fields: providedInputs.length,
        missing_fields: missingBaseInputs.length,
        invalid_fields: invalidFields
      }
    };
  }

  /**
   * 🆕 Basic sanitization (เก็บไว้เพื่อ backward compatibility)
   */
  sanitizeInputs(userInputs: Record<string, any>): Record<string, any> {
    const sanitized: Record<string, any> = {};
    
    Object.entries(userInputs).forEach(([key, value]) => {
      if (typeof value === 'string') {
        // Basic sanitization
        sanitized[key] = value.trim();
      } else {
        sanitized[key] = value;
      }
    });
    
    return sanitized;
  }

  /**
   * 🆕 Advanced sanitization (เหมือน PHP security features)
   */
  sanitizeInputsAdvanced(userInputs: Record<string, any>, options: SanitizationOptions = {}): Record<string, any> {
    const {
      trimStrings = true,
      removeHtml = true,
      maxStringLength = 1000,
      allowedKeys = null
    } = options;

    const sanitized: Record<string, any> = {};
    
    Object.entries(userInputs).forEach(([key, value]) => {
      // Filter allowed keys
      if (allowedKeys && !allowedKeys.includes(key)) {
        return; // Skip this key
      }

      let cleanValue = value;

      if (typeof value === 'string') {
        // Trim whitespace
        if (trimStrings) {
          cleanValue = value.trim();
        }

        // Remove HTML tags (improved regex)
        if (removeHtml) {
          // Remove script tags completely
          cleanValue = cleanValue.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
          // Remove all other HTML tags
          cleanValue = cleanValue.replace(/<[^>]*>/g, '');
          // Remove text that looks like function calls or alerts
          cleanValue = cleanValue.replace(/\b(alert|eval|function)\s*\([^)]*\)/gi, '');
        }

        // Limit string length
        if (cleanValue.length > maxStringLength) {
          cleanValue = cleanValue.substring(0, maxStringLength);
        }

        // Remove dangerous characters for SQL injection prevention
        cleanValue = cleanValue.replace(/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION)\b)/gi, '');
        cleanValue = cleanValue.replace(/['"`;]/g, '');
      }

      sanitized[key] = cleanValue;
    });
    
    return sanitized;
  }

  /**
   * 🆕 Validate input security (ตรวจหา injection attempts)
   */
  validateInputSecurity(userInputs: Record<string, any>): SecurityValidationResult {
    const threats: Array<{ field: string; threat_type: string; message: string; }> = [];

    Object.entries(userInputs).forEach(([key, value]) => {
      if (typeof value === 'string') {
        // Check for SQL injection patterns
        if (/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION)\b)/i.test(value)) {
          threats.push({
            field: key,
            threat_type: 'SQL_INJECTION',
            message: 'Potential SQL injection detected'
          });
        }

        // Check for script injection
        if (/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi.test(value)) {
          threats.push({
            field: key,
            threat_type: 'XSS',
            message: 'Script injection detected'
          });
        }

        // Check for excessively long input
        if (value.length > 10000) {
          threats.push({
            field: key,
            threat_type: 'DOS',
            message: 'Input too long - potential DoS attack'
          });
        }
      }
    });

    return {
      safe: threats.length === 0,
      threats
    };
  }
  
  
}