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
  // Advanced Scoring
  rules?: AccumulativeRule[];
  scoring?: ScoringConfig;
  as?: string; // For storing result with custom name
}

export interface WhenCondition {
  if: Condition | LogicalCondition;
  result: any;
  set_vars?: Record<string, any>;
}

export interface Condition {
  op: string;  // ==, !=, >, <, >=, <=, between, in
  value: any;
  var?: string;
}

export interface LogicalCondition {
  and?: (Condition | LogicalCondition)[];
  or?: (Condition | LogicalCondition)[];
}

// ================================
// Advanced Scoring Types
// ================================

export interface AccumulativeRule {
  var: string;
  ranges?: ScoringRange[];
  if?: Condition | LogicalCondition;
  score?: number;
  result?: number; // Alternative to score
  weight?: number;
  set_vars?: Record<string, any>;
}

export interface ScoringRange {
  if: Condition | LogicalCondition;
  score?: number;
  result?: number; // Alternative to score
  decision?: string;
  level?: string;
  set_vars?: Record<string, any>;
}

export interface ScoringConfig {
  // Multi-dimensional scoring
  ifs?: MultiDimensionalScoring;
  // Simple weighted scoring
  if?: Condition | LogicalCondition;
  score?: number;
  result?: number;
  weight?: number;
}

export interface MultiDimensionalScoring {
  vars: string[]; // Variables for multi-dimensional matrix
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
  breakdown?: Record<string, number>; // Score breakdown by category
  metadata?: Record<string, any>;
}