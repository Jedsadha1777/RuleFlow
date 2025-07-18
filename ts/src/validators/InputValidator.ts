import { RuleFlowException } from '../exceptions/RuleFlowException.js';
import { RuleFlowConfig } from '../types.js';

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

export class InputValidator {
  
  /**
   * หา input ที่จำเป็นจาก configuration (เหมือน PHP version)
   */
  extractRequiredInputs(config: RuleFlowConfig): string[] {
    const inputs: string[] = [];
    
    config.formulas?.forEach(formula => {
      // จาก inputs array
      if (formula.inputs) {
        inputs.push(...formula.inputs);
      }
      
      // จาก switch variable
      if (formula.switch) {
        inputs.push(formula.switch);
      }
      
      // จาก formula string (แยกตัวแปรออกมา)
      if (formula.formula) {
        const vars = formula.formula.match(/\b[a-zA-Z_]\w*\b/g) || [];
        vars.forEach(v => {
          // ข้าม function names
          if (!this.isReservedWord(v)) {
            inputs.push(v);
          }
        });
      }
      
      // จาก when conditions (nested logic)
      if (formula.when) {
        formula.when.forEach(condition => {
          this.extractFromCondition(condition.if, inputs);
        });
      }
    });
    
    // เอาที่ซ้ำออก และเรียงลำดับ
    return [...new Set(inputs)].sort();
  }
  
  /**
   * แยกตัวแปรจาก condition (สำหรับ nested logic)
   */
  private extractFromCondition(condition: any, inputs: string[]): void {
    if (condition.var) {
      inputs.push(condition.var);
    }
    
    if (condition.and) {
      condition.and.forEach((c: any) => this.extractFromCondition(c, inputs));
    }
    
    if (condition.or) {
      condition.or.forEach((c: any) => this.extractFromCondition(c, inputs));
    }
  }
  
  /**
   * เช็คว่าเป็นคำสงวนหรือไม่ (function names)
   */
  private isReservedWord(word: string): boolean {
    const reserved = [
      'abs', 'min', 'max', 'sqrt', 'pow', 'round', 'floor', 'ceil',
      'sin', 'cos', 'tan', 'log', 'exp', 'avg', 'sum', 'count'
    ];
    return reserved.includes(word.toLowerCase());
  }
  
  /**
   * หา input ที่ขาดหาย (เหมือน PHP version)
   */
  getMissingInputs(userInputs: Record<string, any>, config: RuleFlowConfig): string[] {
    const required = this.extractRequiredInputs(config);
    const provided = Object.keys(userInputs);
    return required.filter(r => !provided.includes(r));
  }
  
  /**
   * เช็คว่าครบหรือไม่
   */
  isComplete(userInputs: Record<string, any>, config: RuleFlowConfig): boolean {
    return this.getMissingInputs(userInputs, config).length === 0;
  }
  
  /**
   * คำนวณ progress เปอร์เซ็นต์
   */
  getCompletionPercentage(userInputs: Record<string, any>, config: RuleFlowConfig): number {
    const required = this.extractRequiredInputs(config);
    if (required.length === 0) return 100;
    
    const provided = Object.keys(userInputs).filter(key => required.includes(key));
    return Math.round((provided.length / required.length) * 100);
  }
  
  /**
   * ตรวจสอบ partial inputs (เหมือน PHP validatePartial)
   */
  validatePartial(userInputs: Record<string, any>, config: RuleFlowConfig) {
    const missing = this.getMissingInputs(userInputs, config);
    const progress = this.getCompletionPercentage(userInputs, config);
    
    return {
      valid: missing.length === 0,
      missing_required: missing,
      overall_progress: progress,
      ready_to_submit: missing.length === 0
    };
  }
  
  /**
   * ตรวจสอบก่อน evaluate (เหมือน PHP - โยน exception เมื่อขาด input)
   */
  validateBeforeEvaluate(userInputs: Record<string, any>, config: RuleFlowConfig): void {
    const missing = this.getMissingInputs(userInputs, config);
    if (missing.length > 0) {
      throw new RuleFlowException(`Missing input: ${missing[0]}`);
    }
  }
  
  /**
   * 🆕 Convert value to appropriate type (เหมือน PHP version)
   */
  convertValue(value: any): any {
    if (value === null || value === undefined) {
      return value;
    }

    // Already correct type
    if (typeof value === 'boolean' || typeof value === 'number') {
      return value;
    }

    if (typeof value === 'string') {
      const trimmed = value.trim();
      
      // Empty string
      if (trimmed === '') return trimmed;
      
      // Boolean conversion
      if (trimmed.toLowerCase() === 'true') return true;
      if (trimmed.toLowerCase() === 'false') return false;
      
      // Number conversion
      if (/^-?\d+(\.\d+)?$/.test(trimmed)) {
        const num = parseFloat(trimmed);
        if (!isNaN(num)) return num;
      }
      
      return trimmed;
    }

    return value;
  }
  
  /**
   * 🆕 Validate single field (เหมือน PHP validateField)
   */
  validateField(fieldName: string, value: any, config: RuleFlowConfig): FieldValidationResult {
    const errors: string[] = [];
    const warnings: string[] = [];
    
    // ตรวจสอบว่า field นี้ต้องการหรือไม่
    const requiredInputs = this.extractRequiredInputs(config);
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
   * 🆕 Get validation status for UI (เหมือน PHP getValidationStatus)
   */
  getValidationStatus(userInputs: Record<string, any>, config: RuleFlowConfig): ValidationStatus {
    const fieldValidation = this.validatePartial(userInputs, config);
    const requiredInputs = this.extractRequiredInputs(config);
    const providedInputs = Object.keys(userInputs);
    
    // ตรวจสอบ field ที่ invalid
    const fieldResults = this.validateFields(userInputs, config);
    const invalidFields = Object.values(fieldResults).filter(result => !result.valid).length;
    
    // คำนวณ validation score
    const validFieldsCount = providedInputs.length - invalidFields;
    const validationScore = requiredInputs.length > 0 
      ? Math.max(0, (validFieldsCount / requiredInputs.length) * 100)
      : 100;

    return {
      ready_to_submit: fieldValidation.valid && invalidFields === 0,
      validation_score: Math.round(validationScore),
      field_validation: fieldValidation,
      summary: {
        total_fields: requiredInputs.length,
        provided_fields: providedInputs.length,
        missing_fields: fieldValidation.missing_required.length,
        invalid_fields: invalidFields
      }
    };
  }

  /**
   * 🆕 Sanitize inputs (พื้นฐาน - ไม่ซับซ้อนเหมือน PHP)
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
   * Original validate method (เก็บไว้เพื่อ backward compatibility)
   */
  validate(inputs: Record<string, any>, requiredInputs: string[]): void {
    for (const required of requiredInputs) {
      if (!(required in inputs)) {
        throw new RuleFlowException(`Required input '${required}' is missing`);
      }
    }
  }
}