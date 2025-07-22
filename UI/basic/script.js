// Global state
let components = [];
let componentCounter = 0;

// Component registry - will be populated by component files
window.RuleFlowComponents = {};

// Main app initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 RuleFlow Builder initialized');
    setupEventListeners();
    updateJSON();
});

function setupEventListeners() {
    // Component selector dropdown clicks
    document.addEventListener('click', function(e) {
        const componentLink = e.target.closest('[data-component-type]');
        if (componentLink) {
            e.preventDefault();
            const type = componentLink.dataset.componentType;
            addComponent(type);
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 's':
                    e.preventDefault();
                    copyJSON();
                    break;
            }
        }
    });
}

function addComponent(type) {
    if (!window.RuleFlowComponents[type]) {
        console.error(`Component type '${type}' not found`);
        return;
    }
    
    componentCounter++;
    const id = `component_${componentCounter}`;
    
    const ComponentClass = window.RuleFlowComponents[type];
    const componentInstance = new ComponentClass(`${type}_${componentCounter}`);
    
    const component = {
        id: id,
        type: type,
        instance: componentInstance,
        open: true
    };
    
    components.push(component);
    
    console.log(`Added ${type} component:`, component);
    
    renderComponents();
    updateJSON();
    
    // Hide empty state
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        emptyState.style.display = 'none';
    }
}

function renderComponents() {
    const container = document.getElementById('componentsList');
    const emptyState = document.getElementById('emptyState');
    
    if (!container) return;
    
    // Clear existing components (except empty state)
    Array.from(container.children).forEach(child => {
        if (child.id !== 'emptyState') {
            child.remove();
        }
    });
    
    if (components.length === 0) {
        if (emptyState) {
            emptyState.style.display = 'block';
        }
        return;
    }
    
    if (emptyState) {
        emptyState.style.display = 'none';
    }
    
    components.forEach((component, index) => {
        const element = createComponentElement(component, index);
        container.appendChild(element);
    });
}

function createComponentElement(component, index) {
    const div = document.createElement('div');
    div.className = 'card mb-3';
    div.innerHTML = getComponentHTML(component, index);
    
    // Setup event listeners
    setupComponentEvents(div, component, index);
    
    return div;
}

function getComponentHTML(component, index) {
    const iconClass = component.type;
    const title = component.instance.getTitle();
    const chevronIcon = component.open ? 'chevron-down' : 'chevron-right';
    
    return `
        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-outline-secondary me-2" 
                            onclick="toggleComponent(${index})" type="button">
                        <i class="bi bi-${chevronIcon}"></i>
                    </button>
                    <div class="component-icon ${iconClass} me-2">${component.instance.getIcon()}</div>
                    <div>
                        <h6 class="mb-0">${title}</h6>
                        <small class="text-muted">${component.type}</small>
                    </div>
                </div>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" 
                            onclick="copyComponent(${index})" 
                            title="Copy">
                        <i class="bi bi-files"></i>
                    </button>
                    <button class="btn btn-outline-danger" 
                            onclick="deleteComponent(${index})" 
                            title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body ${component.open ? '' : 'd-none'}">
            ${component.instance.getFormHTML(index)}
        </div>
    `;
}

function setupComponentEvents(element, component, index) {
    // Form field listeners
    const inputs = element.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('input', function(e) {
            component.instance.updateFromForm(element);
            updateComponentTitle(component, element);
            updateJSON();
        });
    });
    
    // Component-specific event setup
    if (component.instance.setupEvents) {
        component.instance.setupEvents(element, index);
    }
}

function updateComponentTitle(component, element) {
    const title = component.instance.getTitle();
    const titleElement = element.querySelector('h6');
    if (titleElement) {
        titleElement.textContent = title;
    }
}

// Component management functions
function toggleComponent(index) {
    if (components[index]) {
        components[index].open = !components[index].open;
        renderComponents();
    }
}

function deleteComponent(index) {
    if (confirm('Delete this component?')) {
        components.splice(index, 1);
        renderComponents();
        updateJSON();
        
        // Show empty state if no components
        if (components.length === 0) {
            const emptyState = document.getElementById('emptyState');
            if (emptyState) {
                emptyState.style.display = 'block';
            }
        }
    }
}

function copyComponent(index) {
    if (!components[index]) return;
    
    const original = components[index];
    componentCounter++;
    
    const ComponentClass = window.RuleFlowComponents[original.type];
    const newInstance = new ComponentClass(`${original.type}_${componentCounter}`);
    newInstance.config = JSON.parse(JSON.stringify(original.instance.config));
    
    const copy = {
        id: `component_${componentCounter}`,
        type: original.type,
        instance: newInstance,
        open: true
    };
    
    components.splice(index + 1, 0, copy);
    
    renderComponents();
    updateJSON();
}

// JSON generation and management
function updateJSON() {
    const config = {
        formulas: components.map(comp => comp.instance.toJSON())
    };
    
    const jsonOutput = document.getElementById('jsonOutput');
    if (jsonOutput) {
        jsonOutput.textContent = JSON.stringify(config, null, 2);
    }
}

function copyJSON() {
    const jsonOutput = document.getElementById('jsonOutput');
    if (!jsonOutput) return;
    
    const jsonText = jsonOutput.textContent;
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(jsonText).then(() => {
            showToast('JSON copied to clipboard!', 'success');
        }).catch(err => {
            console.error('Failed to copy:', err);
            fallbackCopyTextToClipboard(jsonText);
        });
    } else {
        fallbackCopyTextToClipboard(jsonText);
    }
}

function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showToast('JSON copied to clipboard!', 'success');
        } else {
            showToast('Failed to copy JSON', 'danger');
        }
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
        showToast('Failed to copy JSON', 'danger');
    }
    
    document.body.removeChild(textArea);
}

function showToast(message, type = 'info') {
    // Bootstrap toast
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        toastContainer.style.zIndex = '1080';
        document.body.appendChild(toastContainer);
    }
    
    // Add toast
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// PHP Backend Integration
async function testConfig() {
    const config = {
        formulas: components.map(comp => comp.instance.toJSON())
    };
    
    const testResult = document.getElementById('testResult');
    if (!testResult) return;
    
    // Show loading state
    testResult.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Testing configuration...
        </div>
    `;
    testResult.className = 'alert alert-info mt-2';
    
    try {
        // Mock test data
        const testInputs = {
            weight: 70,
            height: 175,
            age: 30,
            income: 50000
        };
        
        const response = await fetch('test-config.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                config: config,
                inputs: testInputs
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            testResult.className = 'alert alert-success mt-2';
            testResult.innerHTML = `
                <div class="d-flex align-items-center mb-2">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Test Successful!</strong>
                </div>
                <pre class="mb-0" style="font-size: 11px; background: rgba(255,255,255,0.2); padding: 8px; border-radius: 4px;">${JSON.stringify(result.data, null, 2)}</pre>
            `;
        } else {
            testResult.className = 'alert alert-danger mt-2';
            testResult.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>
                        <strong>Test Failed</strong><br>
                        <small>${result.error || 'Unknown error'}</small>
                    </div>
                </div>
            `;
        }
        
    } catch (error) {
        console.error('Test error:', error);
        testResult.className = 'alert alert-warning mt-2';
        testResult.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-wifi-off me-2"></i>
                <div>
                    <strong>Connection Error</strong><br>
                    <small>Cannot connect to PHP backend. Make sure the server is running.</small>
                </div>
            </div>
        `;
    }
}

// Development helpers
function generateSampleData() {
    addComponent('formula');
    addComponent('switch');
    
    // Update components with sample data
    if (components.length >= 1) {
        components[0].instance.config = {
            id: 'bmi_calculation',
            formula: 'weight / ((height/100) ** 2)',
            inputs: ['weight', 'height'],
            as: '$bmi_value'
        };
    }
    
    if (components.length >= 2) {
        components[1].instance.config = {
            id: 'bmi_category',
            switch: '$bmi_value',
            when: [
                { if: { op: '<', value: 18.5 }, result: 'Underweight' },
                { if: { op: 'between', value: [18.5, 24.9] }, result: 'Normal' },
                { if: { op: '>=', value: 25 }, result: 'Overweight' }
            ],
            default: 'Unknown'
        };
    }
    
    renderComponents();
    updateJSON();
}

// Demo mode
if (window.location.search.includes('demo=true')) {
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(generateSampleData, 500);
    });
}

// Export for debugging
window.RuleFlowDebug = {
    components,
    generateSampleData,
    updateJSON
};