/**
 * Rules Component Class - Accumulative Scoring
 * ไม่มี tree/ifs แค่ rules array ธรรมดา แต่รองรับ AND/OR ใน if conditions
 */

class RulesComponent {
    constructor() {
        this.id = 'rules_' + Date.now();
        this.rules = [];
        this.as = '';
    }

    /**
     * Get component icon
     */
    getIcon() {
        return '<i class="bi bi-calculator"></i>';
    }

    /**
     * Get component title
     */
    getTitle() {
        return this.id || 'Rules Component';
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
            case 'as':
                this.as = value;
                break;
        }
    }

    /**
     * Add new rule
     */
    addRule() {
        this.rules.push({
            var: '',
            ranges: [
                { 
                    if: { op: '>=', value: 0 }, 
                    score: 0,
                    set_vars: {}
                }
            ]
        });
    }

    /**
     * Remove rule
     */
    removeRule(ruleIndex) {
        this.rules.splice(ruleIndex, 1);
    }

    /**
     * Update rule variable
     */
    updateRuleVar(ruleIndex, value) {
        if (this.rules[ruleIndex]) {
            this.rules[ruleIndex].var = value;
        }
    }

    /**
     * Add range to rule
     */
    addRangeToRule(ruleIndex) {
        if (!this.rules[ruleIndex]) return;

        this.rules[ruleIndex].ranges.push({
            if: { op: '>=', value: 0 },
            score: 0,
            set_vars: {}
        });
    }

    /**
     * Remove range from rule
     */
    removeRangeFromRule(ruleIndex, rangeIndex) {
        if (!this.rules[ruleIndex] || !this.rules[ruleIndex].ranges) return;

        this.rules[ruleIndex].ranges.splice(rangeIndex, 1);
        
        if (this.rules[ruleIndex].ranges.length === 0) {
            this.rules[ruleIndex].ranges.push({
                if: { op: '>=', value: 0 },
                score: 0,
                set_vars: {}
            });
        }
    }

    /**
     * Update range condition type
     */
    updateRangeConditionType(ruleIndex, rangeIndex, conditionType) {
        if (!this.rules[ruleIndex] || !this.rules[ruleIndex].ranges[rangeIndex]) return;

        const range = this.rules[ruleIndex].ranges[rangeIndex];
        
        if (conditionType === 'simple') {
            range.if = { op: '>=', value: 0 };
        } else if (conditionType === 'and') {
            range.if = {
                and: [
                    { op: '>', var: '', value: '' },
                    { op: '==', var: '', value: '' }
                ]
            };
        } else if (conditionType === 'or') {
            range.if = {
                or: [
                    { op: '>', var: '', value: '' },
                    { op: '==', var: '', value: '' }
                ]
            };
        }
    }

    /**
     * Update range field
     */
    updateRangeField(ruleIndex, rangeIndex, field, value) {
        if (!this.rules[ruleIndex] || !this.rules[ruleIndex].ranges[rangeIndex]) return;

        const range = this.rules[ruleIndex].ranges[rangeIndex];
        
        if (field === 'op') {
            range.if.op = value;
        } else if (field === 'value') {
            range.if.value = this.parseValue(value);
        } else {
            range[field] = this.parseValue(value);
        }
    }

    /**
     * Update nested condition in range
     */
    updateNestedRangeCondition(ruleIndex, rangeIndex, conditionIndex, field, value) {
        if (!this.rules[ruleIndex] || !this.rules[ruleIndex].ranges[rangeIndex]) return;

        const rangeIf = this.rules[ruleIndex].ranges[rangeIndex].if;
        let condition = null;

        if (rangeIf.and && rangeIf.and[conditionIndex]) {
            condition = rangeIf.and[conditionIndex];
        } else if (rangeIf.or && rangeIf.or[conditionIndex]) {
            condition = rangeIf.or[conditionIndex];
        } else if (!rangeIf.and && !rangeIf.or) {
            condition = rangeIf; // Simple condition
        }

        if (condition) {
            if (field === 'op') {
                condition.op = value;
            } else if (field === 'var') {
                condition.var = value;
            } else if (field === 'value') {
                condition.value = this.parseValue(value);
            }
        }
    }

    /**
     * Add condition to nested group
     */
    addConditionToRangeGroup(ruleIndex, rangeIndex, groupType) {
        if (!this.rules[ruleIndex] || !this.rules[ruleIndex].ranges[rangeIndex]) return;

        const rangeIf = this.rules[ruleIndex].ranges[rangeIndex].if;
        const newCondition = { op: '>', var: '', value: '' };

        if (groupType === 'and' && rangeIf.and) {
            rangeIf.and.push(newCondition);
        } else if (groupType === 'or' && rangeIf.or) {
            rangeIf.or.push(newCondition);
        }
    }

    /**
     * Remove condition from nested group
     */
    removeConditionFromRangeGroup(ruleIndex, rangeIndex, conditionIndex, groupType) {
        if (!this.rules[ruleIndex] || !this.rules[ruleIndex].ranges[rangeIndex]) return;

        const rangeIf = this.rules[ruleIndex].ranges[rangeIndex].if;

        if (groupType === 'and' && rangeIf.and) {
            rangeIf.and.splice(conditionIndex, 1);
            if (rangeIf.and.length === 0) {
                rangeIf.and.push({ op: '>', var: '', value: '' });
            }
        } else if (groupType === 'or' && rangeIf.or) {
            rangeIf.or.splice(conditionIndex, 1);
            if (rangeIf.or.length === 0) {
                rangeIf.or.push({ op: '>', var: '', value: '' });
            }
        }
    }

    /**
     * Update set_vars for a range
     */
    updateSetVars(ruleIndex, rangeIndex, varsString) {
        if (!this.rules[ruleIndex] || !this.rules[ruleIndex].ranges[rangeIndex]) return;

        const range = this.rules[ruleIndex].ranges[rangeIndex];
        
        try {
            const setVars = {};
            if (varsString.trim()) {
                const pairs = varsString.split(',');
                pairs.forEach(pair => {
                    const [key, value] = pair.split('=').map(s => s.trim());
                    if (key && value) {
                        setVars[key] = this.parseValue(value);
                    }
                });
            }
            range.set_vars = setVars;
        } catch (error) {
            console.warn('Invalid set_vars format:', varsString);
        }
    }

    /**
     * Format set_vars for display
     */
    formatSetVars(setVars) {
        if (!setVars || Object.keys(setVars).length === 0) return '';
        
        return Object.entries(setVars)
            .map(([key, value]) => `${key} = ${JSON.stringify(value)}`)
            .join(', ');
    }

    /**
     * Parse value to appropriate type
     */
    parseValue(value) {
        if (value === 'true') return true;
        if (value === 'false') return false;
        if (/^\d+$/.test(value)) return parseInt(value);
        if (/^\d*\.\d+$/.test(value)) return parseFloat(value);
        
        if (value.startsWith('[') && value.endsWith(']')) {
            try {
                return JSON.parse(value);
            } catch (e) {
                return value;
            }
        }
        
        return value;
    }

    /**
     * Format value for display
     */
    formatValue(value) {
        if (Array.isArray(value)) {
            return JSON.stringify(value);
        }
        return value || '';
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
                               data-field="id">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Store As Variable (optional)</label>
                        <input type="text" 
                               class="form-control" 
                               placeholder="$variable_name"
                               value="${this.as}" 
                               data-field="as">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Accumulative Rules</label>
                        <button type="button" 
                                class="btn btn-sm btn-outline-primary add-rule-btn">
                            <i class="bi bi-plus"></i> Add Rule
                        </button>
                    </div>
                    <div class="rules-container">
        `;

        this.rules.forEach((rule, ruleIndex) => {
            html += this.renderRule(index, ruleIndex, rule);
        });

        if (this.rules.length === 0) {
            html += `
                <div class="text-center p-4 border rounded bg-light">
                    <i class="bi bi-calculator fs-1 text-muted mb-2"></i>
                    <p class="text-muted mb-2">No rules defined</p>
                    <p class="small text-muted">Click "Add Rule" to create scoring logic</p>
                </div>
            `;
        }

        html += `
                    </div>
                </div>
            </div>
        `;

        return html;
    }

    /**
     * Render single rule
     */
    renderRule(componentIndex, ruleIndex, rule) {
        let html = `
            <div class="rule-item border rounded p-3 mb-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        <i class="bi bi-gear text-primary me-2"></i>
                        Rule ${ruleIndex + 1}
                    </h6>
                    <button type="button" 
                            class="btn btn-sm btn-outline-danger remove-rule-btn" 
                            data-rule-index="${ruleIndex}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small">Variable Name</label>
                    <input type="text" 
                           class="form-control rule-var-field" 
                           placeholder="income, age, credit_score, etc."
                           value="${rule.var || ''}"
                           data-rule-index="${ruleIndex}">
                    <small class="text-muted">Variable to evaluate (use $ for calculated vars)</small>
                </div>
                
                <div class="rules-ranges">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small mb-0">Score Ranges</label>
                        <button type="button" 
                                class="btn btn-sm btn-outline-success add-range-to-rule-btn"
                                data-rule-index="${ruleIndex}">
                            <i class="bi bi-plus"></i> Add Range
                        </button>
                    </div>
        `;

        rule.ranges.forEach((range, rangeIndex) => {
            html += this.renderRange(componentIndex, ruleIndex, rangeIndex, range);
        });

        html += `
                </div>
            </div>
        `;

        return html;
    }

    /**
     * Render range with AND/OR support
     */
    renderRange(componentIndex, ruleIndex, rangeIndex, range) {
        const condition = range.if || {};
        const isNested = condition.and || condition.or;
        const conditionType = condition.and ? 'and' : condition.or ? 'or' : 'simple';
        
        let html = `
            <div class="rule-range border rounded p-3 mb-2" style="background: #ffffff;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-info">Range ${rangeIndex + 1}</span>
                    <button type="button" 
                            class="btn btn-sm btn-outline-danger remove-range-from-rule-btn"
                            data-rule-index="${ruleIndex}"
                            data-range-index="${rangeIndex}">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                
                <!-- Condition Type Selector -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small mb-0">Condition Type:</label>
                        <select class="form-select form-select-sm" 
                                style="width: auto;"
                                data-rule-index="${ruleIndex}"
                                data-range-index="${rangeIndex}"
                                onchange="updateRuleRangeConditionType(${componentIndex}, ${ruleIndex}, ${rangeIndex}, this.value)">
                            <option value="simple" ${conditionType === 'simple' ? 'selected' : ''}>Simple</option>
                            <option value="and" ${conditionType === 'and' ? 'selected' : ''}>AND Logic</option>
                            <option value="or" ${conditionType === 'or' ? 'selected' : ''}>OR Logic</option>
                        </select>
                    </div>
        `;

        // Render condition based on type
        if (isNested) {
            html += this.renderNestedRangeCondition(componentIndex, ruleIndex, rangeIndex, condition, conditionType);
        } else {
            html += this.renderSimpleRangeCondition(componentIndex, ruleIndex, rangeIndex, condition);
        }

        html += `
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">Score <span class="text-danger">*</span></label>
                        <input type="number" 
                               class="form-control form-control-sm rule-range-field" 
                               placeholder="Points to add"
                               value="${range.score || ''}"
                               data-rule-index="${ruleIndex}"
                               data-range-index="${rangeIndex}"
                               data-range-field="score"
                               step="any">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Set Variables</label>
                        <input type="text" 
                               class="form-control form-control-sm rule-set-vars-field" 
                               placeholder="$level = 'high', $multiplier = 1.5"
                               value="${this.formatSetVars(range.set_vars)}"
                               data-rule-index="${ruleIndex}"
                               data-range-index="${rangeIndex}">
                    </div>
                </div>
            </div>
        `;

        return html;
    }

    /**
     * Render simple range condition
     */
    renderSimpleRangeCondition(componentIndex, ruleIndex, rangeIndex, condition) {
        return `
            <div class="simple-range-condition">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small">Operator</label>
                        <select class="form-select form-select-sm rule-range-field" 
                                data-rule-index="${ruleIndex}"
                                data-range-index="${rangeIndex}"
                                data-range-field="op">
                            ${getOperatorOptions(condition.op)}
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small">Value</label>
                        <input type="text" 
                               class="form-control form-control-sm rule-range-field" 
                               placeholder="${getValuePlaceholder(condition.op)}"
                               value="${this.formatValue(condition.value)}"
                               data-rule-index="${ruleIndex}"
                               data-range-index="${rangeIndex}"
                               data-range-field="value">
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Render nested range condition (AND/OR)
     */
    renderNestedRangeCondition(componentIndex, ruleIndex, rangeIndex, condition, conditionType) {
        const conditions = condition[conditionType] || [];
        const logicLabel = conditionType.toUpperCase();
        
        let html = `
            <div class="nested-range-condition">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-${conditionType === 'and' ? 'primary' : 'info'} fs-6">${logicLabel} Logic</span>
                    <button type="button" 
                            class="btn btn-sm btn-outline-success"
                            onclick="addConditionToRuleRangeGroup(${componentIndex}, ${ruleIndex}, ${rangeIndex}, '${conditionType}')">
                        <i class="bi bi-plus"></i> Add ${logicLabel}
                    </button>
                </div>
        `;

        conditions.forEach((subCondition, conditionIndex) => {
            html += `
                <div class="nested-range-condition-item mb-2 p-2 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">Condition ${conditionIndex + 1}</small>
                        <button type="button" 
                                class="btn btn-sm btn-outline-danger"
                                onclick="removeConditionFromRuleRangeGroup(${componentIndex}, ${ruleIndex}, ${rangeIndex}, ${conditionIndex}, '${conditionType}')">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" 
                                    onchange="updateNestedRuleRangeCondition(${componentIndex}, ${ruleIndex}, ${rangeIndex}, ${conditionIndex}, 'op', this.value)">
                                ${getOperatorOptions(subCondition.op)}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   placeholder="Variable"
                                   value="${subCondition.var || subCondition.field || ''}"
                                   onchange="updateNestedRuleRangeCondition(${componentIndex}, ${ruleIndex}, ${rangeIndex}, ${conditionIndex}, 'var', this.value)">
                        </div>
                        <div class="col-md-5">
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   placeholder="${getValuePlaceholder(subCondition.op)}"
                                   value="${this.formatValue(subCondition.value)}"
                                   onchange="updateNestedRuleRangeCondition(${componentIndex}, ${ruleIndex}, ${rangeIndex}, ${conditionIndex}, 'value', this.value)">
                        </div>
                    </div>
                </div>
            `;

            if (conditionIndex < conditions.length - 1) {
                html += `<div class="text-center my-1"><span class="badge bg-secondary">${logicLabel}</span></div>`;
            }
        });

        html += `</div>`;
        return html;
    }

    /**
     * Convert to JSON configuration
     */
    toJSON() {
        const json = {
            id: this.id,
            rules: this.rules
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
        this.rules = json.rules || [];
        this.as = json.as || '';
    }
}

// Export for global use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RulesComponent;
} else {
    window.RulesComponent = RulesComponent;
}