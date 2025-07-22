// Formula Component
class FormulaComponent {
    constructor(id) {
        this.config = {
            id: id,
            formula: '',
            inputs: [],
            as: ''
        };
    }
    
    getIcon() {
        return 'F';
    }
    
    getTitle() {
        return this.config.id || 'Formula';
    }
    
    getFormHTML(index) {
        return `
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="form-label">Component ID</label>
                    <input type="text" 
                           class="form-control" 
                           name="id"
                           value="${this.config.id}" 
                           placeholder="formula_name">
                </div>
            </div>
            
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="form-label">Formula Expression</label>
                    <textarea class="form-control formula-expression" 
                              name="formula"
                              rows="3"
                              placeholder="e.g., weight / ((height/100) ** 2)">${this.config.formula}</textarea>
                    <div class="form-text">Mathematical expression using input variables</div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Input Variables</label>
                    <input type="text" 
                           class="form-control" 
                           name="inputs"
                           value="${Array.isArray(this.config.inputs) ? this.config.inputs.join(', ') : ''}" 
                           placeholder="weight, height">
                    <div class="form-text">Comma-separated variable names</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Output Variable (Optional)</label>
                    <input type="text" 
                           class="form-control" 
                           name="as"
                           value="${this.config.as}" 
                           placeholder="$bmi_value">
                    <div class="form-text">Use $ prefix for intermediate variables</div>
                </div>
            </div>
            
            ${this.getValidationHTML()}
        `;
    }
    
    getValidationHTML() {
        const errors = this.validate();
        if (errors.length === 0) {
            return '<div class="alert alert-success">✅ Formula configuration is valid</div>';
        }
        
        return `
            <div class="alert alert-warning">
                <strong>⚠️ Validation Issues:</strong>
                <ul class="mb-0 mt-1">
                    ${errors.map(error => `<li>${error}</li>`).join('')}
                </ul>
            </div>
        `;
    }
    
    updateFromForm(element) {
        const inputs = element.querySelectorAll('input, textarea');
        
        inputs.forEach(input => {
            const name = input.name;
            let value = input.value;
            
            if (name === 'inputs') {
                // Convert comma-separated string to array
                value = value.split(',').map(s => s.trim()).filter(s => s);
            }
            
            if (name && this.config.hasOwnProperty(name)) {
                this.config[name] = value;
            }
        });
        
        // Update validation display
        const validationDiv = element.querySelector('.alert');
        if (validationDiv) {
            const parent = validationDiv.parentNode;
            validationDiv.remove();
            parent.insertAdjacentHTML('beforeend', this.getValidationHTML());
        }
    }
    
    validate() {
        const errors = [];
        
        if (!this.config.id) {
            errors.push('Component ID is required');
        }
        
        if (!this.config.formula) {
            errors.push('Formula expression is required');
        }
        
        if (!this.config.inputs || this.config.inputs.length === 0) {
            errors.push('At least one input variable is required');
        }
        
        // Check if formula contains input variables
        if (this.config.formula && this.config.inputs && this.config.inputs.length > 0) {
            const missingInputs = this.config.inputs.filter(input => 
                !this.config.formula.includes(input)
            );
            
            if (missingInputs.length > 0) {
                errors.push(`Formula doesn't use these inputs: ${missingInputs.join(', ')}`);
            }
        }
        
        // Check output variable format
        if (this.config.as && !this.config.as.startsWith('$')) {
            errors.push('Output variable should start with $ (e.g., $variable_name)');
        }
        
        return errors;
    }
    
    toJSON() {
        // Clean up config for export
        const cleanConfig = { ...this.config };
        
        // Remove empty fields
        Object.keys(cleanConfig).forEach(key => {
            if (cleanConfig[key] === '' || 
                (Array.isArray(cleanConfig[key]) && cleanConfig[key].length === 0)) {
                delete cleanConfig[key];
            }
        });
        
        return cleanConfig;
    }
    
    // Sample configurations
    static getSamples() {
        return [
            {
                name: 'BMI Calculation',
                config: {
                    id: 'bmi_calculation',
                    formula: 'weight / ((height/100) ** 2)',
                    inputs: ['weight', 'height'],
                    as: '$bmi_value'
                }
            },
            {
                name: 'Total Price',
                config: {
                    id: 'total_price',
                    formula: 'price * quantity * (1 + tax_rate)',
                    inputs: ['price', 'quantity', 'tax_rate']
                }
            },
            {
                name: 'Age from Birth Year',
                config: {
                    id: 'calculate_age',
                    formula: '2024 - birth_year',
                    inputs: ['birth_year'],
                    as: '$age'
                }
            }
        ];
    }
    
    // Load sample configuration
    loadSample(sampleName) {
        const samples = FormulaComponent.getSamples();
        const sample = samples.find(s => s.name === sampleName);
        
        if (sample) {
            this.config = { ...sample.config };
            return true;
        }
        
        return false;
    }
}

// Register component
window.RuleFlowComponents = window.RuleFlowComponents || {};
window.RuleFlowComponents.formula = FormulaComponent;