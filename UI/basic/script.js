/**
 * RuleFlow Basic UI - jQuery Version
 * Updated from vanilla JS to use jQuery
 */

$(document).ready(function () {
    // Initialize RuleFlow
    const ruleFlow = new RuleFlow();
    let components = [];
    let debugEnabled = true;



    /**
     * Initialize UI components
     */
    function initializeUI() {
        debug('RuleFlow Basic UI initialized with jQuery', 'success');
    }

    /**
     * Bind all events using jQuery
     */
    function bindEvents() {
        // Component dropdown events - use event delegation
        $(document).on('click', '.dropdown-item[data-component]', function (e) {
            e.preventDefault();
            const componentType = $(this).data('component');
            addComponent(componentType);
        });

        // Component control events - use event delegation
        $(document).on('click', '.toggle-btn', function () {
            const index = $(this).data('index');
            toggleComponent(index);
        });

        $(document).on('click', '.copy-btn', function () {
            const index = $(this).data('index');
            copyComponent(index);
        });

        $(document).on('click', '.delete-btn', function () {
            const index = $(this).data('index');
            deleteComponent(index);
        });

        // Component field change events
        $(document).on('change', '.component-form input, .component-form select', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const field = $this.attr('data-field') || getFieldFromElement($this);
            const value = $this.val();

            if (index >= 0 && field) {
                updateComponentField(index, field, value);
            }
        });


        // Scoring component management
        $(document).on('click', '.add-scoring-branch-btn', function () {
            const $card = $(this).closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            addScoringBranch(index);
        });

        $(document).on('click', '.remove-scoring-branch-btn', function () {
            const $card = $(this).closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($(this).attr('data-branch-index'));
            removeScoringBranch(index, branchIndex);
        });

        $(document).on('change', '.scoring-branch-field', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const field = $this.attr('data-branch-field');
            const value = $this.val();
            updateScoringBranch(index, branchIndex, field, value);
        });

        $(document).on('click', '.add-range-to-branch-btn', function () {
            const $card = $(this).closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($(this).attr('data-branch-index'));
            addRangeToBranch(index, branchIndex);
        });

        $(document).on('click', '.remove-range-from-branch-btn', function () {
            const $card = $(this).closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($(this).attr('data-branch-index'));
            const rangeIndex = parseInt($(this).attr('data-range-index'));
            removeRangeFromBranch(index, branchIndex, rangeIndex);
        });

        $(document).on('change', '.scoring-range-field', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));
            const field = $this.attr('data-range-field');
            const value = $this.val();
            updateRangeInBranch(index, branchIndex, rangeIndex, field, value);
        });

        $(document).on('click', '.add-custom-field-btn', function () {
            const $this = $(this);
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));

            const fieldName = prompt('Enter field name (e.g., level, tier, strategy):');
            if (fieldName && fieldName.trim()) {
                const $card = $this.closest('.card[data-component-index]');
                const index = parseInt($card.attr('data-component-index'));
                addCustomFieldToRange(index, branchIndex, rangeIndex, fieldName.trim());
            }
        });

        $(document).on('click', '.remove-custom-field-btn', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));
            const fieldName = $this.attr('data-field-name');
            removeCustomFieldFromRange(index, branchIndex, rangeIndex, fieldName);
        });

        $(document).on('change', '.set-vars-field', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));
            const value = $this.val();
            updateSetVars(index, branchIndex, rangeIndex, value);
        });


        // Switch case management
        $(document).on('click', '.add-case-btn', function () {
            const $card = $(this).closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            addSwitchCase(index);
        });

        $(document).on('click', '.add-nested-case-btn', function () {
            const $card = $(this).closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            addNestedSwitchCase(index);
        });

        $(document).on('click', '.remove-case-btn', function () {
            const $card = $(this).closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const caseIndex = parseInt($(this).attr('data-case-index'));
            removeSwitchCase(index, caseIndex);
        });

        $(document).on('change', '.case-field', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const caseIndex = parseInt($this.attr('data-case-index'));
            const field = $this.attr('data-case-field');
            const value = $this.val();
            updateSwitchCase(index, caseIndex, field, value);
        });

        // Nested condition management
        $(document).on('change', '.condition-type-select', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const caseIndex = parseInt($this.attr('data-case-index'));
            const path = JSON.parse($this.attr('data-path') || '[]');
            const value = $this.val();

            if (components[index] && components[index].instance.updateNestedConditionByPath) {
                components[index].instance.updateNestedConditionByPath(caseIndex, path, 'type', value);
                updateView();
                updateJSON();
            }
        });

        $(document).on('click', '.add-condition-to-nested-group-btn', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const caseIndex = parseInt($this.attr('data-case-index'));
            const path = JSON.parse($this.attr('data-path') || '[]');
            const groupType = $this.attr('data-group-type');

            if (components[index] && components[index].instance.addConditionToGroupByPath) {
                components[index].instance.addConditionToGroupByPath(caseIndex, path, groupType);
                updateView();
                updateJSON();
            }
        });

        $(document).on('click', '.remove-nested-condition-btn', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const caseIndex = parseInt($this.attr('data-case-index'));
            const path = JSON.parse($this.attr('data-path') || '[]');
            const conditionIndex = parseInt($this.attr('data-condition-index'));
            const groupType = $this.attr('data-group-type');

            if (components[index] && components[index].instance.removeConditionFromGroupByPath) {
                components[index].instance.removeConditionFromGroupByPath(caseIndex, path, conditionIndex, groupType);
                updateView();
                updateJSON();
            }
        });

        $(document).on('change', '.nested-condition-field', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const caseIndex = parseInt($this.attr('data-case-index'));
            const path = JSON.parse($this.attr('data-path') || '[]');
            const field = $this.attr('data-condition-field');
            const value = $this.val();

            if (components[index] && components[index].instance.updateNestedConditionByPath) {
                components[index].instance.updateNestedConditionByPath(caseIndex, path, field, value);
                updateJSON();
                updateInputVariables();
            }
        });

        $(document).on('click', '.add-condition-to-group-btn', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const caseIndex = parseInt($this.attr('data-case-index'));
            const groupType = $this.attr('data-group-type');
            addConditionToGroup(index, caseIndex, groupType);
        });

        $(document).on('click', '.remove-condition-from-group-btn', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const caseIndex = parseInt($this.attr('data-case-index'));
            const conditionIndex = parseInt($this.attr('data-condition-index'));
            const groupType = $this.attr('data-group-type');
            removeConditionFromGroup(index, caseIndex, conditionIndex, groupType);
        });

        // Conditions management


        //Dynamic place update for operators
        $(document).on('change', 'select[data-condition-field="op"], select[data-range-field="op"], select[data-condition-field="condition_op"], .scoring-branch-field[data-branch-field="op"]', function () {
            const $this = $(this);
            const selectedOp = $this.val();

            const $valueInput = $this.closest('.row, .condition-row, .simple-condition-inline, .primary-condition')
                .find('input[data-condition-field="value"], input[data-range-field="value"], input[data-condition-field="condition_value"], input[data-branch-field="value"]');

            if ($valueInput.length) {
                $valueInput.attr('placeholder', getValuePlaceholder(selectedOp));
            }
        });


       


        $(document).on('click', '.remove-range-from-branch-btn', function () {
            const $card = $(this).closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($(this).attr('data-branch-index'));
            const rangeIndex = parseInt($(this).attr('data-range-index'));
            removeRangeFromBranch(index, branchIndex, rangeIndex);
        });

        $(document).on('change', '.scoring-range-field', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));
            const field = $this.attr('data-range-field');
            const value = $this.val();
            updateRangeInBranch(index, branchIndex, rangeIndex, field, value);
        });

        $(document).on('change', '.set-vars-field', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));
            const value = $this.val();
            updateSetVars(index, branchIndex, rangeIndex, value);
        });

        $(document).on('click', '.remove-custom-field-btn', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));
            const fieldName = $this.attr('data-field-name');
            removeCustomFieldFromRange(index, branchIndex, rangeIndex, fieldName);
        });

        // เพิ่มใน bindEvents function
        $(document).on('change', '.condition-type-select', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const value = $this.val();

            if (components[index] && components[index].instance.updateScoringBranch) {
                components[index].instance.updateScoringBranch(branchIndex, 'condition_type', value);
                updateView();
                updateJSON();
            }
        });

        $(document).on('change', '.nested-branch-condition-field', function () {
            const $this = $(this);
            const index = parseInt($this.closest('.card').attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const conditionIndex = parseInt($this.attr('data-condition-index'));
            const field = $this.attr('data-condition-field');
            const value = $this.val();
            
            if (components[index] && components[index].instance.updateNestedBranchCondition) {
                components[index].instance.updateNestedBranchCondition(branchIndex, conditionIndex, field, value);
                updateJSON();
            }
        });

        $(document).on('click', '.add-condition-to-branch-group-btn', function () {
            const index = parseInt($(this).closest('.card').attr('data-component-index'));
            const branchIndex = parseInt($(this).attr('data-branch-index'));
            const groupType = $(this).attr('data-group-type');
            
            if (components[index] && components[index].instance.addConditionToBranchGroup) {
                components[index].instance.addConditionToBranchGroup(branchIndex, groupType);
                updateView();
                updateJSON();
            }
        });



        $(document).on('change', '.deep-nested-condition-field', function () {
            const $this = $(this);
            const index = parseInt($this.closest('.card').attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));
            const conditionIndex = parseInt($this.attr('data-condition-index'));
            const deepIndex = parseInt($this.attr('data-deep-index'));
            const field = $this.attr('data-condition-field');
            const value = $this.val();
            
            if (components[index] && components[index].instance.updateDeepNestedRangeCondition) {
                components[index].instance.updateDeepNestedRangeCondition(branchIndex, rangeIndex, conditionIndex, deepIndex, field, value);
                updateJSON();
            }
        });

        $(document).on('click', '.add-deep-nested-condition-btn', function () {
            const $this = $(this);
            const index = parseInt($this.closest('.card').attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));
            const conditionIndex = parseInt($this.attr('data-condition-index'));
            const deepIndex = parseInt($this.attr('data-deep-index'));
            const nestType = $this.attr('data-nest-type');
            
            if (components[index] && components[index].instance.addDeepNestedCondition) {
                components[index].instance.addDeepNestedCondition(branchIndex, rangeIndex, conditionIndex, deepIndex, nestType);
                updateView();
                updateJSON();
            }
        });

        $(document).on('click', '.remove-condition-from-branch-group-btn', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const conditionIndex = parseInt($this.attr('data-condition-index'));
            const groupType = $this.attr('data-group-type');
            removeConditionFromBranchGroup(index, branchIndex, conditionIndex, groupType);
        });

       

        // Recursive Range Condition Type Selector
        $(document).on('change', '.range-condition-type-select', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));
            const path = JSON.parse($this.attr('data-path') || '[]');
            const value = $this.val();

            if (components[index] && components[index].instance.updateRangeConditionTypeByPath) {
                components[index].instance.updateRangeConditionTypeByPath(branchIndex, rangeIndex, path, value);
                updateView();
                updateJSON();
            }
        });

        // Add Condition to Range Group (Recursive)
        $(document).on('click', '.add-range-condition-to-group-btn', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));
            const path = JSON.parse($this.attr('data-path') || '[]');
            const groupType = $this.attr('data-group-type');

            if (components[index] && components[index].instance.addConditionToRangeGroupByPath) {
                components[index].instance.addConditionToRangeGroupByPath(branchIndex, rangeIndex, path, groupType);
                updateView();
                updateJSON();
            }
        });

        // Remove Range Nested Condition (Recursive)
        $(document).on('click', '.remove-range-nested-condition-btn', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));
            const path = JSON.parse($this.attr('data-path') || '[]');
            const conditionIndex = parseInt($this.attr('data-condition-index'));
            const groupType = $this.attr('data-group-type');

            if (components[index] && components[index].instance.removeRangeConditionByPath) {
                components[index].instance.removeRangeConditionByPath(branchIndex, rangeIndex, path, conditionIndex, groupType);
                updateView();
                updateJSON();
            }
        });

        // Range Nested Condition Field Changes (Recursive)
        $(document).on('change', '.range-nested-condition-field', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));
            const path = JSON.parse($this.attr('data-path') || '[]');
            const field = $this.attr('data-condition-field');
            const value = $this.val();

            if (components[index] && components[index].instance.updateRangeConditionByPath) {
                components[index].instance.updateRangeConditionByPath(branchIndex, rangeIndex, path, field, value);
                updateJSON();
                updateInputVariables();
            }
        });



        //Rules component management start
        $(document).on('click', '.add-rule-btn', function () {
            const $card = $(this).closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            addRule(index);
        });

        $(document).on('click', '.remove-rule-btn', function () {
            const $card = $(this).closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const ruleIndex = parseInt($(this).attr('data-rule-index'));
            removeRule(index, ruleIndex);
        });

        $(document).on('change', '.rule-var-field', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const ruleIndex = parseInt($this.attr('data-rule-index'));
            const value = $this.val();
            updateRuleVar(index, ruleIndex, value);
        });

        $(document).on('click', '.add-range-to-rule-btn', function () {
            const $card = $(this).closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const ruleIndex = parseInt($(this).attr('data-rule-index'));
            addRangeToRule(index, ruleIndex);
        });

        $(document).on('click', '.remove-range-from-rule-btn', function () {
            const $card = $(this).closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const ruleIndex = parseInt($(this).attr('data-rule-index'));
            const rangeIndex = parseInt($(this).attr('data-range-index'));
            removeRangeFromRule(index, ruleIndex, rangeIndex);
        });

        $(document).on('change', '.rule-range-field', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const ruleIndex = parseInt($this.attr('data-rule-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));
            const field = $this.attr('data-range-field');
            const value = $this.val();
            updateRuleRangeField(index, ruleIndex, rangeIndex, field, value);
        });

        $(document).on('change', '.rule-set-vars-field', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const ruleIndex = parseInt($this.attr('data-rule-index'));
            const rangeIndex = parseInt($this.attr('data-range-index'));
            const value = $this.val();
            updateRuleSetVars(index, ruleIndex, rangeIndex, value);
        });
        //Rules component management end 

        $(document).on('change', '.branch-condition-type-select', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const path = JSON.parse($this.attr('data-path') || '[]');
            const value = $this.val();

            if (components[index] && components[index].instance.updateBranchConditionTypeByPath) {
                components[index].instance.updateBranchConditionTypeByPath(branchIndex, path, value);
                updateView();
                updateJSON();
            }
        });

        // Add Branch Condition to Group
        $(document).on('click', '.add-branch-condition-to-group-btn', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const path = JSON.parse($this.attr('data-path') || '[]');
            const groupType = $this.attr('data-group-type');

            if (components[index] && components[index].instance.addConditionToBranchGroupByPath) {
                components[index].instance.addConditionToBranchGroupByPath(branchIndex, path, groupType);
                updateView();
                updateJSON();
            }
        });

        // Remove Branch Nested Condition
        $(document).on('click', '.remove-branch-nested-condition-btn', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const path = JSON.parse($this.attr('data-path') || '[]');
            const conditionIndex = parseInt($this.attr('data-condition-index'));
            const groupType = $this.attr('data-group-type');

            if (components[index] && components[index].instance.removeBranchConditionByPath) {
                components[index].instance.removeBranchConditionByPath(branchIndex, path, conditionIndex, groupType);
                updateView();
                updateJSON();
            }
        });

        // Branch Nested Condition Field Changes
        $(document).on('change', '.branch-nested-condition-field', function () {
            const $this = $(this);
            const $card = $this.closest('.card[data-component-index]');
            const index = parseInt($card.attr('data-component-index'));
            const branchIndex = parseInt($this.attr('data-branch-index'));
            const path = JSON.parse($this.attr('data-path') || '[]');
            const field = $this.attr('data-condition-field');
            const value = $this.val();

            if (components[index] && components[index].instance.updateBranchConditionByPath) {
                components[index].instance.updateBranchConditionByPath(branchIndex, path, field, value);
                updateJSON();
                updateInputVariables();
            }
        });

       
        // Button events
        $('#validateBtn').on('click', validateConfiguration);
        $('#executeBtn').on('click', executeRules);
        $('#generateCodeBtn').on('click', generateCode);
        $('#copyCodeBtn').on('click', copyCode);
        $('#copyJsonBtn').on('click', copyJSON);
        $('#addCustomBtn').on('click', addCustomVariable);
        $('#testConfigBtn').on('click', testConfig);

        // Auto-execute on input changes (debounced)
        $(document).on('input', '.input-variable input', debounce(autoExecute, 300));

        debug('Events bound with jQuery', 'info');

        // Handle modal confirm button
        $('#confirmAddVariable').on('click', function() {
            const name = $('#variableNameInput').val().trim();
            const initialValue = $('#variableValueInput').val().trim();
            
            if (!name) {
                showError('Please enter a variable name');
                return;
            }
            
            // Validate variable name
            const cleanName = name.replace(/[^a-zA-Z0-9_]/g, '');
            if (cleanName !== name) {
                showError('Variable name can only contain letters, numbers, and underscores');
                return;
            }
            
            // Check if already exists
            if ($(`#input_${cleanName}`).length > 0) {
                showError('Variable already exists');
                return;
            }
            
            // Create the input
            createCustomVariableInput(cleanName, initialValue);
            
            // Close modal
            bootstrap.Modal.getInstance($('#addVariableModal')[0]).hide();
            
            debug(`Added custom variable: ${cleanName}`, 'info');
            showSuccess(`Custom variable "${cleanName}" added`);
        });
        
        // Handle Enter key in modal
        $('#variableNameInput, #variableValueInput').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                $('#confirmAddVariable').click();
            }
        });
        
        // Handle modal close - clear validation states
        $('#addVariableModal').on('hidden.bs.modal', function() {
            $('#variableNameInput, #variableValueInput').removeClass('is-invalid is-valid');
        });

        $('#addCustomBtn').on('click', addCustomVariable);


    }

    function setupRealTimeInputHandling() {
        $(document).on('input', '.form-control', function() {
            const $this = $(this);
            const $component = $this.closest('[data-component-index]');
            const index = parseInt($component.data('component-index'));
            const field = getFieldFromElement($this);
            const value = $this.val();
            
            if (field && !isNaN(index)) {
                // Visual feedback - add changing class
                $this.addClass('changing');
                
                // Debounced update
                clearTimeout($this.data('timeout'));
                $this.data('timeout', setTimeout(() => {
                    updateComponentField(index, field, value);
                    $this.removeClass('changing');
                }, 300));
                
                // Immediate title update for ID field
                if (field === 'id') {
                    updateComponentTitle(index, value || `${components[index].type}_component`);
                }
            }
        });


    }

    function createCustomVariableInput(cleanName, initialValue = '') {
        const $inputDiv = $(`
            <div class="input-variable mb-2" data-input="${cleanName}">
                <label class="form-label small">
                    ${cleanName} 
                    <span class="badge bg-secondary ms-1">custom</span>
                </label>
                <div class="input-group input-group-sm">
                    <input type="number" 
                        class="form-control" 
                        id="input_${cleanName}" 
                        placeholder="Enter ${cleanName}"
                        value="${initialValue}"
                        step="any">
                    <button class="btn btn-outline-danger btn-remove-var" 
                            data-var="${cleanName}" 
                            type="button"
                            title="Remove variable">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `);

        $('#inputVariables').append($inputDiv);

        // Bind remove event
        $inputDiv.find('.btn-remove-var').on('click', function () {
            const varName = $(this).data('var');
            if (confirm(`Remove variable "${varName}"?`)) {
                $(`.input-variable[data-input="${varName}"]`).remove();
                debug(`Removed custom variable: ${varName}`, 'info');
                showSuccess(`Variable "${varName}" removed`);
            }
        });
        
        // Auto-focus on the new input
        $inputDiv.find('input').focus();
    }


    /**
     * Get field name from element
     */
    function getFieldFromElement($element) {
        const placeholder = $element.attr('placeholder');
        const id = $element.attr('id');

        // Try to derive field name from context
        if (placeholder) {
            if (placeholder.includes('formula') || placeholder.includes('Formula')) return 'formula';
            if (placeholder.includes('variable') && placeholder.includes('Store')) return 'as';
            if (placeholder.includes('Switch')) return 'switch';
            if (placeholder.includes('Default')) return 'default';
            if (placeholder.includes('Input')) return 'inputs';
        }

        // Try from parent label
        const $label = $element.closest('.mb-3, .col-md-6').find('label');
        if ($label.length) {
            const labelText = $label.text().toLowerCase();
            if (labelText.includes('formula')) return 'formula';
            if (labelText.includes('store') || labelText.includes('variable')) return 'as';
            if (labelText.includes('switch')) return 'switch';
            if (labelText.includes('default')) return 'default';
            if (labelText.includes('input')) return 'inputs';
            if (labelText.includes('id')) return 'id';
        }

        return null;
    }

    /**
     * Add new component
     */
    function getValuePlaceholder(operator) {
        switch(operator) {
            case 'between':
                return 'e.g., [10, 20]';
            case 'in':
            case 'not_in':
                return 'e.g., ["A", "B", "C"]';
            case '==':
            case '!=':
                return 'comparison value';
            case '>':
            case '>=':
            case '<':
            case '<=':
                return 'numeric value';
            default:
                return 'value';
        }
    }

    function addComponent(type) {
        let instance;

        switch (type) {
            case 'formula':
                instance = new FormulaComponent();
                break;
            case 'switch':
                instance = new SwitchComponent();
                break;

            case 'scoring':  
                instance = new ScoringComponent();
                break;

            case 'rules':
                instance = new RulesComponent();
                break;
            default:
                showToast('Unknown component type', 'error');
                return;
        }

        const component = {
            type: type,
            instance: instance,
            open: true
        };

        components.push(component);
        updateView();
        updateJSON();

        debug(`Added ${type} component`, 'info');
        showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} component added`, 'success');
    }

    /**
     * Toggle component visibility
     */
    window.toggleComponent = function (index) {
        if (components[index]) {
            components[index].open = !components[index].open;
            updateView();
        }
    };

    /**
     * Copy component
     */
    window.copyComponent = function (index) {
        if (components[index]) {
            const originalComponent = components[index];
            const newInstance = Object.create(Object.getPrototypeOf(originalComponent.instance));
            Object.assign(newInstance, originalComponent.instance);

            // Update ID to make it unique
            const originalId = newInstance.getId();
            const newId = originalId + '_copy';
            newInstance.setId(newId);

            const newComponent = {
                type: originalComponent.type,
                instance: newInstance,
                open: true
            };

            components.push(newComponent);
            updateView();
            updateJSON();

            debug(`Copied component ${originalId} as ${newId}`, 'info');
            showToast('Component copied', 'success');
        }
    };

    /**
     * Delete component
     */
    window.deleteComponent = function (index) {
        if (components[index]) {
            const componentId = components[index].instance.getId();
            components.splice(index, 1);
            updateView();
            updateJSON();
            updateInputVariables();

            debug(`Deleted component ${componentId}`, 'info');
            showToast('Component deleted', 'success');
        }
    };

    /**
     * Update component field with real-time UI update
     */
    window.updateComponentField = function (index, field, value) {
        if (components[index] && components[index].instance.updateField) {
            const oldTitle = components[index].instance.getTitle();
            
            // Update the component data
            components[index].instance.updateField(field, value);
            
            // Check if title changed
            const newTitle = components[index].instance.getTitle();
            if (field === 'id' && oldTitle !== newTitle) {
                updateComponentTitle(index, newTitle);
            }
            
            updateJSON();
            updateInputVariables();

            debug(`Updated ${field} for component ${index}`, 'info');
        }
    };

    /**
     * Update เฉพาะ title ของ component โดยไม่ต้อง re-render ทั้งหมด
     */
    function updateComponentTitle(index, newTitle) {
        const $component = $(`[data-component-index="${index}"]`);
        const $titleElement = $component.find('.component-title h6');
        
        if ($titleElement.length) {
            $titleElement.text(newTitle);
            debug(`Updated title for component ${index} to: ${newTitle}`, 'info');
        }
    }


    /**
     * Update view
     */
    function updateView() {
        const $container = $('#componentContainer');
        const $emptyState = $('#emptyState');

        // Clear existing components (except empty state)
        $container.children().not('#emptyState').remove();

        if (components.length === 0) {
            $emptyState.show();
            return;
        }

        $emptyState.hide();

        components.forEach((component, index) => {
            const $element = createComponentElement(component, index);
            $container.append($element);
        });
    }

    /**
     * Create component element using jQuery
     */
    function createComponentElement(component, index) {
        const iconClass = component.type;
        const title = component.instance.getTitle();
        const chevronIcon = component.open ? 'bi-chevron-down' : 'bi-chevron-right';

        let html = `
            <div class="card component-card mb-3" data-component-index="${index}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-sm btn-outline-secondary me-2 toggle-btn" 
                                data-index="${index}">
                            <i class="bi ${chevronIcon}"></i>
                        </button>
                        <div class="component-icon me-2">
                            ${component.instance.getIcon ? component.instance.getIcon() : '<i class="bi bi-gear"></i>'}
                        </div>
                        <div class="component-title">
                            <h6 class="mb-0">${title}</h6>
                            <small class="text-muted">${component.type}</small>
                        </div>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary copy-btn" data-index="${index}">
                            <i class="bi bi-copy"></i>
                        </button>
                        <button class="btn btn-outline-danger delete-btn" data-index="${index}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>`;

        if (component.open && component.instance.render) {
            try {
                html += `<div class="card-body">${component.instance.render(index)}</div>`;
            } catch (error) {
                console.error('Error rendering component:', error);
                html += `
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Error rendering component: ${error.message}
                        </div>
                    </div>`;
            }
        }

        html += `</div>`;
        return $(html);
    }


    /**
     * Update JSON output
     */
    function updateJSON() {
        const config = {
            formulas: components.map(comp => comp.instance.toJSON())
        };

        const jsonString = JSON.stringify(config, null, 2);
        $('#jsonOutput').text(jsonString);

        debug('JSON updated', 'info');
    }

    /**
     * Update input variables
     */
    function updateInputVariables() {
        const inputs = extractInputVariables();
        renderInputVariables(inputs);
    }

    /**
     * Extract input variables from components
     */
    function extractInputVariables() {
        const inputs = new Set();
        const calculatedValues = new Set();

        // Collect calculated values first
        components.forEach(comp => {
            const json = comp.instance.toJSON();
            calculatedValues.add(json.id);
            if (json.as) {
                const varName = json.as.startsWith('$') ? json.as.substring(1) : json.as;
                calculatedValues.add(varName);
            }
        });

        // Extract variables from formulas
        components.forEach(comp => {
            const json = comp.instance.toJSON();

            if (json.formula) {
                const variables = json.formula.match(/\$(\w+)/g);
                if (variables) {
                    variables.forEach(variable => {
                        const varName = variable.substring(1);
                        if (!calculatedValues.has(varName)) {
                            inputs.add(varName);
                        }
                    });
                }
            }

            // Extract from switch conditions
            if (json.switch) {
                const switchVar = json.switch.startsWith('$') ? json.switch.substring(1) : json.switch;
                if (!calculatedValues.has(switchVar)) {
                    inputs.add(switchVar);
                }
            }

            // Extract from conditions
            if (json.conditions) {
                extractVariablesFromConditions(json.conditions, inputs, calculatedValues);
            }

            if (json.when) {
                extractVariablesFromConditions(json.when, inputs, calculatedValues);
            }
        });

        return Array.from(inputs);
    }

    /**
     * Extract variables from nested conditions
     */
    function extractVariablesFromConditions(conditions, inputs, calculatedValues) {
        if (!Array.isArray(conditions)) return;

        conditions.forEach(condition => {
            if (condition.if) {
                extractVariablesFromCondition(condition.if, inputs, calculatedValues);
            }
            if (condition.field) {
                const fieldName = condition.field.startsWith('$') ? condition.field.substring(1) : condition.field;
                if (!calculatedValues.has(fieldName)) {
                    inputs.add(fieldName);
                }
            }
            if (condition.var) {
                const varName = condition.var.startsWith('$') ? condition.var.substring(1) : condition.var;
                if (!calculatedValues.has(varName)) {
                    inputs.add(varName);
                }
            }
        });
    }

    /**
     * Extract variables from single condition
     */
    function extractVariablesFromCondition(condition, inputs, calculatedValues) {
        if (condition.and) {
            condition.and.forEach(sub => extractVariablesFromCondition(sub, inputs, calculatedValues));
        }
        if (condition.or) {
            condition.or.forEach(sub => extractVariablesFromCondition(sub, inputs, calculatedValues));
        }
        if (condition.field) {
            const fieldName = condition.field.startsWith('$') ? condition.field.substring(1) : condition.field;
            if (!calculatedValues.has(fieldName)) {
                inputs.add(fieldName);
            }
        }
        if (condition.var) {
            const varName = condition.var.startsWith('$') ? condition.var.substring(1) : condition.var;
            if (!calculatedValues.has(varName)) {
                inputs.add(varName);
            }
        }
    }

    /**
     * Render input variables using jQuery
     */
    function renderInputVariables(inputs) {
        const $container = $('#inputVariables');

        if (inputs.length === 0) {
            $container.html('<p class="text-muted mb-0">Add components to see input variables</p>');
            return;
        }

        $container.empty();

        inputs.forEach(input => {
            const $inputDiv = $(`
               <div class="input-variable mb-2" data-input="${input}">
                   <label class="form-label small">${input}</label>
                   <input type="number" 
                          class="form-control form-control-sm" 
                          id="input_${input}" 
                          placeholder="Enter ${input}"
                          step="any">
               </div>
           `);

            $container.append($inputDiv);
        });

        debug(`Rendered ${inputs.length} input variables`, 'info');
    }

    /**
     * Get current inputs from form
     */
    function getCurrentInputs() {
        const inputs = {};

        $('.input-variable input').each(function () {
            const $input = $(this);
            const value = $input.val();
            const name = $input.attr('id').replace('input_', '');

            if (value !== '') {
                inputs[name] = parseFloat(value) || value;
            }
        });

        return inputs;
    }

    /**
     * Get current configuration
     */
    function getCurrentConfig() {
        try {
            const config = {
                formulas: components.map(comp => comp.instance.toJSON())
            };
            return config;
        } catch (error) {
            debug(`Error getting config: ${error.message}`, 'error');
            return null;
        }
    }

    /**
     * Validate configuration
     */
    function validateConfiguration() {
        const config = getCurrentConfig();
        if (!config) {
            showError('Please add components to validate');
            return;
        }

        const validation = ruleFlow.validateConfig(config);

        if (validation.valid) {
            showSuccess('Configuration is valid!');
            if (validation.warnings.length > 0) {
                showWarning(`Warnings: ${validation.warnings.join(', ')}`);
            }
        } else {
            showError(`Validation failed: ${validation.errors.join(', ')}`);
        }

        debug(`Validation result: ${validation.valid}`, validation.valid ? 'success' : 'error');
    }

    /**
     * Execute rules
     */
    async function executeRules() {
        const config = getCurrentConfig();
        const inputs = getCurrentInputs();

        if (!config) {
            showError('Please add components to execute');
            return;
        }

        if (Object.keys(inputs).length === 0) {
            showWarning('No input values provided');
        }

        try {
            const result = await ruleFlow.evaluate(config, inputs);

            if (result.success) {
                showResults(result.results, result.executionTime);
                debug(`Execution successful in ${result.executionTime}ms`, 'success');
            } else {
                showError(`Execution failed: ${result.error}`);
                debug(`Execution failed: ${result.error}`, 'error');
            }
        } catch (error) {
            showError(`Execution error: ${error.message}`);
            debug(`Execution error: ${error.message}`, 'error');
        }
    }

    /**
     * Auto-execute rules (debounced)
     */
    async function autoExecute() {
        const config = getCurrentConfig();
        const inputs = getCurrentInputs();

        if (!config || Object.keys(inputs).length === 0) {
            return;
        }

        try {
            const result = await ruleFlow.evaluate(config, inputs);
            if (result.success) {
                showResults(result.results, result.executionTime, true);
            }
        } catch (error) {
            debug(`Auto-execution failed: ${error.message}`, 'warning');
        }
    }

    /**
     * Generate JavaScript code
     */
    function generateCode() {
        const config = getCurrentConfig();
        if (!config) {
            showError('Please add components to generate code');
            return;
        }

        try {
            const generatedCode = ruleFlow.generateCode(config);
            $('#generatedCode').val(generatedCode);
            showSuccess('Code generated successfully!');
            debug('Code generation successful', 'success');
        } catch (error) {
            showError(`Code generation failed: ${error.message}`);
            debug(`Code generation failed: ${error.message}`, 'error');
        }
    }

    /**
     * Copy generated code to clipboard
     */
    function copyCode() {
        const code = $('#generatedCode').val();
        if (!code) {
            showWarning('No code to copy. Generate code first.');
            return;
        }

        copyToClipboard(code, 'Code copied to clipboard!');
    }

    /**
     * Copy JSON to clipboard
     */
    function copyJSON() {
        const jsonText = $('#jsonOutput').text();
        copyToClipboard(jsonText, 'JSON copied to clipboard!');
    }

    /**
     * Add custom variable
     */
    function addCustomVariable() {
        // Clear previous values
        $('#variableNameInput').val('');
        $('#variableValueInput').val('');
        
        // Show modal
        const modal = new bootstrap.Modal($('#addVariableModal')[0]);
        modal.show();
        
        // Focus on input when modal is shown
        $('#addVariableModal').on('shown.bs.modal', function () {
            $('#variableNameInput').focus();
        });
    }

    /**
     * Test configuration with PHP backend
     */
    function testConfig() {
        const config = getCurrentConfig();
        if (!config) {
            showError('Please add components to test');
            return;
        }

        // This would normally make an AJAX call to PHP backend
        // For now, just show the configuration
        const $testResult = $('#testResult');
        $testResult.html(`
           <div class="alert alert-info alert-sm mt-2">
               <strong>Test Configuration:</strong><br>
               <small>Ready to test with ${config.formulas.length} formula(s)</small>
           </div>
       `);

        debug('Test configuration displayed', 'info');
    }

    /**
     * Show results in panel
     */
    function showResults(results, executionTime, isAuto = false) {
        const $panel = $('#resultsPanel');
        $panel.removeClass('error-panel warning-panel').addClass('result-panel');

        const title = isAuto ? 'Auto Results' : 'Execution Results';
        let html = `<h6>${title}</h6>`;

        if (Object.keys(results).length === 0) {
            html += '<p class="text-muted mb-0">No results</p>';
        } else {
            html += '<div class="results-grid">';
            Object.entries(results).forEach(([key, value]) => {
                html += `
                   <div class="result-item d-flex justify-content-between">
                       <span class="fw-semibold">${key}:</span>
                       <span class="text-primary">${formatValue(value)}</span>
                   </div>
               `;
            });
            html += '</div>';
        }

        if (executionTime !== undefined) {
            html += `<small class="text-muted d-block mt-2">Execution time: ${executionTime}ms</small>`;
        }

        $panel.html(html);
    }

    /**
     * Format value for display
     */
    function formatValue(value) {
        if (typeof value === 'number') {
            return Number.isInteger(value) ? value.toString() : value.toFixed(3);
        }
        return value;
    }

    /**
     * Copy text to clipboard
     */
    function copyToClipboard(text, successMessage) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                showSuccess(successMessage);
            }).catch(err => {
                console.error('Failed to copy:', err);
                fallbackCopyTextToClipboard(text, successMessage);
            });
        } else {
            fallbackCopyTextToClipboard(text, successMessage);
        }
    }

    /**
     * Fallback copy method
     */
    function fallbackCopyTextToClipboard(text, successMessage) {
        const $textArea = $('<textarea>').val(text).css({
            position: 'fixed',
            top: '0',
            left: '0'
        });

        $('body').append($textArea);
        $textArea[0].focus();
        $textArea[0].select();

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showSuccess(successMessage);
            } else {
                showError('Failed to copy');
            }
        } catch (err) {
            showError('Copy not supported');
        }

        $textArea.remove();
    }

    /**
     * Show toast message
     */
    function showToast(message, type = 'info') {
        // Create toast element if it doesn't exist
        if ($('#toastContainer').length === 0) {
            $('body').append('<div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;"></div>');
        }

        const toastId = 'toast_' + Date.now();
        const bgClass = type === 'success' ? 'bg-success' :
            type === 'error' ? 'bg-danger' :
                type === 'warning' ? 'bg-warning' : 'bg-info';

        const $toast = $(`
           <div id="${toastId}" class="toast ${bgClass} text-white" role="alert">
               <div class="toast-body">
                   ${message}
               </div>
           </div>
       `);

        $('#toastContainer').append($toast);

        // Initialize and show toast
        const toast = new bootstrap.Toast($toast[0], { delay: 3000 });
        toast.show();

        // Remove after hide
        $toast.on('hidden.bs.toast', function () {
            $(this).remove();
        });
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        showToast(message, 'success');
    }

    /**
     * Show error message
     */
    function showError(message) {
        showToast(message, 'error');
    }

    /**
     * Show warning message
     */
    function showWarning(message) {
        showToast(message, 'warning');
    }

    /**
     * Debug logging
     */
    function debug(message, type = 'info') {
        if (!debugEnabled) return;

        const timestamp = new Date().toLocaleTimeString();
        const prefix = type === 'error' ? '❌' :
            type === 'warning' ? '⚠️' :
                type === 'success' ? '✅' : 'ℹ️';

        console.log(`[${timestamp}] ${prefix} ${message}`);

        // Add to debug console if visible
        const $debugConsole = $('#debugConsole');
        if ($debugConsole.length && $('#debugSection').is(':visible')) {
            const $logEntry = $(`<div class="debug-entry text-${type}">[${timestamp}] ${message}</div>`);
            $debugConsole.append($logEntry);
            $debugConsole.scrollTop($debugConsole[0].scrollHeight);
        }
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Expose global functions for component usage
    window.updateComponentField = updateComponentField;
    window.updateJSON = updateJSON;
    window.updateInputVariables = updateInputVariables;
    window.debug = debug;

    // Global functions for switch case management
    window.addSwitchCase = function (componentIndex) {
        if (components[componentIndex] && components[componentIndex].instance.addCase) {
            components[componentIndex].instance.addCase();
            updateView();
            updateJSON();
        }
    };

    window.addNestedSwitchCase = function (componentIndex) {
        if (components[componentIndex] && components[componentIndex].instance.addNestedCase) {
            components[componentIndex].instance.addNestedCase();
            updateView();
            updateJSON();
        }
    };

    window.removeSwitchCase = function (componentIndex, caseIndex) {
        if (components[componentIndex] && components[componentIndex].instance.removeCase) {
            components[componentIndex].instance.removeCase(caseIndex);
            updateView();
            updateJSON();
        }
    };

    window.updateSwitchCase = function (componentIndex, caseIndex, field, value) {
        if (components[componentIndex] && components[componentIndex].instance.updateCase) {
            components[componentIndex].instance.updateCase(caseIndex, field, value);
            updateJSON();
            updateInputVariables();
        }
    };

    window.updateNestedSwitchCondition = function (componentIndex, caseIndex, conditionIndex, field, value) {
        if (components[componentIndex] && components[componentIndex].instance.updateNestedCondition) {
            components[componentIndex].instance.updateNestedCondition(caseIndex, conditionIndex, field, value);
            updateJSON();
            updateInputVariables();
        }
    };



    window.removeConditionFromGroup = function (componentIndex, caseIndex, conditionIndex, groupType) {
        if (components[componentIndex] && components[componentIndex].instance.removeConditionFromGroup) {
            components[componentIndex].instance.removeConditionFromGroup(caseIndex, conditionIndex, groupType);
            updateView();
            updateJSON();
        }
    };

    // Global functions for conditions management


    window.updateCondition = function (componentIndex, conditionIndex, field, value) {
        if (components[componentIndex] && components[componentIndex].instance.updateCondition) {
            components[componentIndex].instance.updateCondition(conditionIndex, field, value);
            updateJSON();
            updateInputVariables();
        }
    };

    // Global functions for scoring management
    window.addScoringBranch = function (componentIndex) {
        if (components[componentIndex] && components[componentIndex].instance.addScoringBranch) {
            components[componentIndex].instance.addScoringBranch();
            updateView();
            updateJSON();
        }
    };

    window.removeScoringBranch = function (componentIndex, branchIndex) {
        if (components[componentIndex] && components[componentIndex].instance.removeScoringBranch) {
            components[componentIndex].instance.removeScoringBranch(branchIndex);
            updateView();
            updateJSON();
        }
    };

    window.updateScoringBranch = function (componentIndex, branchIndex, field, value) {
        if (components[componentIndex] && components[componentIndex].instance.updateScoringBranch) {
            components[componentIndex].instance.updateScoringBranch(branchIndex, field, value);
            updateJSON();
            updateInputVariables();
        }
    };


    window.addCustomFieldToRange = function (componentIndex, branchIndex, rangeIndex, fieldName) {
        if (components[componentIndex] && components[componentIndex].instance.addCustomFieldToRange) {
            components[componentIndex].instance.addCustomFieldToRange(branchIndex, rangeIndex, fieldName, '');
            updateView();
            updateJSON();
        }
    };

    window.removeCustomFieldFromRange = function (componentIndex, branchIndex, rangeIndex, fieldName) {
        if (components[componentIndex] && components[componentIndex].instance.removeCustomFieldFromRange) {
            components[componentIndex].instance.removeCustomFieldFromRange(branchIndex, rangeIndex, fieldName);
            updateView();
            updateJSON();
        }
    };

    window.updateSetVars = function (componentIndex, branchIndex, rangeIndex, varsString) {
        if (components[componentIndex] && components[componentIndex].instance.updateSetVars) {
            components[componentIndex].instance.updateSetVars(branchIndex, rangeIndex, varsString);
            updateJSON();
            updateInputVariables();
        }
    };

    window.addRangeToBranch = function (componentIndex, branchIndex) {
        if (components[componentIndex] && components[componentIndex].instance.addRangeToBranch) {
            components[componentIndex].instance.addRangeToBranch(branchIndex);
            updateView();
            updateJSON();
        }
    };

    window.removeRangeFromBranch = function (componentIndex, branchIndex, rangeIndex) {
        if (components[componentIndex] && components[componentIndex].instance.removeRangeFromBranch) {
            components[componentIndex].instance.removeRangeFromBranch(branchIndex, rangeIndex);
            updateView();
            updateJSON();
        }
    };

    window.updateRangeInBranch = function (componentIndex, branchIndex, rangeIndex, field, value) {
        if (components[componentIndex] && components[componentIndex].instance.updateRangeField) {
            components[componentIndex].instance.updateRangeField(branchIndex, rangeIndex, field, value);
            updateJSON();
            updateInputVariables();
        }
    };

    window.updateSetVars = function (componentIndex, branchIndex, rangeIndex, varsString) {
        if (components[componentIndex] && components[componentIndex].instance.updateSetVars) {
            components[componentIndex].instance.updateSetVars(branchIndex, rangeIndex, varsString);
            updateJSON();
            updateInputVariables();
        }
    };

    window.addCustomFieldToRange = function (componentIndex, branchIndex, rangeIndex, fieldName) {
        if (components[componentIndex] && components[componentIndex].instance.addCustomFieldToRange) {
            components[componentIndex].instance.addCustomFieldToRange(branchIndex, rangeIndex, fieldName, '');
            updateView();
            updateJSON();
        }
    };

    window.removeCustomFieldFromRange = function (componentIndex, branchIndex, rangeIndex, fieldName) {
        if (components[componentIndex] && components[componentIndex].instance.removeCustomFieldFromRange) {
            components[componentIndex].instance.removeCustomFieldFromRange(branchIndex, rangeIndex, fieldName);
            updateView();
            updateJSON();
        }
    };

    window.updateNestedBranchCondition = function (componentIndex, branchIndex, conditionIndex, field, value) {
        if (components[componentIndex] && components[componentIndex].instance.updateNestedBranchCondition) {
            components[componentIndex].instance.updateNestedBranchCondition(branchIndex, conditionIndex, field, value);
            updateJSON();
            updateInputVariables();
        }
    };

    window.addConditionToBranchGroup = function(componentIndex, branchIndex, groupType) {
        if (components[componentIndex] && components[componentIndex].instance.addConditionToBranchGroup) {
            components[componentIndex].instance.addConditionToBranchGroup(branchIndex, groupType);
            updateView();
            updateJSON();
        }
    };

    window.removeConditionFromBranchGroup = function(componentIndex, branchIndex, conditionIndex, groupType) {
        if (components[componentIndex] && components[componentIndex].instance.removeConditionFromBranchGroup) {
            components[componentIndex].instance.removeConditionFromBranchGroup(branchIndex, conditionIndex, groupType);
            updateView();
            updateJSON();
        }
    };

    window.updateRangeConditionType = function(componentIndex, branchIndex, rangeIndex, conditionType) {
        if (components[componentIndex] && components[componentIndex].instance.updateRangeConditionType) {
            components[componentIndex].instance.updateRangeConditionType(branchIndex, rangeIndex, conditionType);
            updateView();
            updateJSON();
        }
    };

    window.updateNestedRangeCondition = function(componentIndex, branchIndex, rangeIndex, conditionIndex, field, value) {
        if (components[componentIndex] && components[componentIndex].instance.updateNestedRangeCondition) {
            components[componentIndex].instance.updateNestedRangeCondition(branchIndex, rangeIndex, conditionIndex, field, value);
            updateJSON();
            updateInputVariables();
        }
    };

    window.addConditionToRangeGroup = function(componentIndex, branchIndex, rangeIndex, groupType) {
        if (components[componentIndex] && components[componentIndex].instance.addConditionToRangeGroup) {
            components[componentIndex].instance.addConditionToRangeGroup(branchIndex, rangeIndex, groupType);
            updateView();
            updateJSON();
        }
    };

    window.removeConditionFromRangeGroup = function(componentIndex, branchIndex, rangeIndex, conditionIndex, groupType) {
        if (components[componentIndex] && components[componentIndex].instance.removeConditionFromRangeGroup) {
            components[componentIndex].instance.removeConditionFromRangeGroup(branchIndex, rangeIndex, conditionIndex, groupType);
            updateView();
            updateJSON();
        }
    };

    //Rule management start 
    window.addRule = function(componentIndex) {
        if (components[componentIndex] && components[componentIndex].instance.addRule) {
            components[componentIndex].instance.addRule();
            updateView();
            updateJSON();
        }
    };

    window.removeRule = function(componentIndex, ruleIndex) {
        if (components[componentIndex] && components[componentIndex].instance.removeRule) {
            components[componentIndex].instance.removeRule(ruleIndex);
            updateView();
            updateJSON();
        }
    };

    window.updateRuleVar = function(componentIndex, ruleIndex, value) {
        if (components[componentIndex] && components[componentIndex].instance.updateRuleVar) {
            components[componentIndex].instance.updateRuleVar(ruleIndex, value);
            updateJSON();
            updateInputVariables();
        }
    };

    // Range management
    window.addRangeToRule = function(componentIndex, ruleIndex) {
        if (components[componentIndex] && components[componentIndex].instance.addRangeToRule) {
            components[componentIndex].instance.addRangeToRule(ruleIndex);
            updateView();
            updateJSON();
        }
    };

    window.removeRangeFromRule = function(componentIndex, ruleIndex, rangeIndex) {
        if (components[componentIndex] && components[componentIndex].instance.removeRangeFromRule) {
            components[componentIndex].instance.removeRangeFromRule(ruleIndex, rangeIndex);
            updateView();
            updateJSON();
        }
    };

    window.updateRuleRangeField = function(componentIndex, ruleIndex, rangeIndex, field, value) {
        if (components[componentIndex] && components[componentIndex].instance.updateRangeField) {
            components[componentIndex].instance.updateRangeField(ruleIndex, rangeIndex, field, value);
            updateJSON();
            updateInputVariables();
        }
    };

    // Condition type management
    window.updateRuleRangeConditionType = function(componentIndex, ruleIndex, rangeIndex, conditionType) {
        if (components[componentIndex] && components[componentIndex].instance.updateRangeConditionType) {
            components[componentIndex].instance.updateRangeConditionType(ruleIndex, rangeIndex, conditionType);
            updateView();
            updateJSON();
        }
    };

    // Nested condition management
    window.updateNestedRuleRangeCondition = function(componentIndex, ruleIndex, rangeIndex, conditionIndex, field, value) {
        if (components[componentIndex] && components[componentIndex].instance.updateNestedRangeCondition) {
            components[componentIndex].instance.updateNestedRangeCondition(ruleIndex, rangeIndex, conditionIndex, field, value);
            updateJSON();
            updateInputVariables();
        }
    };

    window.addConditionToRuleRangeGroup = function(componentIndex, ruleIndex, rangeIndex, groupType) {
        if (components[componentIndex] && components[componentIndex].instance.addConditionToRangeGroup) {
            components[componentIndex].instance.addConditionToRangeGroup(ruleIndex, rangeIndex, groupType);
            updateView();
            updateJSON();
        }
    };

    window.removeConditionFromRuleRangeGroup = function(componentIndex, ruleIndex, rangeIndex, conditionIndex, groupType) {
        if (components[componentIndex] && components[componentIndex].instance.removeConditionFromRangeGroup) {
            components[componentIndex].instance.removeConditionFromRangeGroup(ruleIndex, rangeIndex, conditionIndex, groupType);
            updateView();
            updateJSON();
        }
    };

    // Set vars management
    window.updateRuleSetVars = function(componentIndex, ruleIndex, rangeIndex, varsString) {
        if (components[componentIndex] && components[componentIndex].instance.updateSetVars) {
            components[componentIndex].instance.updateSetVars(ruleIndex, rangeIndex, varsString);
            updateJSON();
            updateInputVariables();
        }
    };
     //Rule management end

    function addVisualFeedbackStyles() {
        const style = `
            <style id="visual-feedback-styles">
            .form-control.changing {
                border-color: #0d6efd;
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
                transition: all 0.15s ease-in-out;
            }
            
            .component-title h6 {
                transition: color 0.3s ease;
            }
            
            .component-title h6.updating {
                color: #0d6efd;
            }
            </style>
        `;
        
        if (!$('#visual-feedback-styles').length) {
            $('head').append(style);
        }
    }

    initializeUI();
    bindEvents();
    addVisualFeedbackStyles();
    setupRealTimeInputHandling();
    updateView();
});