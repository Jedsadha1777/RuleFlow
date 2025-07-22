// Switch Component
class SwitchComponent {
   constructor(id) {
       this.config = {
           id: id,
           switch: '',
           when: [],
           default: ''
       };
       
       this.operators = [
           { value: '==', label: 'Equal (==)', type: 'any' },
           { value: '!=', label: 'Not equal (!=)', type: 'any' },
           { value: '>', label: 'Greater than (>)', type: 'number' },
           { value: '>=', label: 'Greater or equal (>=)', type: 'number' },
           { value: '<', label: 'Less than (<)', type: 'number' },
           { value: '<=', label: 'Less or equal (<=)', type: 'number' },
           { value: 'between', label: 'Between', type: 'range' },
           { value: 'in', label: 'In list', type: 'array' },
           { value: 'not_in', label: 'Not in list', type: 'array' },
           { value: 'contains', label: 'Contains', type: 'string' },
           { value: 'starts_with', label: 'Starts with', type: 'string' }
       ];
   }
   
   getIcon() {
       return 'S';
   }
   
   getTitle() {
       return this.config.id || 'Switch';
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
                          placeholder="switch_name">
               </div>
           </div>
           
           <div class="row">
               <div class="col-12 mb-3">
                   <label class="form-label">Switch Variable</label>
                   <input type="text" 
                          class="form-control" 
                          name="switch"
                          value="${this.config.switch}" 
                          placeholder="$variable_name">
                   <div class="form-text">Variable to evaluate for conditions</div>
               </div>
           </div>
           
           <div class="row">
               <div class="col-12 mb-3">
                   <label class="form-label">When Conditions</label>
                   <div id="conditions-${index}" class="conditions-container">
                       ${this.getConditionsHTML(index)}
                   </div>
                   <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addCondition(${index})">
                       <i class="bi bi-plus"></i> Add Condition
                   </button>
               </div>
           </div>
           
           <div class="row">
               <div class="col-12 mb-3">
                   <label class="form-label">Default Value</label>
                   <input type="text" 
                          class="form-control" 
                          name="default"
                          value="${this.config.default}" 
                          placeholder="default_result">
                   <div class="form-text">Value to return if no conditions match</div>
               </div>
           </div>
           
           ${this.getValidationHTML()}
       `;
   }
   
   getConditionsHTML(componentIndex) {
       if (!Array.isArray(this.config.when) || this.config.when.length === 0) {
           return '<div class="text-muted fst-italic">No conditions yet. Click "Add Condition" to start.</div>';
       }
       
       return this.config.when.map((condition, conditionIndex) => {
           const operator = condition.if?.op || '>';
           const value = condition.if?.value || '';
           const result = condition.result || '';
           
           return `
               <div class="condition-item mb-2" data-condition-index="${conditionIndex}">
                   <div class="row g-2">
                       <div class="col-md-3">
                           <select class="form-select form-select-sm" 
                                   data-field="operator" 
                                   data-condition-index="${conditionIndex}">
                               ${this.operators.map(op => 
                                   `<option value="${op.value}" ${op.value === operator ? 'selected' : ''}>${op.label}</option>`
                               ).join('')}
                           </select>
                       </div>
                       <div class="col-md-4">
                           <input type="text" 
                                  class="form-control form-control-sm" 
                                  data-field="value"
                                  data-condition-index="${conditionIndex}"
                                  value="${Array.isArray(value) ? value.join(', ') : value}" 
                                  placeholder="${this.getValuePlaceholder(operator)}">
                       </div>
                       <div class="col-md-4">
                           <input type="text" 
                                  class="form-control form-control-sm" 
                                  data-field="result"
                                  data-condition-index="${conditionIndex}"
                                  value="${result}" 
                                  placeholder="result value">
                       </div>
                       <div class="col-md-1">
                           <button type="button" 
                                   class="btn btn-outline-danger btn-sm w-100" 
                                   onclick="removeCondition(${componentIndex}, ${conditionIndex})" 
                                   title="Remove condition">
                               <i class="bi bi-trash"></i>
                           </button>
                       </div>
                   </div>
               </div>
           `;
       }).join('');
   }
   
   getValuePlaceholder(operator) {
       switch (operator) {
           case 'between':
               return '18, 65';
           case 'in':
           case 'not_in':
               return 'value1, value2, value3';
           case 'contains':
           case 'starts_with':
               return 'text to search';
           default:
               return 'comparison value';
       }
   }
   
   getValidationHTML() {
       const errors = this.validate();
       if (errors.length === 0) {
           return '<div class="alert alert-success">✅ Switch configuration is valid</div>';
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
   
   setupEvents(element, componentIndex) {
       // Handle condition field changes
       element.addEventListener('change', (e) => {
           if (e.target.dataset.conditionIndex !== undefined) {
               this.updateConditionFromForm(element);
               this.updateValidationDisplay(element);
           }
       });
       
       element.addEventListener('input', (e) => {
           if (e.target.dataset.conditionIndex !== undefined) {
               this.updateConditionFromForm(element);
           }
       });
       
       // Make this available globally for onclick handlers
       window[`addCondition`] = (index) => this.addCondition(index, element);
       window[`removeCondition`] = (compIndex, condIndex) => this.removeCondition(compIndex, condIndex, element);
   }
   
   addCondition(componentIndex, element) {
       if (!this.config.when) {
           this.config.when = [];
       }
       
       this.config.when.push({
           if: { op: '>', value: '' },
           result: ''
       });
       
       // Re-render conditions
       const container = element.querySelector(`#conditions-${componentIndex}`);
       if (container) {
           container.innerHTML = this.getConditionsHTML(componentIndex);
       }
       
       this.updateValidationDisplay(element);
   }
   
   removeCondition(componentIndex, conditionIndex, element) {
       if (this.config.when && conditionIndex < this.config.when.length) {
           this.config.when.splice(conditionIndex, 1);
           
           // Re-render conditions
           const container = element.querySelector(`#conditions-${componentIndex}`);
           if (container) {
               container.innerHTML = this.getConditionsHTML(componentIndex);
           }
           
           this.updateValidationDisplay(element);
       }
   }
   
   updateFromForm(element) {
       // Update basic fields
       const inputs = element.querySelectorAll('input[name], select[name]');
       inputs.forEach(input => {
           const name = input.name;
           if (name && this.config.hasOwnProperty(name)) {
               this.config[name] = input.value;
           }
       });
       
       // Update conditions
       this.updateConditionFromForm(element);
       this.updateValidationDisplay(element);
   }
   
   updateConditionFromForm(element) {
       const conditionElements = element.querySelectorAll('[data-condition-index]');
       const conditions = {};
       
       conditionElements.forEach(el => {
           const conditionIndex = parseInt(el.dataset.conditionIndex);
           const field = el.dataset.field;
           
           if (!conditions[conditionIndex]) {
               conditions[conditionIndex] = { if: {}, result: '' };
           }
           
           if (field === 'operator') {
               conditions[conditionIndex].if.op = el.value;
           } else if (field === 'value') {
               let value = el.value;
               
               // Handle different value types based on operator
               const operator = conditions[conditionIndex].if.op;
               if (operator === 'between' || operator === 'in' || operator === 'not_in') {
                   // Convert to array
                   value = value.split(',').map(s => s.trim()).filter(s => s);
                   // Convert to numbers if they look like numbers
                   value = value.map(v => {
                       const num = parseFloat(v);
                       return !isNaN(num) ? num : v;
                   });
               } else if (['>', '>=', '<', '<='].includes(operator)) {
                   // Convert to number if possible
                   const num = parseFloat(value);
                   if (!isNaN(num)) {
                       value = num;
                   }
               }
               
               conditions[conditionIndex].if.value = value;
           } else if (field === 'result') {
               conditions[conditionIndex].result = el.value;
           }
       });
       
       // Convert conditions object to array, filtering out incomplete ones
       this.config.when = Object.values(conditions).filter(c => 
           c.if.op && 
           (c.if.value !== '' && c.if.value !== undefined) && 
           c.result !== ''
       );
   }
   
   updateValidationDisplay(element) {
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
       
       if (!this.config.switch) {
           errors.push('Switch variable is required');
       }
       
       if (!this.config.when || this.config.when.length === 0) {
           errors.push('At least one condition is required');
       } else {
           // Validate each condition
           this.config.when.forEach((condition, index) => {
               if (!condition.if || !condition.if.op) {
                   errors.push(`Condition ${index + 1}: Operator is required`);
               }
               
               if (condition.if && (condition.if.value === '' || condition.if.value === undefined)) {
                   errors.push(`Condition ${index + 1}: Comparison value is required`);
               }
               
               if (!condition.result) {
                   errors.push(`Condition ${index + 1}: Result value is required`);
               }
               
               // Validate operator-specific requirements
               if (condition.if && condition.if.op === 'between') {
                   if (!Array.isArray(condition.if.value) || condition.if.value.length !== 2) {
                       errors.push(`Condition ${index + 1}: Between operator requires exactly 2 values`);
                   }
               }
           });
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
               name: 'BMI Category',
               config: {
                   id: 'bmi_category',
                   switch: '$bmi_value',
                   when: [
                       { if: { op: '<', value: 18.5 }, result: 'Underweight' },
                       { if: { op: 'between', value: [18.5, 24.9] }, result: 'Normal' },
                       { if: { op: '>=', value: 25 }, result: 'Overweight' }
                   ],
                   default: 'Unknown'
               }
           },
           {
               name: 'Shipping Cost',
               config: {
                   id: 'shipping_cost',
                   switch: 'weight',
                   when: [
                       { if: { op: '<=', value: 1 }, result: 50 },
                       { if: { op: '<=', value: 5 }, result: 100 },
                       { if: { op: '<=', value: 10 }, result: 150 }
                   ],
                   default: 200
               }
           },
           {
               name: 'Grade Letter',
               config: {
                   id: 'grade_letter',
                   switch: 'score',
                   when: [
                       { if: { op: '>=', value: 90 }, result: 'A' },
                       { if: { op: '>=', value: 80 }, result: 'B' },
                       { if: { op: '>=', value: 70 }, result: 'C' },
                       { if: { op: '>=', value: 60 }, result: 'D' }
                   ],
                   default: 'F'
               }
           }
       ];
   }
   
   // Load sample configuration
   loadSample(sampleName) {
       const samples = SwitchComponent.getSamples();
       const sample = samples.find(s => s.name === sampleName);
       
       if (sample) {
           this.config = JSON.parse(JSON.stringify(sample.config));
           return true;
       }
       
       return false;
   }
}

// Register component
window.RuleFlowComponents = window.RuleFlowComponents || {};
window.RuleFlowComponents.switch = SwitchComponent;