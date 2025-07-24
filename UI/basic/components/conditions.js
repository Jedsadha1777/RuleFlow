/**
 * Conditions Component Class
 * Handles nested AND/OR logic components
 */

class ConditionsComponent {
    constructor() {
        this.id = 'conditions_' + Date.now();
        this.conditions = [];
        this.default = '';
        this.as = '';
    }

    /**
     * Get component icon
     */
    getIcon() {
        return '<i class="bi bi-question-circle"></i>';
    }

    /**
     * Get component title
     */
    getTitle() {
        return this.id || 'Conditions Component';
    }

    /**
     * Get component ID
     */
    getId() {
        return this.id;
    }

    /**
     * Set component ID
     */
    setId(newId) {
        this.id = newId;
    }

    /**
     * Update field value
     */
    updateField(field, value) {
        switch(field) {
            case 'id':
                this.id = value;
                break;
            case 'default':
                this.default = value;
                break;
            case 'as':
                this.as = value;
                break;
        }
    }

    /**
     * Add new condition
     */
    addCondition() {
        this.conditions.push({
            if: { op: '>', var: '', value: '' },
            result: ''
        });
    }

    /**
     * Remove condition
     */
    removeCondition(conditionIndex) {
        this.conditions.splice(conditionIndex, 1);
    }

    /**
     * Update condition
     */
    updateCondition(conditionIndex, field, value) {
        if (!this.conditions[conditionIndex]) return;

        if (field === 'condition_op') {
            this.conditions[conditionIndex].if.op = value;
        } else if (field === 'condition_var') {
            this.conditions[conditionIndex].if.var = value;
        } else if (field === 'condition_value') {
            this.conditions[conditionIndex].if.value = this.parseValue(value);
        } else if (field === 'result') {
            this.conditions[conditionIndex].result = this.parseValue(value);
        }
    }

    /**
     * Parse value to appropriate type
     */
    parseValue(value) {
        if (value === 'true') return true;
        if (value === 'false') return false;
        if (/^\d+$/.test(value)) return parseInt(value);
        if (/^\d*\.\d+$/.test(value)) return parseFloat(value);
        return value;
    }

    /**
     * Render component form
     */
    render(index) {
        let html = `
            <div class="component-form">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ID</label>
                        <input type="text" 
                               class="form-control" 
                               value="${this.id}" 
                               onchange="updateComponentField(${index}, 'id', this.value)">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Store As Variable (optional)</label>
                        <input type="text" 
                               class="form-control" 
                               placeholder="\$variable_name"
                               value="${this.as}" 
                               onchange="updateComponentField(${index}, 'as', this.value)">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Default Value</label>
                    <input type="text" 
                           class="form-control" 
                           placeholder="Default result when no conditions match"
                           value="${this.default}" 
                           onchange="updateComponentField(${index}, 'default', this.value)">
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Conditions</label>
                        <button type="button" 
                                class="btn btn-sm btn-outline-primary" 
                                onclick="addCondition(${index})">
                            <i class="bi bi-plus"></i> Add Condition
                        </button>
                    </div>
                    <div class="conditions-list" id="conditionsList_${index}">
        `;

        this.conditions.forEach((condition, conditionIndex) => {
            html += this.renderCondition(index, conditionIndex, condition);
        });

        if (this.conditions.length === 0) {
            html += '<p class="text-muted">No conditions defined. Click "Add Condition" to create logic.</p>';
        }

        html += `
                    </div>
                </div>
            </div>
        `;

        return html;
    }

    /**
     * Render individual condition
     */
    renderCondition(componentIndex, conditionIndex, condition) {
        const ifCondition = condition.if || {};
        return `
            <div class="condition-builder" id="condition_${componentIndex}_${conditionIndex}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">Condition ${conditionIndex + 1}</small>
                    <button type="button" 
                            class="btn btn-sm btn-outline-danger" 
                            onclick="removeCondition(${componentIndex}, ${conditionIndex})"
                            title="Remove Condition">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                
                <div class="condition-row">
                    <div class="row">
                        <div class="col-md-3">
                           <select class="form-select form-select-sm condition-field" 
                                data-condition-index="${conditionIndex}"
                                data-condition-field="condition_op">
                            ${getOperatorOptions(ifCondition.op)}
                        </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   placeholder="Variable name"
                                   value="${ifCondition.var || ifCondition.field || ''}"
                                   onchange="updateCondition(${componentIndex}, ${conditionIndex}, 'condition_var', this.value)">
                        </div>
                        <div class="col-md-5">
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   placeholder="Compare value"
                                   value="${ifCondition.value || ''}"
                                   onchange="updateCondition(${componentIndex}, ${conditionIndex}, 'condition_value', this.value)">
                        </div>
                    </div>
                </div>
                
                <div class="mt-2">
                    <label class="form-label small">Then Result:</label>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           placeholder="Result if condition is true"
                           value="${condition.result || ''}"
                           onchange="updateCondition(${componentIndex}, ${conditionIndex}, 'result', this.value)">
                </div>
                
                <small class="text-muted">
                    <strong>Note:</strong> Conditions are evaluated in order. First matching condition wins.
                </small>
            </div>
        `;
    }

    /**
     * Convert to JSON configuration
     */
    toJSON() {
        const json = {
            id: this.id,
            conditions: this.conditions,
            default: this.parseValue(this.default)
        };

        if (this.as) {
            json.as = this.as;
        }

        return json;
    }

    /**
     * Load from JSON configuration
     */
    fromJSON(json) {
        this.id = json.id || this.id;
        this.conditions = json.conditions || [];
        this.default = json.default || '';
        this.as = json.as || '';
    }
}

// Global functions for condition management
window.addCondition = function(componentIndex) {
    if (components[componentIndex] && components[componentIndex].instance.addCondition) {
        components[componentIndex].instance.addCondition();
        updateView();
        updateJSON();
    }
};

window.removeCondition = function(componentIndex, conditionIndex) {
    if (components[componentIndex] && components[componentIndex].instance.removeCondition) {
        components[componentIndex].instance.removeCondition(conditionIndex);
        updateView();
        updateJSON();
    }
};

window.updateCondition = function(componentIndex, conditionIndex, field, value) {
    if (components[componentIndex] && components[componentIndex].instance.updateCondition) {
        components[componentIndex].instance.updateCondition(conditionIndex, field, value);
        updateJSON();
        updateInputVariables();
    }
};

// Export for global use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ConditionsComponent;
} else {
    window.ConditionsComponent = ConditionsComponent;
}