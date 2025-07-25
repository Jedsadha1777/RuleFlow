/**
 * Enhanced Scoring Component Class
 * Complete version with all fields and AND/OR logic support
 */

class ScoringComponent {
    constructor() {
        this.id = 'scoring_' + Date.now();
        this.scoring = {
            ifs: {
                vars: [],
                tree: []
            }
        };
        this.as = '';
    }

    /**
     * Get component icon
     */
    getIcon() {
        return '<i class="bi bi-bar-chart"></i>';
    }

    /**
     * Get component title
     */
    getTitle() {
        return this.id || 'Scoring Component';
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
            case 'vars':
                this.scoring.ifs.vars = value ? value.split(',').map(s => s.trim()) : [];
                break;
        }
    }

    /**
     * Add new scoring branch
     */
    addScoringBranch() {
        this.scoring.ifs.tree.push({
            if: { op: '>=', value: 0 },
            ranges: [
                { 
                    if: { op: '==', value: '' }, 
                    score: 0,
                    set_vars: {}
                }
            ]
        });
    }

    /**
     * Remove scoring branch
     */
    removeScoringBranch(branchIndex) {
        this.scoring.ifs.tree.splice(branchIndex, 1);
    }

    /**
     * Update scoring branch condition
     */
    updateScoringBranch(branchIndex, field, value) {
        if (!this.scoring.ifs.tree[branchIndex]) return;

        const branch = this.scoring.ifs.tree[branchIndex];
        
        if (field === 'op') {
            branch.if.op = value;
        } else if (field === 'value') {
            branch.if.value = this.parseValue(value);
        } else if (field === 'condition_type') {
            // Support for AND/OR logic like other components
            if (value === 'simple') {
                branch.if = { op: '>=', value: 0 };
            } else if (value === 'and') {
                branch.if = {
                    and: [
                        { op: '>', var: '', value: '' },
                        { op: '==', var: '', value: '' }
                    ]
                };
            } else if (value === 'or') {
                branch.if = {
                    or: [
                        { op: '>', var: '', value: '' },
                        { op: '==', var: '', value: '' }
                    ]
                };
            }
        }
    }

    /**
     * Update nested condition in branch (for AND/OR support)
     */
    updateNestedBranchCondition(branchIndex, conditionIndex, field, value) {
        if (!this.scoring.ifs.tree[branchIndex]) return;

        const branchIf = this.scoring.ifs.tree[branchIndex].if;
        let condition = null;

        if (branchIf.and && branchIf.and[conditionIndex]) {
            condition = branchIf.and[conditionIndex];
        } else if (branchIf.or && branchIf.or[conditionIndex]) {
            condition = branchIf.or[conditionIndex];
        } else if (!branchIf.and && !branchIf.or) {
            condition = branchIf; // Simple condition
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
     * Add condition to nested group in branch
     */
    addConditionToBranchGroup(branchIndex, groupType) {
        if (!this.scoring.ifs.tree[branchIndex]) return;

        const branchIf = this.scoring.ifs.tree[branchIndex].if;
        const newCondition = { op: '>', var: '', value: '' };

        if (groupType === 'and' && branchIf.and) {
            branchIf.and.push(newCondition);
        } else if (groupType === 'or' && branchIf.or) {
            branchIf.or.push(newCondition);
        }
    }

    /**
     * Remove condition from nested group in branch
     */
    removeConditionFromBranchGroup(branchIndex, conditionIndex, groupType) {
        if (!this.scoring.ifs.tree[branchIndex]) return;

        const branchIf = this.scoring.ifs.tree[branchIndex].if;

        if (groupType === 'and' && branchIf.and) {
            branchIf.and.splice(conditionIndex, 1);
            if (branchIf.and.length === 0) {
                branchIf.and.push({ op: '>', var: '', value: '' });
            }
        } else if (groupType === 'or' && branchIf.or) {
            branchIf.or.splice(conditionIndex, 1);
            if (branchIf.or.length === 0) {
                branchIf.or.push({ op: '>', var: '', value: '' });
            }
        }
    }

    /**
     * Add range to scoring branch
     */
    addRangeToBranch(branchIndex) {
        if (!this.scoring.ifs.tree[branchIndex]) return;

        this.scoring.ifs.tree[branchIndex].ranges.push({
            if: { op: '==', value: '' },
            score: 0,
            set_vars: {}
        });
    }

    /**
     * Remove range from scoring branch
     */
    removeRangeFromBranch(branchIndex, rangeIndex) {
        if (!this.scoring.ifs.tree[branchIndex] || !this.scoring.ifs.tree[branchIndex].ranges) return;

        this.scoring.ifs.tree[branchIndex].ranges.splice(rangeIndex, 1);
        
        if (this.scoring.ifs.tree[branchIndex].ranges.length === 0) {
            this.scoring.ifs.tree[branchIndex].ranges.push({
                if: { op: '==', value: '' },
                score: 0,
                set_vars: {}
            });
        }
    }

    /**
     * Update range field
     */
    updateRangeField(branchIndex, rangeIndex, field, value) {
        if (!this.scoring.ifs.tree[branchIndex] || !this.scoring.ifs.tree[branchIndex].ranges[rangeIndex]) return;

        const range = this.scoring.ifs.tree[branchIndex].ranges[rangeIndex];
        
        if (field === 'op') {
            range.if.op = value;
        } else if (field === 'value') {
            range.if.value = this.parseValue(value);
        } else {
            range[field] = this.parseValue(value);
        }
    }

    /**
     * Update set_vars for a range
     */
    updateSetVars(branchIndex, rangeIndex, varsString) {
        if (!this.scoring.ifs.tree[branchIndex] || !this.scoring.ifs.tree[branchIndex].ranges[rangeIndex]) return;

        const range = this.scoring.ifs.tree[branchIndex].ranges[rangeIndex];
        
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
     * Add custom field to range
     */
    addCustomFieldToRange(branchIndex, rangeIndex, fieldName, value) {
        if (!this.scoring.ifs.tree[branchIndex] || !this.scoring.ifs.tree[branchIndex].ranges[rangeIndex]) return;

        const range = this.scoring.ifs.tree[branchIndex].ranges[rangeIndex];
        range[fieldName] = this.parseValue(value);
    }

    /**
     * Remove custom field from range
     */
    removeCustomFieldFromRange(branchIndex, rangeIndex, fieldName) {
        if (!this.scoring.ifs.tree[branchIndex] || !this.scoring.ifs.tree[branchIndex].ranges[rangeIndex]) return;

        const range = this.scoring.ifs.tree[branchIndex].ranges[rangeIndex];
        if (fieldName !== 'if' && fieldName !== 'score' && fieldName !== 'set_vars') {
            delete range[fieldName];
        }
    }

    //20250725 Enhanced Scoring Range
    /**
     * Update range condition type (เพิ่ม method ใหม่)
     */
    updateRangeConditionType(branchIndex, rangeIndex, conditionType) {
        if (!this.scoring.ifs.tree[branchIndex] || !this.scoring.ifs.tree[branchIndex].ranges[rangeIndex]) return;

        const range = this.scoring.ifs.tree[branchIndex].ranges[rangeIndex];
        
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
     * Update nested condition in range (เพิ่ม method ใหม่)
     */
    updateNestedRangeCondition(branchIndex, rangeIndex, conditionIndex, field, value) {
        if (!this.scoring.ifs.tree[branchIndex] || !this.scoring.ifs.tree[branchIndex].ranges[rangeIndex]) return;

        const rangeIf = this.scoring.ifs.tree[branchIndex].ranges[rangeIndex].if;
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
     * Add condition to nested group in range (เพิ่ม method ใหม่)
     */
    addConditionToRangeGroup(branchIndex, rangeIndex, groupType) {
        if (!this.scoring.ifs.tree[branchIndex] || !this.scoring.ifs.tree[branchIndex].ranges[rangeIndex]) return;

        const rangeIf = this.scoring.ifs.tree[branchIndex].ranges[rangeIndex].if;
        const newCondition = { op: '>', var: '', value: '' };

        if (groupType === 'and' && rangeIf.and) {
            rangeIf.and.push(newCondition);
        } else if (groupType === 'or' && rangeIf.or) {
            rangeIf.or.push(newCondition);
        }
    }

    /**
     * Remove condition from nested group in range (เพิ่ม method ใหม่)
     */
    removeConditionFromRangeGroup(branchIndex, rangeIndex, conditionIndex, groupType) {
        if (!this.scoring.ifs.tree[branchIndex] || !this.scoring.ifs.tree[branchIndex].ranges[rangeIndex]) return;

        const rangeIf = this.scoring.ifs.tree[branchIndex].ranges[rangeIndex].if;

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
    //20250725 Enhanced Scoring Range end



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
     * Get all custom fields from ranges
     */
    getAllCustomFields() {
        const fields = new Set(['score']);
        
        this.scoring.ifs.tree.forEach(branch => {
            branch.ranges.forEach(range => {
                Object.keys(range).forEach(key => {
                    if (key !== 'if' && key !== 'set_vars') {
                        fields.add(key);
                    }
                });
            });
        });
        
        return Array.from(fields);
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
                    <label class="form-label">Scoring Variables</label>
                    <input type="text" 
                           class="form-control" 
                           placeholder="$perf_score, years_service"
                           value="${this.scoring.ifs.vars.join(', ')}" 
                           data-field="vars">
                    <small class="text-muted">Variables for multi-dimensional scoring (use $ for calculated vars)</small>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Scoring Matrix</label>
                        <button type="button" 
                                class="btn btn-sm btn-outline-primary add-scoring-branch-btn">
                            <i class="bi bi-plus"></i> Add Dimension
                        </button>
                    </div>
                    <div class="scoring-matrix">
        `;

        this.scoring.ifs.tree.forEach((branch, branchIndex) => {
            html += this.renderScoringBranch(index, branchIndex, branch);
        });

        if (this.scoring.ifs.tree.length === 0) {
            html += `
                <div class="text-center p-4 border rounded bg-light">
                    <i class="bi bi-bar-chart fs-1 text-muted mb-2"></i>
                    <p class="text-muted mb-2">No scoring dimensions defined</p>
                    <p class="small text-muted">Click "Add Dimension" to create scoring logic</p>
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
     * Render scoring branch (dimension)
     */
    renderScoringBranch(componentIndex, branchIndex, branch) {
        const condition = branch.if || {};
        const var1 = this.scoring.ifs.vars[0] || 'var1';
        const var2 = this.scoring.ifs.vars[1] || 'var2';
        const isNested = condition.and || condition.or;
        const conditionType = condition.and ? 'and' : condition.or ? 'or' : 'simple';
        
        let html = `
            <div class="scoring-dimension border rounded p-3 mb-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">
                        <i class="bi bi-layers text-primary me-2"></i>
                        Dimension ${branchIndex + 1}: <strong>${var1}</strong>
                    </h6>
                    <button type="button" 
                            class="btn btn-sm btn-outline-danger remove-scoring-branch-btn" 
                            data-branch-index="${branchIndex}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                
                <div class="primary-condition mb-3 p-3 border rounded" style="background: #ffffff;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small mb-0">Primary Condition Type:</label>
                        <select class="form-select form-select-sm me-2 condition-type-select" 
                                style="width: auto;"
                                data-branch-index="${branchIndex}"
                                data-branch-field="condition_type">
                            <option value="simple" ${conditionType === 'simple' ? 'selected' : ''}>Simple</option>
                            <option value="and" ${conditionType === 'and' ? 'selected' : ''}>AND Logic</option>
                            <option value="or" ${conditionType === 'or' ? 'selected' : ''}>OR Logic</option>
                        </select>
                    </div>
        `;

        if (isNested) {
            html += this.renderNestedBranchCondition(componentIndex, branchIndex, condition, conditionType);
        } else {
            html += this.renderSimpleBranchCondition(componentIndex, branchIndex, condition, var1);
        }

        html += `
                </div>
                
                <div class="scoring-ranges">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small mb-0">Then score by <strong>${var2}</strong>:</label>
                        <button type="button" 
                                class="btn btn-sm btn-outline-success add-range-to-branch-btn"
                                data-branch-index="${branchIndex}">
                            <i class="bi bi-plus"></i> Add Range
                        </button>
                    </div>
        `;

        branch.ranges.forEach((range, rangeIndex) => {
            html += this.renderScoringRange(componentIndex, branchIndex, rangeIndex, range);
        });

        html += `
                </div>
            </div>
        `;

        return html;
    }

    /**
     * Render simple branch condition
     */
    renderSimpleBranchCondition(componentIndex, branchIndex, condition, varName) {
        return `
            <label class="form-label small mb-2">When <strong>${varName}</strong>:</label>
            <div class="row g-2">
                <div class="col-md-4">
                    <select class="form-select form-select-sm scoring-branch-field" 
                            data-branch-index="${branchIndex}"
                            data-branch-field="op">
                        ${getOperatorOptions(condition.op)}
                    </select>
                </div>
                <div class="col-md-8">
                    <input type="text" 
                           class="form-control form-control-sm scoring-branch-field" 
                           placeholder="${getValuePlaceholder(condition.op)}"
                           value="${this.formatValue(condition.value)}"
                           data-branch-index="${branchIndex}"
                           data-branch-field="value">
                </div>
            </div>
        `;
    }

    /**
     * Render nested branch condition (AND/OR)
     */
    renderNestedBranchCondition(componentIndex, branchIndex, condition, conditionType) {
        const conditions = condition[conditionType] || [];
        const logicLabel = conditionType.toUpperCase();
        
        let html = `
            <div class="nested-branch-condition">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-${conditionType === 'and' ? 'primary' : 'info'} fs-6">${logicLabel} Logic</span>
                    <button type="button" 
                            class="btn btn-sm btn-outline-success add-condition-to-branch-group-btn"
                            data-branch-index="${branchIndex}"
                            data-group-type="${conditionType}">
                        <i class="bi bi-plus"></i> Add ${logicLabel}
                    </button>
                </div>
        `;

        conditions.forEach((subCondition, conditionIndex) => {
            html += `
                <div class="nested-branch-condition-item mb-2 p-2 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">Condition ${conditionIndex + 1}</small>
                        <button type="button" 
                                class="btn btn-sm btn-outline-danger remove-condition-from-branch-group-btn"
                                data-branch-index="${branchIndex}"
                                data-condition-index="${conditionIndex}"
                                data-group-type="${conditionType}">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm nested-branch-condition-field" 
                                    data-branch-index="${branchIndex}"
                                    data-condition-index="${conditionIndex}"
                                    data-condition-field="op">
                                ${getOperatorOptions(subCondition.op)}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" 
                                   class="form-control form-control-sm nested-branch-condition-field" 
                                   placeholder="Variable"
                                   value="${subCondition.var || subCondition.field || ''}"
                                   data-branch-index="${branchIndex}"
                                   data-condition-index="${conditionIndex}"
                                   data-condition-field="var">
                        </div>
                        <div class="col-md-5">
                            <input type="text" 
                                   class="form-control form-control-sm nested-branch-condition-field" 
                                   placeholder="${getValuePlaceholder(subCondition.op)}"
                                   value="${this.formatValue(subCondition.value)}"
                                   data-branch-index="${branchIndex}"
                                   data-condition-index="${conditionIndex}"
                                   data-condition-field="value">
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
     * Render scoring range
     * Enhanced renderScoringRange method - รองรับ AND/OR
     */
    renderScoringRange(componentIndex, branchIndex, rangeIndex, range) {
        const condition = range.if || {};
        const customFields = this.getAllCustomFields();
        const isNested = condition.and || condition.or;
        const conditionType = condition.and ? 'and' : condition.or ? 'or' : 'simple';
        
        let html = `
            <div class="scoring-range border rounded p-3 mb-2" style="background: #ffffff;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-secondary">Range ${rangeIndex + 1}</span>
                    <div class="btn-group btn-group-sm">
                        <button type="button" 
                                class="btn btn-outline-info add-custom-field-btn"
                                data-branch-index="${branchIndex}"
                                data-range-index="${rangeIndex}">
                            <i class="bi bi-plus-circle"></i>
                        </button>
                        <button type="button" 
                                class="btn btn-outline-danger remove-range-from-branch-btn"
                                data-branch-index="${branchIndex}"
                                data-range-index="${rangeIndex}">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
                
                <!-- 🆕 Condition Type Selector -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small mb-0">Condition Type:</label>
                        <select class="form-select form-select-sm" 
                                style="width: auto;"
                                data-branch-index="${branchIndex}"
                                data-range-index="${rangeIndex}"
                                onchange="updateRangeConditionType(${componentIndex}, ${branchIndex}, ${rangeIndex}, this.value)">
                            <option value="simple" ${conditionType === 'simple' ? 'selected' : ''}>Simple</option>
                            <option value="and" ${conditionType === 'and' ? 'selected' : ''}>AND Logic</option>
                            <option value="or" ${conditionType === 'or' ? 'selected' : ''}>OR Logic</option>
                        </select>
                    </div>
        `;

        // Render condition based on type
        if (isNested) {
            html += this.renderNestedRangeCondition(componentIndex, branchIndex, rangeIndex, condition, conditionType);
        } else {
            html += this.renderSimpleRangeCondition(componentIndex, branchIndex, rangeIndex, condition);
        }

        html += `
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small">Score <span class="text-danger">*</span></label>
                        <input type="number" 
                            class="form-control form-control-sm scoring-range-field" 
                            placeholder="Numeric score"
                            value="${range.score || ''}"
                            data-branch-index="${branchIndex}"
                            data-range-index="${rangeIndex}"
                            data-range-field="score"
                            step="any">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Set Variables</label>
                        <input type="text" 
                            class="form-control form-control-sm set-vars-field" 
                            placeholder="$multiplier = 2.5, $rate = 0.15"
                            value="${this.formatSetVars(range.set_vars)}"
                            data-branch-index="${branchIndex}"
                            data-range-index="${rangeIndex}">
                    </div>
                </div>
        `;

        // Custom fields (existing code)
        const excludeFields = ['score', 'set_vars', 'if'];
        const customFieldsOnly = customFields.filter(field => !excludeFields.includes(field));
        
        if (customFieldsOnly.length > 0) {
            html += `<div class="row g-2">`;
            
            customFieldsOnly.forEach((fieldName) => {
                const value = range[fieldName] || '';
                
                html += `
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label small">${fieldName}</label>
                            <button type="button" 
                                    class="btn btn-sm btn-link text-danger p-0 remove-custom-field-btn"
                                    data-branch-index="${branchIndex}"
                                    data-range-index="${rangeIndex}"
                                    data-field-name="${fieldName}">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        <input type="text" 
                            class="form-control form-control-sm scoring-range-field" 
                            placeholder="Enter ${fieldName}"
                            value="${value}"
                            data-branch-index="${branchIndex}"
                            data-range-index="${rangeIndex}"
                            data-range-field="${fieldName}">
                    </div>
                `;
            });
            
            html += `</div>`;
        }

        html += `</div>`;
        return html;
    }

    /**
     * Render simple range condition (เพิ่ม method ใหม่)
     */
    renderSimpleRangeCondition(componentIndex, branchIndex, rangeIndex, condition) {
        return `
            <div class="simple-range-condition">
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small">Operator</label>
                        <select class="form-select form-select-sm scoring-range-field" 
                                data-branch-index="${branchIndex}"
                                data-range-index="${rangeIndex}"
                                data-range-field="op">
                            ${getOperatorOptions(condition.op)}
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small">Value</label>
                        <input type="text" 
                            class="form-control form-control-sm scoring-range-field" 
                            placeholder="${getValuePlaceholder(condition.op)}"
                            value="${this.formatValue(condition.value)}"
                            data-branch-index="${branchIndex}"
                            data-range-index="${rangeIndex}"
                            data-range-field="value">
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Render nested range condition (เพิ่ม method ใหม่)
     */
    renderNestedRangeCondition(componentIndex, branchIndex, rangeIndex, condition, conditionType) {
        const conditions = condition[conditionType] || [];
        const logicLabel = conditionType.toUpperCase();
        
        let html = `
            <div class="nested-range-condition">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-${conditionType === 'and' ? 'primary' : 'info'} fs-6">${logicLabel} Logic</span>
                    <button type="button" 
                            class="btn btn-sm btn-outline-success"
                            onclick="addConditionToRangeGroup(${componentIndex}, ${branchIndex}, ${rangeIndex}, '${conditionType}')">
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
                                onclick="removeConditionFromRangeGroup(${componentIndex}, ${branchIndex}, ${rangeIndex}, ${conditionIndex}, '${conditionType}')">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" 
                                    onchange="updateNestedRangeCondition(${componentIndex}, ${branchIndex}, ${rangeIndex}, ${conditionIndex}, 'op', this.value)">
                                ${getOperatorOptions(subCondition.op)}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" 
                                class="form-control form-control-sm" 
                                placeholder="Variable"
                                value="${subCondition.var || subCondition.field || ''}"
                                onchange="updateNestedRangeCondition(${componentIndex}, ${branchIndex}, ${rangeIndex}, ${conditionIndex}, 'var', this.value)">
                        </div>
                        <div class="col-md-5">
                            <input type="text" 
                                class="form-control form-control-sm" 
                                placeholder="${getValuePlaceholder(subCondition.op)}"
                                value="${this.formatValue(subCondition.value)}"
                                onchange="updateNestedRangeCondition(${componentIndex}, ${branchIndex}, ${rangeIndex}, ${conditionIndex}, 'value', this.value)">
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
     * Format value for display
     */
    formatValue(value) {
        if (Array.isArray(value)) {
            return JSON.stringify(value);
        }
        return value || '';
    }

    /**
     * Convert to JSON configuration
     */
    toJSON() {
        const json = {
            id: this.id,
            scoring: this.scoring
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
        this.scoring = json.scoring || { ifs: { vars: [], tree: [] } };
        this.as = json.as || '';
    }
}

// Export for global use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ScoringComponent;
} else {
    window.ScoringComponent = ScoringComponent;
}