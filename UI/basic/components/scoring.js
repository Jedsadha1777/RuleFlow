/**
 * Scoring Component Class
 * Handles multi-dimensional scoring with nested conditions
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
                { if: { op: '==', value: '' }, score: 0, category: '' }
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
     * Update scoring branch
     */
    updateScoringBranch(branchIndex, field, value) {
        if (!this.scoring.ifs.tree[branchIndex]) return;

        const branch = this.scoring.ifs.tree[branchIndex];
        
        if (field === 'op') {
            branch.if.op = value;
        } else if (field === 'value') {
            branch.if.value = this.parseValue(value);
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
            category: ''
        });
    }

    /**
     * Remove range from scoring branch
     */
    removeRangeFromBranch(branchIndex, rangeIndex) {
        if (!this.scoring.ifs.tree[branchIndex] || !this.scoring.ifs.tree[branchIndex].ranges) return;

        this.scoring.ifs.tree[branchIndex].ranges.splice(rangeIndex, 1);
        
        // Ensure at least one range exists
        if (this.scoring.ifs.tree[branchIndex].ranges.length === 0) {
            this.scoring.ifs.tree[branchIndex].ranges.push({
                if: { op: '==', value: '' },
                score: 0,
                category: ''
            });
        }
    }

    /**
     * Update range in scoring branch
     */
    updateRangeInBranch(branchIndex, rangeIndex, field, value) {
        if (!this.scoring.ifs.tree[branchIndex] || !this.scoring.ifs.tree[branchIndex].ranges[rangeIndex]) return;

        const range = this.scoring.ifs.tree[branchIndex].ranges[rangeIndex];
        
        if (field === 'op') {
            range.if.op = value;
        } else if (field === 'value') {
            range.if.value = this.parseValue(value);
        } else if (field === 'score') {
            range.score = parseFloat(value) || 0;
        } else if (field === 'category') {
            range.category = value;
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
        
        // Handle arrays for 'between' operator
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
                           placeholder="investment_amount, risk_tolerance"
                           value="${this.scoring.ifs.vars.join(', ')}" 
                           data-field="vars">
                    <small class="text-muted">Comma-separated list of variables used for scoring</small>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Scoring Tree</label>
                        <button type="button" 
                                class="btn btn-sm btn-outline-primary add-scoring-branch-btn">
                            <i class="bi bi-plus"></i> Add Branch
                        </button>
                    </div>
                    <div class="scoring-tree">
        `;

        this.scoring.ifs.tree.forEach((branch, branchIndex) => {
            html += this.renderScoringBranch(index, branchIndex, branch);
        });

        if (this.scoring.ifs.tree.length === 0) {
            html += '<p class="text-muted">No scoring branches defined. Click "Add Branch" to create scoring logic.</p>';
        }

        html += `
                    </div>
                </div>
            </div>
        `;

        return html;
    }

    /**
     * Render scoring branch
     */
    renderScoringBranch(componentIndex, branchIndex, branch) {
        const condition = branch.if || {};
        
        let html = `
            <div class="scoring-branch border rounded p-3 mb-3" style="background: #f8f9fa;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Branch ${branchIndex + 1}: Primary Condition</h6>
                    <button type="button" 
                            class="btn btn-sm btn-outline-danger remove-scoring-branch-btn" 
                            data-branch-index="${branchIndex}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                
                <div class="primary-condition mb-3 p-2 border rounded" style="background: #ffffff;">
                    <label class="form-label small">When <strong>${this.scoring.ifs.vars[0] || 'variable1'}</strong>:</label>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select class="form-select form-select-sm scoring-branch-field" 
                                    data-branch-index="${branchIndex}"
                                    data-branch-field="op">
                                <option value=">=" ${condition.op === '>=' ? 'selected' : ''}>Greater or Equal</option>
                                <option value=">" ${condition.op === '>' ? 'selected' : ''}>Greater Than</option>
                                <option value="<=" ${condition.op === '<=' ? 'selected' : ''}>Less or Equal</option>
                                <option value="<" ${condition.op === '<' ? 'selected' : ''}>Less Than</option>
                                <option value="==" ${condition.op === '==' ? 'selected' : ''}>Equals</option>
                                <option value="between" ${condition.op === 'between' ? 'selected' : ''}>Between</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <input type="text" 
                                   class="form-control form-control-sm scoring-branch-field" 
                                   placeholder="Value or [min,max] for between"
                                   value="${this.formatValue(condition.value)}"
                                   data-branch-index="${branchIndex}"
                                   data-branch-field="value">
                        </div>
                    </div>
                </div>
                
                <div class="scoring-ranges">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small mb-0">Then score based on <strong>${this.scoring.ifs.vars[1] || 'variable2'}</strong>:</label>
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
     * Render scoring range
     */
    renderScoringRange(componentIndex, branchIndex, rangeIndex, range) {
        const condition = range.if || {};
        
        return `
            <div class="scoring-range border rounded p-2 mb-2" style="background: #ffffff;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">Range ${rangeIndex + 1}</small>
                    <button type="button" 
                            class="btn btn-sm btn-outline-danger remove-range-from-branch-btn"
                            data-branch-index="${branchIndex}"
                            data-range-index="${rangeIndex}">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                
                <div class="row g-2">
                    <div class="col-md-3">
                        <select class="form-select form-select-sm scoring-range-field" 
                                data-branch-index="${branchIndex}"
                                data-range-index="${rangeIndex}"
                                data-range-field="op">
                            <option value=="==" ${condition.op === '==' ? 'selected' : ''}>Equals</option>
                            <option value="!=" ${condition.op === '!=' ? 'selected' : ''}>Not Equals</option>
                            <option value=">" ${condition.op === '>' ? 'selected' : ''}>Greater Than</option>
                            <option value=">=" ${condition.op === '>=' ? 'selected' : ''}>Greater or Equal</option>
                            <option value="<" ${condition.op === '<' ? 'selected' : ''}>Less Than</option>
                            <option value="<=" ${condition.op === '<=' ? 'selected' : ''}>Less or Equal</option>
                            <option value="between" ${condition.op === 'between' ? 'selected' : ''}>Between</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" 
                               class="form-control form-control-sm scoring-range-field" 
                               placeholder="Value"
                               value="${this.formatValue(condition.value)}"
                               data-branch-index="${branchIndex}"
                               data-range-index="${rangeIndex}"
                               data-range-field="value">
                    </div>
                    <div class="col-md-3">
                        <input type="number" 
                               class="form-control form-control-sm scoring-range-field" 
                               placeholder="Score"
                               value="${range.score || ''}"
                               data-branch-index="${branchIndex}"
                               data-range-index="${rangeIndex}"
                               data-range-field="score"
                               step="any">
                    </div>
                    <div class="col-md-3">
                        <input type="text" 
                               class="form-control form-control-sm scoring-range-field" 
                               placeholder="Category"
                               value="${range.category || ''}"
                               data-branch-index="${branchIndex}"
                               data-range-index="${rangeIndex}"
                               data-range-field="category">
                    </div>
                </div>
            </div>
        `;
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