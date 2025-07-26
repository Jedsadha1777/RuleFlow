/**
* Rules Component Class
* Fixed version with proper nested OR/AND support using recursive approach like scoring.js
*/
class RulesComponent {
   constructor(id = 'rules', as = '') {
       this.id = id;
       this.as = as;
       this.rules = [];
   }

   /**
     * Get component icon
     */
    getIcon() {
        return '<i class="bi bi-list-check"></i>';
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
    * Render component HTML
    */
   render(index) {
       let html = `
            <div class="formula-component" data-type="rules" data-index="${index}">
               <div class="card mb-3">
                   <div class="card-header">
                       <div class="d-flex justify-content-between align-items-center">
                           <h6 class="mb-0">
                               <i class="bi bi-list-check text-success me-2"></i>
                               Accumulative Rules Component
                           </h6>
                           <button type="button" class="btn btn-sm btn-outline-danger remove-component-btn" data-index="${index}">
                               <i class="bi bi-trash"></i>
                           </button>
                       </div>
                   </div>
                   <div class="card-body">
                       <div class="row g-3 mb-3">
                           <div class="col-md-6">
                               <label class="form-label">Component ID <span class="text-danger">*</span></label>
                               <input type="text" class="form-control component-id-field" 
                                      placeholder="total_score, risk_points, etc."
                                      value="${this.id}"
                                      data-index="${index}">
                           </div>
                           <div class="col-md-6">
                               <label class="form-label">Store As Variable</label>
                               <input type="text" class="form-control component-as-field" 
                                      placeholder="$total_score"
                                      value="${this.as}"
                                      data-index="${index}">
                           </div>
                       </div>
                   
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
    * Render range with AND/OR support (🔥 ใช้ recursive approach เหมือน scoring.js)
    */
   renderRange(componentIndex, ruleIndex, rangeIndex, range) {
       const condition = range.if || {};
       
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
               
               <!-- ✅ Condition Rendering (Recursive) เหมือน scoring.js -->
               <div class="mb-3">
                   <label class="form-label small mb-2">Range Condition:</label>
                   ${this.renderRangeConditionRecursive(componentIndex, ruleIndex, rangeIndex, condition, [], 0)}
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
    * 🔥 Render range condition recursively - ก็อปจาก scoring.js ทั้งหมด
    */
   renderRangeConditionRecursive(componentIndex, ruleIndex, rangeIndex, condition, path, depth) {
       const pathStr = JSON.stringify(path || []);
       const isNested = condition.and || condition.or;
       const conditionType = condition.and ? 'and' : condition.or ? 'or' : 'simple';
       const logicLabel = conditionType.toUpperCase();
       
       let html = `
           <div class="nested-condition-wrapper" data-depth="${depth}" style="margin-left: ${depth * 20}px;">
               <div class="condition-header d-flex align-items-center gap-2 mb-2">
                   <span class="badge bg-secondary">Level ${depth + 1}</span>
                   <select class="form-select form-select-sm range-condition-type-select" 
                           style="width: auto;"
                           data-rule-index="${ruleIndex}"
                           data-range-index="${rangeIndex}"
                           data-path='${pathStr}'
                          >
                       <option value="simple" ${conditionType === 'simple' ? 'selected' : ''}>Simple</option>
                       <option value="and" ${conditionType === 'and' ? 'selected' : ''}>AND</option>
                       <option value="or" ${conditionType === 'or' ? 'selected' : ''}>OR</option>
                   </select>
       `;

       if (isNested) {
           html += `
                 <button type="button" 
                    class="btn btn-sm btn-outline-success add-rule-range-condition-to-group-btn"
                    data-rule-index="${ruleIndex}"
                    data-range-index="${rangeIndex}"
                    data-path='${pathStr}'
                    data-group-type="${conditionType}">
                <i class="bi bi-plus"></i> Add ${logicLabel}
            </button>
           `;
       }

       html += `</div>`;

       if (isNested) {
           html += `<div class="nested-conditions border rounded p-2 mb-2" style="background: ${depth % 2 === 0 ? '#f8f9fa' : '#ffffff'};">`;
           
           const conditions = condition[conditionType] || [];
           conditions.forEach((subCondition, conditionIndex) => {
               const newPath = [...path, { type: conditionType, index: conditionIndex }];
               
               html += `
                   <div class="nested-condition-item mb-2">
                       <div class="d-flex justify-content-between align-items-start">
                           <div class="flex-grow-1">
               `;
               
               // 🔁 Recursive call - ไม่จำกัดชั้น!
               html += this.renderRangeConditionRecursive(componentIndex, ruleIndex, rangeIndex, subCondition, newPath, depth + 1);
               
               html += `
                           </div>
                           ${conditions.length > 1 ? `
                               <button type="button" 
                                    class="btn btn-sm btn-outline-danger ms-2 remove-rule-range-nested-condition-btn"
                                    data-rule-index="${ruleIndex}"
                                    data-range-index="${rangeIndex}"
                                    data-path='${pathStr}'
                                    data-condition-index="${conditionIndex}"
                                    data-group-type="${conditionType}">
                                <i class="bi bi-x"></i>
                            </button>
                           ` : ''}
                       </div>
               `;

               if (conditionIndex < conditions.length - 1) {
                   html += `<div class="text-center my-1"><span class="badge bg-primary">${logicLabel}</span></div>`;
               }

               html += `</div>`;
           });
           
           html += `</div>`;
       } else {
           // Simple condition
            html += `
                <div class="simple-condition-inline">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select class="form-select form-select-sm rule-range-nested-condition-field" 
                                    data-rule-index="${ruleIndex}"
                                    data-range-index="${rangeIndex}"
                                    data-path='${pathStr}'
                                    data-condition-field="op">
                                ${getOperatorOptions(condition.op)}
                            </select>
                        </div>
                        <div class="col-md-8">
                            <input type="text" 
                                class="form-control form-control-sm rule-range-nested-condition-field" 
                                placeholder="${getValuePlaceholder(condition.op)}"
                                value="${this.formatValue(condition.value)}"
                                data-rule-index="${ruleIndex}"
                                data-range-index="${rangeIndex}"
                                data-path='${pathStr}'
                                data-condition-field="value">
                        </div>
                    </div>
                </div>
            `;
       }

       html += `</div>`;
       return html;
   }

   /**
    * Navigate to nested condition by path (ก็อปจาก scoring.js)
    */
   getRangeNestedCondition(ruleIndex, rangeIndex, path) {
       if (!this.rules[ruleIndex] || !this.rules[ruleIndex].ranges[rangeIndex]) return null;
       
       let condition = this.rules[ruleIndex].ranges[rangeIndex].if;
       
       if (!path || path.length === 0) return condition;
       
       let current = condition;
       for (const step of path) {
           if (step.type === 'and' && current.and && current.and[step.index]) {
               current = current.and[step.index];
           } else if (step.type === 'or' && current.or && current.or[step.index]) {
               current = current.or[step.index];
           } else {
               return null;
           }
       }
       return current;
   }

   /**
    * Update range condition type by path (ก็อปจาก scoring.js)
    */
   updateRangeConditionTypeByPath(ruleIndex, rangeIndex, path, conditionType) {
       const condition = this.getRangeNestedCondition(ruleIndex, rangeIndex, path);
       if (!condition) return;

       if (conditionType === 'simple') {
           Object.keys(condition).forEach(key => delete condition[key]);
           Object.assign(condition, { op: '>=', value: 0 });
       } else if (conditionType === 'and') {
           Object.keys(condition).forEach(key => delete condition[key]);
           Object.assign(condition, {
               and: [
                   { op: '>', var: '', value: '' },
                   { op: '>', var: '', value: '' }
               ]
           });
       } else if (conditionType === 'or') {
           Object.keys(condition).forEach(key => delete condition[key]);
           Object.assign(condition, {
               or: [
                   { op: '>', var: '', value: '' },
                   { op: '>', var: '', value: '' }
               ]
           });
       }
   }

   /**
    * Add condition to range group by path (ก็อปจาก scoring.js)
    */
   addConditionToRangeGroupByPath(ruleIndex, rangeIndex, path, groupType) {
       const condition = this.getRangeNestedCondition(ruleIndex, rangeIndex, path);
       if (!condition) return;

       const newCondition = { op: '>', var: '', value: '' };

       if (groupType === 'and' && condition.and) {
           condition.and.push(newCondition);
       } else if (groupType === 'or' && condition.or) {
           condition.or.push(newCondition);
       }
   }

   /**
    * Remove range condition by path (ก็อปจาก scoring.js)
    */
   removeRangeConditionByPath(ruleIndex, rangeIndex, path, conditionIndex, groupType) {
       const condition = this.getRangeNestedCondition(ruleIndex, rangeIndex, path);
       if (!condition) return;

       if (groupType === 'and' && condition.and) {
           condition.and.splice(conditionIndex, 1);
           if (condition.and.length === 0) {
               condition.and.push({ op: '>', var: '', value: '' });
           }
       } else if (groupType === 'or' && condition.or) {
           condition.or.splice(conditionIndex, 1);
           if (condition.or.length === 0) {
               condition.or.push({ op: '>', var: '', value: '' });
           }
       }
   }

   /**
    * Update range condition by path (ก็อปจาก scoring.js)
    */
   updateRangeConditionByPath(ruleIndex, rangeIndex, path, field, value) {
       const condition = this.getRangeNestedCondition(ruleIndex, rangeIndex, path);
       if (!condition) return;

       if (field === 'op') {
           condition.op = value;
       } else if (field === 'var') {
           condition.var = value;
       } else if (field === 'value') {
           condition.value = this.parseValue(value);
       }
   }

   /**
     * Update nested condition type by path
     */
    updateNestedConditionTypeByPath(ruleIndex, rangeIndex, path, conditionType) {
        const condition = this.getRangeNestedCondition(ruleIndex, rangeIndex, path);
        if (!condition) return;

        if (conditionType === 'simple') {
            // Convert to simple
            Object.keys(condition).forEach(key => delete condition[key]);
            Object.assign(condition, { op: '>=', value: 0 });
        } else if (conditionType === 'and') {
            // Convert to AND
            Object.keys(condition).forEach(key => delete condition[key]);
            Object.assign(condition, {
                and: [
                    { op: '>', var: '', value: '' },
                    { op: '>', var: '', value: '' }
                ]
            });
        } else if (conditionType === 'or') {
            // Convert to OR
            Object.keys(condition).forEach(key => delete condition[key]);
            Object.assign(condition, {
                or: [
                    { op: '>', var: '', value: '' },
                    { op: '>', var: '', value: '' }
                ]
            });
        }
    }

   /**
    * Add rule
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
       if (!this.rules[ruleIndex]) return;
       this.rules[ruleIndex].var = value;
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
    * Update range field (legacy method - ใช้สำหรับ simple condition)
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
       } catch (e) {
           console.warn('Invalid set_vars format:', varsString);
       }
   }

   /**
    * Parse value helper
    */
   parseValue(value) {
       if (value === 'true') return true;
       if (value === 'false') return false;
       if (value === 'null') return null;
       if (value === '') return '';
       
       const num = Number(value);
       if (!isNaN(num) && isFinite(num)) return num;
       
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
    * Format set_vars for display
    */
   formatSetVars(setVars) {
       if (!setVars || typeof setVars !== 'object') return '';
       
       return Object.entries(setVars)
           .map(([key, value]) => `${key} = ${JSON.stringify(value)}`)
           .join(', ');
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