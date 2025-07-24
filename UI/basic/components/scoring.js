/**
 * Enhanced Scoring Component with complete operators support
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

    getIcon() {
        return '<i class="bi bi-bar-chart"></i>';
    }

    getTitle() {
        return this.id || 'Scoring Component';
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
            case 'as':
                this.as = value;
                break;
            case 'vars':
                this.scoring.ifs.vars = value ? value.split(',').map(s => s.trim()) : [];
                break;
        }
    }

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

    removeScoringBranch(branchIndex) {
        this.scoring.ifs.tree.splice(branchIndex, 1);
    }

    updateScoringBranch(branchIndex, field, value) {
        if (!this.scoring.ifs.tree[branchIndex]) return;

        const branch = this.scoring.ifs.tree[branchIndex];
        
        if (field === 'op') {
            branch.if.op = value;
        } else if (field === 'value') {
            branch.if.value = this.parseValue(value);
        }
    }

    addRangeToBranch(branchIndex) {
        if (!this.scoring.ifs.tree[branchIndex]) return;

        this.scoring.ifs.tree[branchIndex].ranges.push({
            if: { op: '==', value: '' },
            score: 0,
            set_vars: {}
        });
    }

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

    addCustomFieldToRange(branchIndex, rangeIndex, fieldName, value) {
        if (!this.scoring.ifs.tree[branchIndex] || !this.scoring.ifs.tree[branchIndex].ranges[rangeIndex]) return;

        const range = this.scoring.ifs.tree[branchIndex].ranges[rangeIndex];
        range[fieldName] = this.parseValue(value);
    }

    removeCustomFieldFromRange(branchIndex, rangeIndex, fieldName) {
        if (!this.scoring.ifs.tree[branchIndex] || !this.scoring.ifs.tree[branchIndex].ranges[rangeIndex]) return;

        const range = this.scoring.ifs.tree[branchIndex].ranges[rangeIndex];
        if (fieldName !== 'if' && fieldName !== 'score' && fieldName !== 'set_vars') {
            delete range[fieldName];
        }
    }

    formatSetVars(setVars) {
        if (!setVars || Object.keys(setVars).length === 0) return '';
        
        return Object.entries(setVars)
            .map(([key, value]) => `${key} = ${JSON.stringify(value)}`)
            .join(', ');
    }

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

    renderScoringBranch(componentIndex, branchIndex, branch) {
        const condition = branch.if || {};
        const var1 = this.scoring.ifs.vars[0] || 'var1';
        const var2 = this.scoring.ifs.vars[1] || 'var2';
        
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
                    <label class="form-label small mb-2">When <strong>${var1}</strong>:</label>
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

    renderScoringRange(componentIndex, branchIndex, rangeIndex, range) {
        const condition = range.if || {};
        const customFields = this.getAllCustomFields();
        
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
                
                <div class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small">Condition</label>
                        <select class="form-select form-select-sm scoring-range-field" 
                                data-branch-index="${branchIndex}"
                                data-range-index="${rangeIndex}"
                                data-range-field="op">
                            ${getOperatorOptions(condition.op)}
                        </select>
                    </div>
                    <div class="col-md-9">
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

        // Custom fields
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

    formatValue(value) {
        if (Array.isArray(value)) {
            return JSON.stringify(value);
        }
        return value || '';
    }

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