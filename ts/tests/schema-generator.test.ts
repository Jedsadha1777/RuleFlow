// ไฟล์: ts/tests/schema-generator.test.ts

import { describe, it, expect } from 'vitest';
import { SchemaGenerator } from '../src/generators/SchemaGenerator.js';
import type { RuleFlowConfig } from '../src/types.js';

describe('SchemaGenerator', () => {
  const generator = new SchemaGenerator();

  const sampleConfig: RuleFlowConfig = {
    formulas: [
      {
        id: 'bmi',
        formula: 'weight / (height ** 2)',
        inputs: ['weight', 'height']
      },
      {
        id: 'category',
        switch: '$bmi',
        when: [
          { 
            if: { op: '<', value: 18.5 }, 
            result: 'Underweight',
            set_vars: { '$underweight': true }
          },
          { if: { op: '<', value: 25 }, result: 'Normal' }
        ],
        default: 'Obese',
        set_vars: { '$category_determined': true }
      },
      {
        id: 'score',
        rules: [
          {
            var: 'experience',
            ranges: [
              { if: { op: '>=', value: 5 }, result: 10 }
            ]
          }
        ]
      }
    ]
  };

  const complexConfig: RuleFlowConfig = {
    formulas: [
      {
        id: 'multi_scoring',
        scoring: {
          ifs: {
            vars: ['performance', 'tenure'],
            tree: [
              {
                if: { op: '>=', value: 90 },
                ranges: [
                  { if: { op: '>=', value: 5 }, result: 100 }
                ]
              }
            ]
          }
        }
      }
    ]
  };

  // ========================================
  // Test Input Schema Generation
  // ========================================

  describe('generateInputSchema()', () => {
    it('should generate schema for basic formula inputs', () => {
      const schema = generator.generateInputSchema(sampleConfig);
      
      expect(schema.type).toBe('object');
      expect(schema.additionalProperties).toBe(false);
      expect(schema.required).toContain('weight');
      expect(schema.required).toContain('height');
      
      expect(schema.properties.weight).toEqual({
        type: ['string', 'number', 'boolean'],
        description: 'Input field for bmi'
      });
      
      expect(schema.properties.height).toEqual({
        type: ['string', 'number', 'boolean'],
        description: 'Input field for bmi'
      });
    });

    it('should include switch variables in schema', () => {
      const schema = generator.generateInputSchema(sampleConfig);
      
      expect(schema.required).toContain('bmi');
      expect(schema.properties.bmi).toEqual({
        type: ['string', 'number', 'boolean'],
        description: 'Switch variable for category'
      });
    });

    it('should include rules variables in schema', () => {
      const schema = generator.generateInputSchema(sampleConfig);
      
      expect(schema.required).toContain('experience');
      expect(schema.properties.experience).toEqual({
        type: ['string', 'number', 'boolean'],
        description: 'Rule variable for score'
      });
    });

    it('should include scoring variables in schema', () => {
      const schema = generator.generateInputSchema(complexConfig);
      
      expect(schema.required).toContain('performance');
      expect(schema.required).toContain('tenure');
      expect(schema.properties.performance).toEqual({
        type: ['string', 'number', 'boolean'],
        description: 'Scoring variable for multi_scoring'
      });
    });

    it('should handle empty configuration', () => {
      const emptyConfig: RuleFlowConfig = { formulas: [] };
      const schema = generator.generateInputSchema(emptyConfig);
      
      expect(schema.properties).toEqual({});
      expect(schema.required).toHaveLength(0);
    });

    it('should not duplicate fields', () => {
      const configWithDuplicates: RuleFlowConfig = {
        formulas: [
          {
            id: 'calc1',
            formula: 'weight * 2',
            inputs: ['weight']
          },
          {
            id: 'calc2', 
            formula: 'weight / 2',
            inputs: ['weight'] // Duplicate
          }
        ]
      };
      
      const schema = generator.generateInputSchema(configWithDuplicates);
      
      expect(schema.required.filter(field => field === 'weight')).toHaveLength(1);
    });
  });

  // ========================================
  // Test TypeScript Interface Generation
  // ========================================

  describe('generateTypeScriptInterface()', () => {
    it('should generate basic TypeScript interface', () => {
      const tsInterface = generator.generateTypeScriptInterface(sampleConfig, 'BMIInputs');
      
      expect(tsInterface).toContain('interface BMIInputs');
      expect(tsInterface).toContain('weight: string | number | boolean;');
      expect(tsInterface).toContain('height: string | number | boolean;');
      expect(tsInterface).toContain('bmi: string | number | boolean;');
      expect(tsInterface).toContain('experience: string | number | boolean;');
    });

    it('should use default interface name', () => {
      const tsInterface = generator.generateTypeScriptInterface(sampleConfig);
      expect(tsInterface).toContain('interface RuleFlowInputs');
    });

    it('should include field descriptions as comments', () => {
      const tsInterface = generator.generateTypeScriptInterface(sampleConfig);
      
      expect(tsInterface).toContain('// Input field for bmi');
      expect(tsInterface).toContain('// Switch variable for category');
      expect(tsInterface).toContain('// Rule variable for score');
    });

    it('should handle optional fields correctly', () => {
      // Note: Current implementation treats all fields as required
      // This test verifies the current behavior
      const tsInterface = generator.generateTypeScriptInterface(sampleConfig);
      
      // All fields should be required (no ? marks)
      expect(tsInterface).not.toContain('weight?:');
      expect(tsInterface).not.toContain('height?:');
    });
  });

  // ========================================
  // Test Output Schema Generation
  // ========================================

  describe('generateOutputSchema()', () => {
    it('should generate schema for formula outputs', () => {
      const schema = generator.generateOutputSchema(sampleConfig);
      
      expect(schema.type).toBe('object');
      expect(schema.additionalProperties).toBe(true);
      expect(schema.required).toHaveLength(0); // Output fields are not required
      
      expect(schema.properties.bmi).toEqual({
        type: ['string', 'number', 'boolean', 'object'],
        description: 'Output from formula bmi'
      });
      
      expect(schema.properties.category).toEqual({
        type: ['string', 'number', 'boolean', 'object'],
        description: 'Output from formula category'
      });
    });

    it('should include set_vars in output schema', () => {
      const schema = generator.generateOutputSchema(sampleConfig);
      
      expect(schema.properties.category_determined).toEqual({
        type: ['string', 'number', 'boolean'],
        description: 'Variable set by category'
      });
    });

    it('should include when condition set_vars', () => {
      const schema = generator.generateOutputSchema(sampleConfig);
      
      expect(schema.properties.underweight).toEqual({
        type: ['string', 'number', 'boolean'],
        description: 'Variable set by category when condition 0'
      });
    });
  });

  // ========================================
  // Test Complete Documentation
  // ========================================

  describe('generateDocumentation()', () => {
    it('should generate complete documentation', () => {
      const docs = generator.generateDocumentation(sampleConfig);
      
      expect(docs.inputSchema).toBeDefined();
      expect(docs.outputSchema).toBeDefined();
      expect(docs.typeScriptInterface).toBeDefined();
      expect(docs.summary).toBeDefined();
      
      expect(docs.summary.totalFormulas).toBe(3);
      expect(docs.summary.requiredInputs).toBeGreaterThan(0);
      expect(docs.summary.outputFields).toBeGreaterThan(0);
    });

    it('should have consistent data across documentation parts', () => {
      const docs = generator.generateDocumentation(sampleConfig);
      
      // Input schema required fields should match summary
      expect(docs.inputSchema.required.length).toBe(docs.summary.requiredInputs);
      
      // Output schema properties should match summary
      expect(Object.keys(docs.outputSchema.properties).length).toBe(docs.summary.outputFields);
      
      // TypeScript interface should contain all required fields
      docs.inputSchema.required.forEach(field => {
        expect(docs.typeScriptInterface).toContain(field);
      });
    });
  });

  // ========================================
  // Test Schema Validation
  // ========================================

  describe('validateAgainstSchema()', () => {
    let schema: any;

    beforeEach(() => {
      schema = generator.generateInputSchema(sampleConfig);
    });

    it('should validate correct data', () => {
      const data = {
        weight: 70,
        height: 1.75,
        bmi: 22.86,
        experience: 5
      };
      
      const result = generator.validateAgainstSchema(data, schema);
      
      expect(result.valid).toBe(true);
      expect(result.errors).toHaveLength(0);
    });

    it('should detect missing required fields', () => {
      const data = {
        weight: 70
        // Missing height, bmi, experience
      };
      
      const result = generator.validateAgainstSchema(data, schema);
      
      expect(result.valid).toBe(false);
      expect(result.errors).toContain('Missing required field: height');
      expect(result.errors).toContain('Missing required field: bmi');
      expect(result.errors).toContain('Missing required field: experience');
    });

    it('should detect unexpected fields when additionalProperties is false', () => {
      const data = {
        weight: 70,
        height: 1.75,
        bmi: 22.86,
        experience: 5,
        unexpected: 'value'
      };
      
      const result = generator.validateAgainstSchema(data, schema);
      
      expect(result.valid).toBe(false);
      expect(result.errors).toContain('Unexpected field: unexpected');
    });

    it('should validate field types (basic)', () => {
      const data = {
        weight: 70,
        height: 1.75,
        bmi: 22.86,
        experience: 5
      };
      
      const result = generator.validateAgainstSchema(data, schema);
      
      expect(result.valid).toBe(true);
      // Note: Current implementation allows string, number, boolean for all fields
    });

    it('should allow additional properties when enabled', () => {
      const flexibleSchema = { ...schema, additionalProperties: true };
      const data = {
        weight: 70,
        height: 1.75,
        bmi: 22.86,
        experience: 5,
        extra: 'allowed'
      };
      
      const result = generator.validateAgainstSchema(data, flexibleSchema);
      
      expect(result.valid).toBe(true);
    });
  });

  // ========================================
  // Test Edge Cases
  // ========================================

  describe('Edge Cases', () => {
    it('should handle configuration without inputs', () => {
      const noInputConfig: RuleFlowConfig = {
        formulas: [
          {
            id: 'constant',
            formula: '42'
          }
        ]
      };
      
      const schema = generator.generateInputSchema(noInputConfig);
      expect(schema.required).toHaveLength(0);
      expect(Object.keys(schema.properties)).toHaveLength(0);
    });

    it('should handle complex nested configurations', () => {
      const nestedConfig: RuleFlowConfig = {
        formulas: [
          {
            id: 'complex',
            switch: 'type',
            when: [
              {
                if: { 
                  and: [
                    { op: '>', var: 'score', value: 80 },
                    { op: '<', var: 'age', value: 30 }
                  ]
                },
                result: 'qualified'
              }
            ]
          }
        ]
      };
      
      const schema = generator.generateInputSchema(nestedConfig);
      expect(schema.required).toContain('type');
      expect(schema.properties.type).toBeDefined();
    });

    it('should handle $ notation in switch variables', () => {
      const dollarConfig: RuleFlowConfig = {
        formulas: [
          {
            id: 'test',
            switch: '$calculated_value',
            when: [
              { if: { op: '>', value: 0 }, result: 'positive' }
            ]
          }
        ]
      };
      
      const schema = generator.generateInputSchema(dollarConfig);
      expect(schema.required).toContain('calculated_value');
      expect(schema.properties.calculated_value).toBeDefined();
    });
  });
});