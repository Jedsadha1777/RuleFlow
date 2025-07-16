export type FunctionHandler = (...args: any[]) => any;

export interface FunctionInfo {
  name: string;
  category: string;
  description?: string;
  parameters?: string[];
  returnType?: string;
}

export interface FunctionProvider {
  getFunctions(): Record<string, FunctionHandler>;
  getInfo(): {
    name: string;
    version?: string;
    description?: string;
  };
}

export interface FunctionCategories {
  Math: string[];
  Statistics: string[];
  Business: string[];
  Utility: string[];
  [category: string]: string[];
}