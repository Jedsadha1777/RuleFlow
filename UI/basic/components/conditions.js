/**
 * Conditions Component Class - Enhanced with Nested Logic
 * เพิ่ม AND/OR support โดยไม่ทำลาย structure เดิม
 */

class ConditionsComponent {
    constructor() {
        this.id = 'conditions_' + Date.now();
        this.conditions = [];
        this.default = '';
        this.as = '';
    }

    getIcon() {
        return '<i class="bi bi-question-circle"></i>';
    }

    getTitle() {
        return this.id || 'Conditions Component';
    }

    getId() {
        return this.id;
    }

    setId(newId) {
        this.id = newId;
    }

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

    addCondition() {
        this.conditions.push({
            if: { op: '>', var: '', value: '' },
            result: ''
        });
    }

    // 🆕 เพิ่ม nested condition support
    addNestedCondition() {
        this.conditions.push({
            if: {
                and: [
                    { op: '>', var: '', value: '' },
                    { op: '==', var: '', value: '' }
                ]
            },
            result: ''
        });
    }

    removeCondition(conditionIndex) {
        this.conditions.splice(conditionIndex, 1);
    }

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
        // 🆕 เพิ่ม nested logic handling
        else if (field === 'condition_type') {
            if (value === 'simple') {
                this.conditions[conditionIndex].if = { op: '>', var: '', value: '' };
            } else if (value === 'and') {
                this.conditions[conditionIndex].if = {
                    and: [
                        { op: '>', var: '', value: '' },
                        { op: '==', var: '', value: '' }
                    ]
                };
            } else if (value === 'or') {
                this.conditions[conditionIndex].if = {
                    or: [
                        { op: '>', var: '', value: '' },
                        { op: '==', var: '', value: '' }
                    ]
                };
            }
        }
    }

    // 🆕 Helper methods for nested logic
    isNestedCondition(condition) {
        return condition && (condition.and || condition.or);
    }

    parseValue(value) {
        if (value === '') return '';
        if (value === 'true') return true;
        if (value === 'false') return false;
        if (!isNaN(value) && !isNaN(parseFloat(value))) return parseFloat(value);
        
        if (value.startsWith('[') && value.endsWith(']')) {
            try {
                return JSON.parse(value);
            } catch (e) {
                return value;
            }
        }
        
        return value;
    }

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
                        <div class="btn-group btn-group-sm">
                            <button type="button" 
                                    class="btn btn-outline-primary" 
                                    onclick="addCondition(${index})">
                                <i class="bi bi-plus"></i> Simple
                            </button>
                            <button type="button" 
                                    class="btn btn-outline-success" 
                                    onclick="addNestedCondition(${index})">
                                <i class="bi bi-diagram-3"></i> Nested
                            </button>
                        </div>
                    </div>
                    <div class="conditions-list" id="conditionsList_${index}">
        `;

        this.conditions.forEach((condition, conditionIndex) => {
            html += this.renderCondition(index, conditionIndex, condition);
        });

        if (this.conditions.length === 0) {
            html += '<p class="text-muted">No conditions defined. Click "Simple" for basic conditions or "Nested" for AND/OR logic.</p>';
        }

        html += `
                    </div>
                </div>
                
                <small class="text-muted">
                    First matching condition wins.
                </small>
            </div>
        `;

        return html;
    }

    renderCondition(componentIndex, conditionIndex, condition) {
        const ifCondition = condition.if || {};
        
        // 🆕 Check if it's nested
        const isNested = this.isNestedCondition(ifCondition);
        const conditionType = ifCondition.and ? 'and' : ifCondition.or ? 'or' : 'simple';
        
        let html = `
            <div class="condition-builder border rounded p-3 mb-3" id="condition_${componentIndex}_${conditionIndex}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">Condition ${conditionIndex + 1}</small>
                        <select class="form-select form-select-sm" style="width: auto;"
                                onchange="updateCondition(${componentIndex}, ${conditionIndex}, 'condition_type', this.value)">
                            <option value="simple" ${conditionType === 'simple' ? 'selected' : ''}>Simple</option>
                            <option value="and" ${conditionType === 'and' ? 'selected' : ''}>AND Group</option>
                            <option value="or" ${conditionType === 'or' ? 'selected' : ''}>OR Group</option>
                        </select>
                    </div>
                    <button type="button" 
                            class="btn btn-sm btn-outline-danger" 
                            onclick="removeCondition(${componentIndex}, ${conditionIndex})"
                            title="Remove Condition">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
        `;

        if (isNested) {
            // 🆕 Render nested conditions
            const conditions = ifCondition[conditionType] || [];
            html += `
                <div class="nested-conditions border rounded p-2 mb-2" style="background: #f8f9fa;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted"><strong>${conditionType.toUpperCase()}</strong> Group:</small>
                        <button type="button" 
                                class="btn btn-sm btn-outline-success"
                                onclick="addConditionToNestedGroup(${componentIndex}, ${conditionIndex}, '${conditionType}')">
                            <i class="bi bi-plus"></i> Add ${conditionType.toUpperCase()}
                        </button>
                    </div>
            `;
            
            conditions.forEach((subCondition, subIndex) => {
                html += `
                    <div class="sub-condition border rounded p-2 mb-2" style="background: white;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Sub-condition ${subIndex + 1}</small>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="removeConditionFromNestedGroup(${componentIndex}, ${conditionIndex}, '${conditionType}', ${subIndex})">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        ${this.renderSimpleConditionForm(componentIndex, conditionIndex, subCondition, `nested_${subIndex}`)}
                    </div>
                `;
                
                if (subIndex < conditions.length - 1) {
                    html += `<div class="text-center my-1"><span class="badge bg-primary">${conditionType.toUpperCase()}</span></div>`;
                }
            });
            
            html += `</div>`;
        } else {
            // 🆕 Render simple condition (existing logic)
            html += this.renderSimpleConditionForm(componentIndex, conditionIndex, ifCondition, 'simple');
        }

        html += `
                <div class="mt-2">
                    <label class="form-label small">Then Result:</label>
                    <input type="text" 
                           class="form-control form-control-sm" 
                           placeholder="Result if condition is true"
                           value="${condition.result || ''}"
                           onchange="updateCondition(${componentIndex}, ${conditionIndex}, 'result', this.value)">
                </div>
            </div>
        `;

        return html;
    }

    // 🆕 Render simple condition form (extracted from original)
    renderSimpleConditionForm(componentIndex, conditionIndex, ifCondition, prefix) {
        return `
            <div class="condition-row">
                <div class="row g-2">
                    <div class="col-md-3">
                        <select class="form-select form-select-sm condition-field" 
                                data-condition-index="${conditionIndex}"
                                data-condition-field="condition_op"
                                data-prefix="${prefix}">
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
                               placeholder="${getValuePlaceholder(ifCondition.op || '>')}"
                               value="${Array.isArray(ifCondition.value) ? JSON.stringify(ifCondition.value) : (ifCondition.value || '')}"
                               onchange="updateCondition(${componentIndex}, ${conditionIndex}, 'condition_value', this.value)">
                    </div>
                </div>
            </div>
        `;
    }

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

    fromJSON(json) {
        this.id = json.id || this.id;
        this.conditions = json.conditions || [];
        this.default = json.default || '';
        this.as = json.as || '';
    }
}

// Global functions for condition management (existing + new)
window.addCondition = function(componentIndex) {
    if (components[componentIndex] && components[componentIndex].instance.addCondition) {
        components[componentIndex].instance.addCondition();
        updateView();
        updateJSON();
    }
};

window.addNestedCondition = function(componentIndex) {
    if (components[componentIndex] && components[componentIndex].instance.addNestedCondition) {
        components[componentIndex].instance.addNestedCondition();
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

// 🆕 Functions for nested logic
window.addConditionToNestedGroup = function(componentIndex, conditionIndex, groupType) {
    // Implementation for adding conditions to nested groups
    console.log('Add condition to nested group:', componentIndex, conditionIndex, groupType);
    // TODO: Implement this based on the nested structure
};

window.removeConditionFromNestedGroup = function(componentIndex, conditionIndex, groupType, subIndex) {
    // Implementation for removing conditions from nested groups
    console.log('Remove condition from nested group:', componentIndex, conditionIndex, groupType, subIndex);
    // TODO: Implement this based on the nested structure
};

// Export for global use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ConditionsComponent;
} else {
    window.ConditionsComponent = ConditionsComponent;
}