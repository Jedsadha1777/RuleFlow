import { RuleFlowConfig } from '../types';

export interface JSONSchema {
  type: string;
  properties: Record<string, any>;
  required: string[];
  additionalProperties?: boolean;
}

export class SchemaGenerator {
  /**
   * Generate JSON schema from RuleFlow configuration
   */
  generateInputSchema(config: RuleFlowConfig): JSONSchema {
    const properties: Record<string, any> = {};
    const required: string[] = [];

    // Extract all input fields from formulas
    config.formulas.forEach(formula => {
      if (formula.inputs) {
        formula.inputs.forEach(input => {
          if (!properties[input]) {
            properties[input] = {
              type: ['string', 'number', 'boolean'],
              description: `Input field for ${formula.id}`
            };
          }
          
          if (!required.includes(input)) {
            required.push(input);
          }
        });
      }

      // Handle switch variables
      if (formula.switch) {
        const switchVar = formula.switch.replace('$', '');
        if (!properties[switchVar]) {
          properties[switchVar] = {
            type: ['string', 'number', 'boolean'],
            description: `Switch variable for ${formula.id}`
          };
        }
        if (!required.includes(switchVar)) {
          required.push(switchVar);
        }
      }

      // Handle scoring variables
      if ((formula as any).scoring?.ifs?.vars) {
        (formula as any).scoring.ifs.vars.forEach((varName: string) => {
          if (!properties[varName]) {
            properties[varName] = {
              type: ['string', 'number', 'boolean'], 
              description: `Scoring variable for ${formula.id}`
            };
          }
          if (!required.includes(varName)) {
            required.push(varName);
          }
        });
      }

      // Handle rules variables
      if ((formula as any).rules) {
        (formula as any).rules.forEach((rule: any) => {
          if (rule.var && !properties[rule.var]) {
            properties[rule.var] = {
              type: ['string', 'number', 'boolean'],
              description: `Rule variable for ${formula.id}`
            };
            if (!required.includes(rule.var)) {
              required.push(rule.var);
            }
          }
        });
      }
    });

    return {
      type: 'object',
      properties,
      required,
      additionalProperties: false
    };
  }

  /**
   * Generate TypeScript interface from configuration
   */
  generateTypeScriptInterface(config: RuleFlowConfig, interfaceName: string = 'RuleFlowInputs'): string {
    const schema = this.generateInputSchema(config);
    
    let tsInterface = `interface ${interfaceName} {\n`;
    
    Object.entries(schema.properties).forEach(([key, prop]) => {
      const isRequired = schema.required.includes(key);
      const optional = isRequired ? '' : '?';
      
      // Determine TypeScript type
      let tsType = 'any';
      if (Array.isArray(prop.type)) {
        tsType = prop.type.join(' | ');
      } else {
        tsType = prop.type;
      }
      
      const comment = prop.description ? ` // ${prop.description}` : '';
      tsInterface += `  ${key}${optional}: ${tsType};${comment}\n`;
    });
    
    tsInterface += '}';
    
    return tsInterface;
  }

  /**
   * Generate output schema (what RuleFlow returns)
   */
  generateOutputSchema(config: RuleFlowConfig): JSONSchema {
    const properties: Record<string, any> = {};
    
    config.formulas.forEach(formula => {
      const outputKey = formula.id;
      properties[outputKey] = {
        type: ['string', 'number', 'boolean', 'object'],
        description: `Output from formula ${formula.id}`
      };

      // Add set_vars outputs
      if (formula.set_vars) {
        Object.keys(formula.set_vars).forEach(varName => {
          const normalizedVar = varName.replace('$', '');
          properties[normalizedVar] = {
            type: ['string', 'number', 'boolean'],
            description: `Variable set by ${formula.id}`
          };
        });
      }

      // Add when condition set_vars
      if (formula.when) {
        formula.when.forEach((condition, index) => {
          if (condition.set_vars) {
            Object.keys(condition.set_vars).forEach(varName => {
              const normalizedVar = varName.replace('$', '');
              properties[normalizedVar] = {
                type: ['string', 'number', 'boolean'],
                description: `Variable set by ${formula.id} when condition ${index}`
              };
            });
          }
        });
      }
    });

    return {
      type: 'object',
      properties,
      required: [], // Output fields are generally not required
      additionalProperties: true
    };
  }

  /**
   * Generate complete documentation
   */
  generateDocumentation(config: RuleFlowConfig): {
    inputSchema: JSONSchema;
    outputSchema: JSONSchema;
    typeScriptInterface: string;
    summary: {
      totalFormulas: number;
      requiredInputs: number;
      outputFields: number;
    };
  } {
    const inputSchema = this.generateInputSchema(config);
    const outputSchema = this.generateOutputSchema(config);
    const typeScriptInterface = this.generateTypeScriptInterface(config);

    return {
      inputSchema,
      outputSchema,
      typeScriptInterface,
      summary: {
        totalFormulas: config.formulas.length,
        requiredInputs: inputSchema.required.length,
        outputFields: Object.keys(outputSchema.properties).length
      }
    };
  }

  /**
   * Validate data against schema
   */
  validateAgainstSchema(data: Record<string, any>, schema: JSONSchema): {
    valid: boolean;
    errors: string[];
  } {
    const errors: string[] = [];

    // Check required fields
    schema.required.forEach(field => {
      if (!(field in data)) {
        errors.push(`Missing required field: ${field}`);
      }
    });

    // Check field types (basic validation)
    Object.entries(data).forEach(([key, value]) => {
      if (schema.properties[key]) {
        const expectedTypes = Array.isArray(schema.properties[key].type) 
          ? schema.properties[key].type 
          : [schema.properties[key].type];
        
        const actualType = typeof value;
        if (!expectedTypes.includes(actualType)) {
          errors.push(`Field '${key}' should be ${expectedTypes.join(' or ')}, got ${actualType}`);
        }
      } else if (!schema.additionalProperties) {
        errors.push(`Unexpected field: ${key}`);
      }
    });

    return {
      valid: errors.length === 0,
      errors
    };
  }
}