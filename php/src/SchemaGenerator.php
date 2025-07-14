<?php

declare(strict_types=1);
require_once __DIR__ . '/RuleFlowHelper.php';

/**
 * Generate input schemas and documentation from RuleFlow configurations
 */
class SchemaGenerator
{
    private ConfigValidator $validator;
    
    public function __construct()
    {
        $this->validator = new ConfigValidator();
    }
    
    /**
     * Generate complete input schema from configuration
     */
    public function generateInputSchema(array $config): array
    {
        $requiredInputs = $this->validator->extractRequiredInputs($config);
        $schema = [
            'type' => 'object',
            'properties' => [],
            'required' => [],
            'additionalProperties' => false,
            'title' => 'RuleFlow Input Schema',
            'description' => 'Input schema generated from RuleFlow configuration'
        ];
        
        foreach ($requiredInputs as $input) {
            $fieldSchema = $this->inferFieldSchema($input, $config);
            $schema['properties'][$input] = $fieldSchema;
            
            if ($fieldSchema['required'] ?? true) {
                $schema['required'][] = $input;
            }
        }
        
        return $schema;
    }
    
    /**
     * Generate JSON Schema (draft-07 compatible)
     */
    public function generateJSONSchema(array $config, array $options = []): array
    {
        $schema = $this->generateInputSchema($config);
        
        return array_merge([
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            '$id' => $options['id'] ?? 'https://example.com/ruleflow-schema.json',
            'title' => $options['title'] ?? 'RuleFlow Input Schema',
            'description' => $options['description'] ?? 'Auto-generated schema for RuleFlow configuration inputs'
        ], $schema);
    }
    
    /**
     * Generate TypeScript interface
     */
    public function generateTypeScriptInterface(array $config, string $interfaceName = 'RuleFlowInputs'): string
    {
        $requiredInputs = $this->validator->extractRequiredInputs($config);
        $interface = "interface $interfaceName {\n";
        
        foreach ($requiredInputs as $input) {
            $fieldSchema = $this->inferFieldSchema($input, $config);
            $tsType = $this->jsonSchemaToTypeScript($fieldSchema);
            $optional = ($fieldSchema['required'] ?? true) ? '' : '?';
            
            if (isset($fieldSchema['description'])) {
                $interface .= "  /** {$fieldSchema['description']} */\n";
            }
            
            $interface .= "  $input$optional: $tsType;\n";
        }
        
        $interface .= "}";
        
        return $interface;
    }
    
    /**
     * Generate OpenAPI schema
     */
    public function generateOpenAPISchema(array $config): array
    {
        $schema = $this->generateInputSchema($config);
        
        // Convert to OpenAPI 3.0 format
        $openApiSchema = [
            'type' => 'object',
            'required' => $schema['required'],
            'properties' => $schema['properties'],
            'additionalProperties' => false
        ];
        
        return $openApiSchema;
    }
    
    /**
     * Generate form validation rules (for popular validation libraries)
     */
    public function generateValidationRules(array $config, string $format = 'laravel'): array
    {
        $requiredInputs = $this->validator->extractRequiredInputs($config);
        $rules = [];
        
        foreach ($requiredInputs as $input) {
            $fieldSchema = $this->inferFieldSchema($input, $config);
            $rules[$input] = $this->schemaToValidationRules($fieldSchema, $format);
        }
        
        return $rules;
    }
    
    /**
     * Generate HTML form
     */
    public function generateHTMLForm(array $config, array $options = []): string
    {
        $requiredInputs = $this->validator->extractRequiredInputs($config);
        $formId = $options['id'] ?? 'ruleflow-form';
        $formClass = $options['class'] ?? 'ruleflow-form';
        
        $html = "<form id=\"$formId\" class=\"$formClass\">\n";
        
        foreach ($requiredInputs as $input) {
            $fieldSchema = $this->inferFieldSchema($input, $config);
            $html .= $this->generateFormField($input, $fieldSchema, $options);
        }
        
        $html .= "  <button type=\"submit\">Calculate</button>\n";
        $html .= "</form>\n";
        
        return $html;
    }
    
    /**
     * Generate React component
     */
    public function generateReactComponent(array $config, string $componentName = 'RuleFlowForm'): string
    {
        $requiredInputs = $this->validator->extractRequiredInputs($config);
        $tsInterface = $this->generateTypeScriptInterface($config, $componentName . 'Props');
        
        $component = "import React, { useState } from 'react';\n\n";
        $component .= "$tsInterface\n\n";
        $component .= "const $componentName: React.FC<{onSubmit: (data: RuleFlowInputs) => void}> = ({ onSubmit }) => {\n";
        
        // State initialization
        $component .= "  const [formData, setFormData] = useState<Partial<RuleFlowInputs>>({});\n\n";
        
        // Handle change function
        $component .= "  const handleChange = (field: keyof RuleFlowInputs, value: any) => {\n";
        $component .= "    setFormData(prev => ({ ...prev, [field]: value }));\n";
        $component .= "  };\n\n";
        
        // Handle submit
        $component .= "  const handleSubmit = (e: React.FormEvent) => {\n";
        $component .= "    e.preventDefault();\n";
        $component .= "    onSubmit(formData as RuleFlowInputs);\n";
        $component .= "  };\n\n";
        
        // Render
        $component .= "  return (\n";
        $component .= "    <form onSubmit={handleSubmit}>\n";
        
        foreach ($requiredInputs as $input) {
            $fieldSchema = $this->inferFieldSchema($input, $config);
            $component .= $this->generateReactField($input, $fieldSchema);
        }
        
        $component .= "      <button type=\"submit\">Calculate</button>\n";
        $component .= "    </form>\n";
        $component .= "  );\n";
        $component .= "};\n\n";
        $component .= "export default $componentName;";
        
        return $component;
    }
    
    /**
     * Generate documentation
     */
    public function generateDocumentation(array $config): array
    {
        $requiredInputs = $this->validator->extractRequiredInputs($config);
        $docs = [
            'title' => 'RuleFlow Configuration Documentation',
            'description' => 'Auto-generated documentation for input fields and expected outputs',
            'inputs' => [],
            'outputs' => [],
            'examples' => []
        ];
        
        // Document inputs
        foreach ($requiredInputs as $input) {
            $fieldSchema = $this->inferFieldSchema($input, $config);
            $docs['inputs'][$input] = [
                'name' => $input,
                'type' => $fieldSchema['type'] ?? 'number',
                'description' => $fieldSchema['description'] ?? "Input field: $input",
                'required' => $fieldSchema['required'] ?? true,
                'constraints' => $this->extractConstraints($fieldSchema),
                'examples' => $fieldSchema['examples'] ?? []
            ];
        }
        
        // Document outputs
        foreach ($config['formulas'] as $formula) {
            $outputName = $formula['as'] ?? $formula['id'];
            if (substr($outputName, 0, 1) === '$') {
                $outputName = substr($outputName, 1);
            }
            
            $docs['outputs'][$outputName] = [
                'name' => $outputName,
                'type' => $this->inferOutputType($formula),
                'description' => $this->generateOutputDescription($formula),
                'formula_type' => $this->getFormulaType($formula)
            ];
        }
        
        // Generate example usage
        $docs['examples'] = $this->generateExampleUsage($config);
        
        return $docs;
    }
    
    /**
     * Infer field schema from configuration context
     */
    private function inferFieldSchema(string $input, array $config): array
    {
        $schema = [
            'type' => 'number',
            'required' => true,
            'description' => "Input field: $input"
        ];
        
        // Analyze field usage to infer type and constraints
        $usage = $this->analyzeFieldUsage($input, $config);
        
        if ($usage['in_comparisons']) {
            $schema = array_merge($schema, $this->inferFromComparisons($usage['comparisons']));
        }
        
        if ($usage['in_expressions']) {
            $schema['type'] = 'number';
        }
        
        // Infer from field name patterns
        $schema = array_merge($schema, $this->inferFromFieldName($input));
        
        return $schema;
    }
    
    /**
     * Analyze how a field is used in the configuration
     */
    private function analyzeFieldUsage(string $input, array $config): array
    {
        $usage = [
            'in_expressions' => false,
            'in_comparisons' => false,
            'in_switches' => false,
            'comparisons' => [],
            'ranges' => []
        ];
        
        foreach ($config['formulas'] as $formula) {
            // Check expressions
            if (isset($formula['inputs']) && in_array($input, $formula['inputs'])) {
                $usage['in_expressions'] = true;
            }
            
            // Check switch variables
            if (isset($formula['switch']) && RuleFlowHelper::normalizeVariableName($formula['switch']) === $input) {
                $usage['in_switches'] = true;
            }
            
            // Check comparisons in when clauses
            if (isset($formula['when'])) {
                foreach ($formula['when'] as $case) {
                    if (isset($case['if'])) {
                        $this->extractComparisons($case['if'], $input, $usage);
                    }
                }
            }
            
            // Check rules
            if (isset($formula['rules'])) {
                foreach ($formula['rules'] as $rule) {
                    if (isset($rule['var']) && RuleFlowHelper::normalizeVariableName($rule['var']) === $input) {
                        if (isset($rule['ranges'])) {
                            foreach ($rule['ranges'] as $range) {
                                if (isset($range['if'])) {
                                    $this->extractComparisons($range['if'], $input, $usage);
                                }
                            }
                        }
                        if (isset($rule['if'])) {
                            $this->extractComparisons($rule['if'], $input, $usage);
                        }
                    }
                }
            }
            
            // Check scoring conditions
            if (isset($formula['scoring'])) {
                $this->analyzeScoring($formula['scoring'], $input, $usage);
            }
        }
        
        return $usage;
    }
    
    /**
     * Extract comparison operations for a field
     */
    private function extractComparisons(array $condition, string $input, array &$usage): void
    {
        $usage['in_comparisons'] = true;
        $usage['comparisons'][] = $condition;
        
        // Extract ranges for min/max inference
        if (isset($condition['value'])) {
            $value = $condition['value'];
            $op = $condition['op'];
            
            if (is_numeric($value)) {
                switch ($op) {
                    case '>=':
                    case '>':
                        $usage['ranges']['min'] = min($usage['ranges']['min'] ?? $value, $value);
                        break;
                    case '<=':
                    case '<':
                        $usage['ranges']['max'] = max($usage['ranges']['max'] ?? $value, $value);
                        break;
                    case 'between':
                        if (is_array($value) && count($value) === 2) {
                            $usage['ranges']['min'] = min($usage['ranges']['min'] ?? $value[0], $value[0]);
                            $usage['ranges']['max'] = max($usage['ranges']['max'] ?? $value[1], $value[1]);
                        }
                        break;
                }
            }
        }
    }
    
    /**
     * Analyze scoring section for field usage
     */
    private function analyzeScoring(array $scoring, string $input, array &$usage): void
    {
        if (isset($scoring['ifs']['vars']) && in_array($input, $scoring['ifs']['vars'])) {
            $usage['in_expressions'] = true;
            
            if (isset($scoring['ifs']['tree'])) {
                $this->analyzeScoringTree($scoring['ifs']['tree'], $input, $usage);
            }
        }
    }
    
    /**
     * Analyze scoring tree for comparisons
     */
    private function analyzeScoringTree(array $tree, string $input, array &$usage): void
    {
        foreach ($tree as $item) {
            if (isset($item['if'])) {
                $this->extractComparisons($item['if'], $input, $usage);
            }
            if (isset($item['ranges'])) {
                $this->analyzeScoringTree($item['ranges'], $input, $usage);
            }
        }
    }
    
    /**
     * Infer schema from comparison operations
     */
    private function inferFromComparisons(array $comparisons): array
    {
        $schema = [];
        
        // Look for enum-like comparisons
        $equalityValues = [];
        foreach ($comparisons as $comp) {
            if ($comp['op'] === '==' && is_string($comp['value'])) {
                $equalityValues[] = $comp['value'];
            }
        }
        
        if (count($equalityValues) > 1) {
            $schema['type'] = 'string';
            $schema['enum'] = array_unique($equalityValues);
            $schema['description'] = 'Allowed values: ' . implode(', ', $schema['enum']);
        }
        
        return $schema;
    }
    
    /**
     * Infer schema from field name patterns
     */
    private function inferFromFieldName(string $input): array
    {
        $schema = [];
        $lowerInput = strtolower($input);
        
        // Age fields
        if (strpos($lowerInput, 'age') !== false) {
            $schema['type'] = 'integer';
            $schema['minimum'] = 0;
            $schema['maximum'] = 120;
            $schema['description'] = 'Age in years';
            $schema['examples'] = [25, 35, 45];
        }
        
        // Email fields
        elseif (strpos($lowerInput, 'email') !== false) {
            $schema['type'] = 'string';
            $schema['format'] = 'email';
            $schema['description'] = 'Email address';
            $schema['examples'] = ['user@example.com'];
        }
        
        // Phone fields
        elseif (strpos($lowerInput, 'phone') !== false) {
            $schema['type'] = 'string';
            $schema['pattern'] = '^[0-9+\-\s\(\)]+$';
            $schema['description'] = 'Phone number';
            $schema['examples'] = ['+1234567890', '(555) 123-4567'];
        }
        
        // Date fields
        elseif (strpos($lowerInput, 'date') !== false || strpos($lowerInput, '_at') !== false) {
            $schema['type'] = 'string';
            $schema['format'] = 'date';
            $schema['description'] = 'Date in YYYY-MM-DD format';
            $schema['examples'] = ['2024-01-15'];
        }
        
        // Score/rating fields
        elseif (strpos($lowerInput, 'score') !== false || strpos($lowerInput, 'rating') !== false) {
            $schema['type'] = 'number';
            $schema['minimum'] = 0;
            $schema['maximum'] = 100;
            $schema['description'] = 'Score or rating (0-100)';
            $schema['examples'] = [75, 85, 95];
        }
        
        // Percentage fields
        elseif (strpos($lowerInput, 'percent') !== false || strpos($lowerInput, 'rate') !== false) {
            $schema['type'] = 'number';
            $schema['minimum'] = 0;
            $schema['maximum'] = 100;
            $schema['description'] = 'Percentage value (0-100)';
            $schema['examples'] = [15.5, 25.0, 50.0];
        }
        
        // Currency/money fields
        elseif (preg_match('/(price|cost|salary|income|amount|fee)/', $lowerInput)) {
            $schema['type'] = 'number';
            $schema['minimum'] = 0;
            $schema['description'] = 'Monetary amount';
            $schema['examples'] = [1000, 5000, 10000];
        }
        
        // Boolean fields
        elseif (preg_match('/^(is_|has_|can_|should_)/', $lowerInput)) {
            $schema['type'] = 'boolean';
            $schema['description'] = 'True/false value';
            $schema['examples'] = [true, false];
        }
        
        // Count fields
        elseif (strpos($lowerInput, 'count') !== false || strpos($lowerInput, 'number') !== false) {
            $schema['type'] = 'integer';
            $schema['minimum'] = 0;
            $schema['description'] = 'Count or quantity';
            $schema['examples'] = [1, 5, 10];
        }
        
        return $schema;
    }
    
    /**
     * Convert JSON Schema type to TypeScript type
     */
    private function jsonSchemaToTypeScript(array $schema): string
    {
        $type = $schema['type'] ?? 'any';
        
        switch ($type) {
            case 'string':
                if (isset($schema['enum'])) {
                    return "'" . implode("' | '", $schema['enum']) . "'";
                }
                return 'string';
            
            case 'number':
            case 'integer':
                return 'number';
            
            case 'boolean':
                return 'boolean';
            
            case 'array':
                $items = $schema['items'] ?? ['type' => 'any'];
                return $this->jsonSchemaToTypeScript($items) . '[]';
            
            case 'object':
                return 'object';
            
            default:
                return 'any';
        }
    }
    
    /**
     * Convert schema to validation rules
     */
    private function schemaToValidationRules(array $schema, string $format): array|string
    {
        $rules = [];
        
        if ($schema['required'] ?? true) {
            $rules[] = 'required';
        }
        
        $type = $schema['type'] ?? 'string';
        
        switch ($format) {
            case 'laravel':
                return $this->generateLaravelRules($schema, $rules);
            
            case 'joi':
                return $this->generateJoiRules($schema);
            
            case 'yup':
                return $this->generateYupRules($schema);
            
            default:
                return $rules;
        }
    }
    
    /**
     * Generate Laravel validation rules
     */
    private function generateLaravelRules(array $schema, array $rules): string
    {
        $type = $schema['type'] ?? 'string';
        
        switch ($type) {
            case 'number':
            case 'integer':
                $rules[] = 'numeric';
                if (isset($schema['minimum'])) {
                    $rules[] = "min:{$schema['minimum']}";
                }
                if (isset($schema['maximum'])) {
                    $rules[] = "max:{$schema['maximum']}";
                }
                break;
            
            case 'string':
                $rules[] = 'string';
                if (isset($schema['format']) && $schema['format'] === 'email') {
                    $rules[] = 'email';
                }
                if (isset($schema['enum'])) {
                    $rules[] = 'in:' . implode(',', $schema['enum']);
                }
                break;
            
            case 'boolean':
                $rules[] = 'boolean';
                break;
        }
        
        return implode('|', $rules);
    }
    
    /**
     * Generate Joi validation rules
     */
    private function generateJoiRules(array $schema): string
    {
        $type = $schema['type'] ?? 'string';
        $joi = "Joi.$type()";
        
        if ($schema['required'] ?? true) {
            $joi .= '.required()';
        }
        
        if ($type === 'number' || $type === 'integer') {
            if (isset($schema['minimum'])) {
                $joi .= ".min({$schema['minimum']})";
            }
            if (isset($schema['maximum'])) {
                $joi .= ".max({$schema['maximum']})";
            }
        }
        
        if ($type === 'string') {
            if (isset($schema['format']) && $schema['format'] === 'email') {
                $joi .= '.email()';
            }
            if (isset($schema['enum'])) {
                $values = "'" . implode("', '", $schema['enum']) . "'";
                $joi .= ".valid($values)";
            }
        }
        
        return $joi;
    }
    
    /**
     * Generate Yup validation rules
     */
    private function generateYupRules(array $schema): string
    {
        $type = $schema['type'] ?? 'string';
        $yup = "yup.$type()";
        
        if ($schema['required'] ?? true) {
            $yup .= '.required()';
        }
        
        if ($type === 'number') {
            if (isset($schema['minimum'])) {
                $yup .= ".min({$schema['minimum']})";
            }
            if (isset($schema['maximum'])) {
                $yup .= ".max({$schema['maximum']})";
            }
        }
        
        if ($type === 'string') {
            if (isset($schema['format']) && $schema['format'] === 'email') {
                $yup .= '.email()';
            }
            if (isset($schema['enum'])) {
                $values = "'" . implode("', '", $schema['enum']) . "'";
                $yup .= ".oneOf([$values])";
            }
        }
        
        return $yup;
    }
    
    /**
     * Generate HTML form field
     */
    private function generateFormField(string $input, array $schema, array $options): string
    {
        $type = $this->getHTMLInputType($schema);
        $required = ($schema['required'] ?? true) ? 'required' : '';
        $description = $schema['description'] ?? '';
        
        $html = "  <div class=\"form-field\">\n";
        $html .= "    <label for=\"$input\">$input</label>\n";
        
        if ($type === 'select' && isset($schema['enum'])) {
            $html .= "    <select id=\"$input\" name=\"$input\" $required>\n";
            if (!$required) {
                $html .= "      <option value=\"\">Select...</option>\n";
            }
            foreach ($schema['enum'] as $value) {
                $html .= "      <option value=\"$value\">$value</option>\n";
            }
            $html .= "    </select>\n";
        } else {
            $attrs = $this->getHTMLAttributes($schema);
            $html .= "    <input type=\"$type\" id=\"$input\" name=\"$input\" $required $attrs>\n";
        }
        
        if ($description) {
            $html .= "    <small class=\"field-description\">$description</small>\n";
        }
        
        $html .= "  </div>\n";
        
        return $html;
    }
    
    /**
     * Get HTML input type from schema
     */
    private function getHTMLInputType(array $schema): string
    {
        $type = $schema['type'] ?? 'text';
        
        if (isset($schema['enum'])) {
            return 'select';
        }
        
        switch ($type) {
            case 'integer':
            case 'number':
                return 'number';
            case 'boolean':
                return 'checkbox';
            case 'string':
                if (isset($schema['format'])) {
                    return match($schema['format']) {
                        'email' => 'email',
                        'date' => 'date',
                        'password' => 'password',
                        default => 'text'
                    };
                }
                return 'text';
            default:
                return 'text';
        }
    }
    
    /**
     * Get HTML attributes from schema
     */
    private function getHTMLAttributes(array $schema): string
    {
        $attrs = [];
        
        if (isset($schema['minimum'])) {
            $attrs[] = "min=\"{$schema['minimum']}\"";
        }
        if (isset($schema['maximum'])) {
            $attrs[] = "max=\"{$schema['maximum']}\"";
        }
        if (isset($schema['pattern'])) {
            $attrs[] = "pattern=\"{$schema['pattern']}\"";
        }
        if (isset($schema['examples'][0])) {
            $attrs[] = "placeholder=\"{$schema['examples'][0]}\"";
        }
        
        return implode(' ', $attrs);
    }
    
    /**
     * Generate React form field
     */
    private function generateReactField(string $input, array $schema): string
    {
        $type = $this->getHTMLInputType($schema);
        $required = ($schema['required'] ?? true) ? 'true' : 'false';
        
        $field = "      <div className=\"form-field\">\n";
        $field .= "        <label htmlFor=\"$input\">$input</label>\n";
        
        if ($type === 'select' && isset($schema['enum'])) {
            $field .= "        <select\n";
            $field .= "          id=\"$input\"\n";
            $field .= "          value={formData.$input || ''}\n";
            $field .= "          onChange={(e) => handleChange('$input', e.target.value)}\n";
            $field .= "          required={$required}\n";
            $field .= "        >\n";
            if (!($schema['required'] ?? true)) {
                $field .= "          <option value=\"\">Select...</option>\n";
            }
            foreach ($schema['enum'] as $value) {
                $field .= "          <option value=\"$value\">$value</option>\n";
            }
            $field .= "        </select>\n";
        } else {
            $inputType = $type === 'checkbox' ? 'checkbox' : 'input';
            $valueAttr = $type === 'checkbox' ? 'checked' : 'value';
            $changeHandler = $type === 'checkbox' ? 'e.target.checked' : 'e.target.value';
            
            $field .= "        <$inputType\n";
            $field .= "          type=\"$type\"\n";
            $field .= "          id=\"$input\"\n";
            $field .= "          $valueAttr={formData.$input || " . ($type === 'checkbox' ? 'false' : "''") . "}\n";
            $field .= "          onChange={(e) => handleChange('$input', $changeHandler)}\n";
            $field .= "          required={$required}\n";
            $field .= "        />\n";
        }
        
        $field .= "      </div>\n";
        
        return $field;
    }
    
    /**
     * Infer output type from formula
     */
    private function inferOutputType(array $formula): string
    {
        if (isset($formula['switch'])) {
            return 'string|number';
        } elseif (isset($formula['formula'])) {
            return 'number';
        } elseif (isset($formula['rules']) || isset($formula['scoring'])) {
            return 'number';
        }
        
        return 'any';
    }
    
    /**
     * Generate output description
     */
    private function generateOutputDescription(array $formula): string
    {
        $id = $formula['id'];
        
        if (isset($formula['switch'])) {
            return "Conditional result based on switch logic for $id";
        } elseif (isset($formula['formula'])) {
            return "Calculated value from expression: {$formula['formula']}";
        } elseif (isset($formula['rules'])) {
            return "Accumulated score from rules for $id";
        } elseif (isset($formula['scoring'])) {
            return "Weighted score calculation for $id";
        }
        
        return "Output from formula $id";
    }
    
    /**
     * Get formula type
     */
    private function getFormulaType(array $formula): string
    {
        if (isset($formula['formula'])) return 'expression';
        if (isset($formula['switch'])) return 'switch';
        if (isset($formula['scoring'])) return 'scoring';
        if (isset($formula['rules'])) return 'rules';
        return 'unknown';
    }
    
    /**
     * Generate example usage
     */
    private function generateExampleUsage(array $config): array
    {
        $requiredInputs = $this->validator->extractRequiredInputs($config);
        $examples = [];
        
        // Generate basic example
        $basicExample = [];
        foreach ($requiredInputs as $input) {
            $schema = $this->inferFieldSchema($input, $config);
            $basicExample[$input] = $this->generateExampleValue($schema);
        }
        
        $examples['basic'] = [
            'description' => 'Basic usage example',
            'input' => $basicExample,
            'usage' => [
                'php' => "\$result = \$ruleFlow->evaluate(\$config, " . var_export($basicExample, true) . ");",
                'javascript' => "const result = await ruleFlow.evaluate(config, " . json_encode($basicExample) . ");"
            ]
        ];
        
        return $examples;
    }
    
    /**
     * Generate example value for schema
     */
    private function generateExampleValue(array $schema): mixed
    {
        if (isset($schema['examples'][0])) {
            return $schema['examples'][0];
        }
        
        if (isset($schema['enum'])) {
            return $schema['enum'][0];
        }
        
        $type = $schema['type'] ?? 'string';
        
        return match($type) {
            'integer' => $schema['minimum'] ?? 1,
            'number' => $schema['minimum'] ?? 1.0,
            'boolean' => true,
            'string' => 'example',
            default => null
        };
    }
    
    /**
     * Extract constraints from schema
     */
    private function extractConstraints(array $schema): array
    {
        $constraints = [];
        
        if (isset($schema['minimum'])) {
            $constraints['minimum'] = $schema['minimum'];
        }
        if (isset($schema['maximum'])) {
            $constraints['maximum'] = $schema['maximum'];
        }
        if (isset($schema['enum'])) {
            $constraints['allowed_values'] = $schema['enum'];
        }
        if (isset($schema['pattern'])) {
            $constraints['pattern'] = $schema['pattern'];
        }
        if (isset($schema['format'])) {
            $constraints['format'] = $schema['format'];
        }
        
        return $constraints;
    }
    
   
}