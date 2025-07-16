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