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
  rules?: AccumulativeRule[];
  scoring?: ScoringConfig;
  function_call?: string;
  as?: string;
}

// ================================
// Condition Types
// ================================
export interface WhenCondition {
  if: Condition | LogicalCondition;
  result?: any;
  function_call?: string;
  params?: any[]; 
  set_vars?: Record<string, any>;
}

export interface Condition {
  op: string;
  value?: any;
  var?: string;
  function?: string;
  params?: any[];    
}

export interface LogicalCondition {
  and?: (Condition | LogicalCondition)[];
  or?: (Condition | LogicalCondition)[];
}

// ================================
// Scoring Types
// ================================
export interface AccumulativeRule {
  var: string;
  ranges?: ScoringRange[];
  if?: Condition | LogicalCondition;
  score?: number;
  result?: number;
  weight?: number;
  set_vars?: Record<string, any>;
}

export interface ScoringRange {
  if: Condition | LogicalCondition;
  score?: number;
  result?: number;
  decision?: string;
  level?: string;
  set_vars?: Record<string, any>;
}

export interface ScoringConfig {
  ifs?: MultiDimensionalScoring;
  if?: Condition | LogicalCondition;
  score?: number;
  result?: number;
  weight?: number;
}



export interface MultiDimensionalScoring {
  vars: string[];
  tree: ScoringTreeNode[];
}

export interface ScoringTreeNode {
  if: Condition | LogicalCondition;
  ranges?: ScoringRange[];
  score?: number;
  result?: number;
  decision?: string;
  level?: string;
  set_vars?: Record<string, any>;
}

export interface ScoringResult {
  score: number;
  decision?: string;
  level?: string;
  breakdown?: Record<string, number>;
  metadata?: Record<string, any>;
}

// ================================
// Validation Types
// ================================
export interface ValidationResult {
  valid: boolean;
  errors: string[];
  warnings: string[];
}

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

export interface BatchValidationResult {
  index: number;
  valid: boolean;
  errors: string[];
  warnings: string[];
}

// ================================
// Test & Config Types
// ================================
export interface TestConfigResult {
  valid: boolean;
  errors: string[];
  warnings: string[];
  test_result?: Record<string, any>;
  execution_time?: number;
}

// ================================
// Function Types
// ================================
export type FunctionHandler = (...args: any[]) => any;

export interface FunctionInfo {
  name: string;
  category?: string;
  description?: string;
  parameters?: string[];
  returnType?: string;
}

export interface FunctionCategories {
  [category: string]: string[];
  Math: string[];
  Statistics: string[];
  Business: string[];
  Utility: string[];
}

// ================================
// Type Aliases for Export
// ================================
export type ConfigValidationResult = ValidationResult;



// ================================
// Template Types
// ================================

export interface TemplateMetadata {
  name: string;
  description: string;
  category: string;
  author: string;
  version: string;
  tags: string[];
  difficulty: 'beginner' | 'intermediate' | 'advanced';
  estimatedTime: string;
  inputs: string[];
  outputs: string[];
}

export interface Template {
  config: RuleFlowConfig;
  metadata: TemplateMetadata;
  examples: TemplateExample[];
}

export interface TemplateExample {
  name: string;
  description: string;
  inputs: Record<string, any>;
  expectedOutputs: Record<string, any>;
}