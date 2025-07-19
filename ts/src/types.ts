export interface RuleFlowConfig {
  formulas: Formula[];
}

export interface Formula {
  id: string;
  formula?: string;
  inputs?: string[];
  switch?: string;
  when?: WhenCondition[];
  default?: any;
  set_vars?: Record<string, any>;
  rules?: any[]; // For accumulative scoring
  scoring?: any; // For advanced scoring
  as?: string;
}

export interface WhenCondition {
  if: Condition | LogicalCondition;
  result: any;
  set_vars?: Record<string, any>;
}

export interface Condition {
  op: string;  // ==, !=, >, <, >=, <=
  value: any;
  var?: string;
}

export interface LogicalCondition {
  and?: (Condition | LogicalCondition)[];
  or?: (Condition | LogicalCondition)[];
}

// 🆕 Test Config Result Interface
export interface TestConfigResult {
  valid: boolean;
  errors: string[];
  warnings: string[];
  test_result?: Record<string, any>;
  execution_time?: number;
}

// 🆕 Security Validation Interface
export interface SecurityValidationResult {
  safe: boolean;
  threats: Array<{
    field: string;
    threat_type: string;
    message: string;
  }>;
}

// 🆕 Sanitization Options Interface
export interface SanitizationOptions {
  trimStrings?: boolean;
  removeHtml?: boolean;
  maxStringLength?: number;
  allowedKeys?: string[];
}

// 🆕 Batch Validation Result
export interface BatchValidationResult {
  index: number;
  valid: boolean;
  errors: string[];
  warnings: string[];
}

// 🆕 Field Validation Result
export interface FieldValidationResult {
  valid: boolean;
  converted_value: any;
  type: string;
  errors: string[];
  warnings: string[];
}

// 🆕 Validation Status (สำหรับ UI)
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