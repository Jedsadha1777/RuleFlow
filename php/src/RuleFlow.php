<?php
declare(strict_types=1);

require_once 'FormulaProcessor.php';
require_once 'ExpressionEvaluator.php';
require_once 'ConfigValidator.php';
require_once 'InputValidator.php';
require_once 'CodeGenerator.php';
require_once 'FunctionRegistry.php';
require_once 'RuleFlowException.php';
require_once 'Templates/ConfigTemplateManager.php';
require_once 'SchemaGenerator.php';
require_once 'ValidationAPI.php';

// ============================================
// 🔌 Interface สำหรับ Auto-discovery
// ============================================

interface RuleFlowFunctionProvider
{
    /**
     * Get functions ที่จะ register เข้า RuleFlow
     * @return array ['function_name' => callable]
     */
    public static function getFunctions(): array;
    
    /**
     * Get plugin info (optional)
     * @return array ['name' => string, 'version' => string, 'description' => string]
     */
    public static function getInfo(): array;
}

/**
 * Main RuleFlow API - Enhanced entry point with SECURE functionality + Auto-discovery
 * 🔒 Security Note: Removed dangerous eval() usage and replaced with safe alternatives
 * 🔌 Auto-discovery: Functions are automatically loaded from Functions/ folder
 */
class RuleFlow
{
    private FormulaProcessor $processor;
    private ConfigValidator $validator;
    private InputValidator $inputValidator;
    private CodeGenerator $codeGenerator;
    private ExpressionEvaluator $evaluator;
    private FunctionRegistry $functions;
    private ConfigTemplateManager $templateManager;
    private SchemaGenerator $schemaGenerator;
    private ValidationAPI $validationAPI;

    // 🔒 Security: Cache for pre-validated configs to avoid repeated validation overhead
    private array $configCache = [];
    
    // 🔌 Auto-discovery properties
    private array $autoLoadedFunctions = [];
    private string $functionsPath = 'Functions/';

    public function __construct()
    {
        $this->functions = new FunctionRegistry();
        $this->evaluator = new ExpressionEvaluator($this->functions);
        $this->processor = new FormulaProcessor($this->evaluator);
        $this->validator = new ConfigValidator();
        $this->inputValidator = new InputValidator();
        $this->codeGenerator = new CodeGenerator();
        $this->templateManager = new ConfigTemplateManager();
        $this->schemaGenerator = new SchemaGenerator();
        $this->validationAPI = new ValidationAPI();
        
        // 🔌 Auto-load functions จาก Functions/ folder
        $this->autoLoadFunctions();
    }

    // ============================================
    // 🔌 AUTO-DISCOVERY METHODS
    // ============================================
    
    /**
     * Auto-load functions จาก Functions/ folder
     */
    private function autoLoadFunctions(): void
    {
        $functionsDir = __DIR__ . '/' . $this->functionsPath;
        
        if (!is_dir($functionsDir)) {
            return; // ถ้าไม่มี folder ก็ข้าม
        }
        
        // หา PHP files ใน folder
        $files = glob($functionsDir . '*.php');
        
        foreach ($files as $file) {
            $this->loadFunctionFile($file);
        }
    }
    
    /**
     * Load function file และ register functions
     */
    private function loadFunctionFile(string $file): void
    {
        try {
            require_once $file;
            
            // หา class name จาก filename
            $className = basename($file, '.php');
            
            // ตรวจสอบว่า class implement interface หรือไม่
            if (class_exists($className) && 
                in_array(RuleFlowFunctionProvider::class, class_implements($className))) {
                
                // Get functions และ register
                $functions = $className::getFunctions();
                $info = method_exists($className, 'getInfo') ? $className::getInfo() : ['name' => $className];
                
                foreach ($functions as $name => $handler) {
                    if (is_callable($handler)) {
                        $this->functions->register($name, $handler);
                        $this->autoLoadedFunctions[$name] = [
                            'provider' => $className,
                            'info' => $info
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            // Silent fail - ไม่ให้ plugin ที่เสียทำให้ระบบหลักพัง
            error_log("Failed to load function file {$file}: " . $e->getMessage());
        }
    }
    
    /**
     * Get auto-loaded functions info
     */
    public function getAutoLoadedFunctions(): array
    {
        return $this->autoLoadedFunctions;
    }
    
    /**
     * Reload functions (สำหรับ development)
     */
    public function reloadFunctions(): void
    {
        // Clear existing auto-loaded functions
        foreach (array_keys($this->autoLoadedFunctions) as $functionName) {
            // Note: FunctionRegistry should have unregister method
            if (method_exists($this->functions, 'unregister')) {
                $this->functions->unregister($functionName);
            }
        }
        
        $this->autoLoadedFunctions = [];
        $this->autoLoadFunctions();
    }

    // ============================================
    // 🔧 EXISTING METHODS (ไม่เปลี่ยน)
    // ============================================

    /**
     * Evaluate configuration with inputs
     */
    public function evaluate(array $config, array $inputs): array
    {
        try {
            $validatedConfig = $this->validator->validate($config);
            return $this->processor->processAll($validatedConfig, $inputs);
        } catch (RuleFlowException $e) {
            throw $e;
        } catch (Exception $e) {
            throw RuleFlowException::expressionError('evaluation', $e->getMessage());
        }
    }

    /**
     * 🔒 SECURE: Generate PHP function code as string (for manual implementation)
     * This replaces the dangerous createOptimizedEvaluator method
     */
    public function generateFunctionAsString(array $config): string
    {
        $validatedConfig = $this->validator->validate($config);
        return $this->codeGenerator->generate($validatedConfig);
    }

    /**
     * 🔒 SECURE: Create cached evaluator (safe alternative to eval-based optimization)
     * This provides performance benefits without security risks
     */
    public function createCachedEvaluator(array $config): callable
    {
        $configHash = md5(serialize($config));
        
        // Cache the validated config to avoid repeated validation
        if (!isset($this->configCache[$configHash])) {
            $this->configCache[$configHash] = $this->validator->validate($config);
        }
        
        $validatedConfig = $this->configCache[$configHash];
        
        // Return a closure that reuses the validated config
        return function(array $inputs) use ($validatedConfig): array {
            return $this->processor->processAll($validatedConfig, $inputs);
        };
    }

    /**
     * 🔒 SECURE: Prepare configuration for high-performance usage
     * Pre-validates and optimizes config for repeated use
     */
    public function prepareConfig(array $config): string
    {
        $validatedConfig = $this->validator->validate($config);
        $configHash = md5(serialize($config));
        
        // Cache the validated config
        $this->configCache[$configHash] = $validatedConfig;
        
        // Return the hash for later use
        return $configHash;
    }

    /**
     * 🔒 SECURE: Evaluate using pre-prepared configuration
     * Much faster than regular evaluate() for repeated use
     */
    public function evaluatePrepared(string $configHash, array $inputs): array
    {
        if (!isset($this->configCache[$configHash])) {
            throw new RuleFlowException("Configuration not found. Use prepareConfig() first.", [
                'config_hash' => $configHash,
                'available_configs' => array_keys($this->configCache)
            ]);
        }
        
        return $this->processor->processAll($this->configCache[$configHash], $inputs);
    }

    /**
     * Evaluate multiple input sets efficiently (simple version)
     */
    public function evaluateBatch(array $config, array $inputSets): array
    {
        $validatedConfig = $this->validator->validate($config);
        $results = [];
        
        foreach ($inputSets as $index => $inputs) {
            try {
                $results[$index] = [
                    'success' => true,
                    'result' => $this->processor->processAll($validatedConfig, $inputs),
                    'input_set' => $index
                ];
            } catch (Exception $e) {
                $results[$index] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'input_set' => $index
                ];
            }
        }
        
        $successCount = 0;
        $failCount = 0;
        foreach ($results as $result) {
            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }
        
        return [
            'total_processed' => count($inputSets),
            'successful' => $successCount,
            'failed' => $failCount,
            'results' => $results
        ];
    }

    /**
     * Validate configuration only
     */
    public function validateConfig(array $config): array
    {
        try {
            $this->validator->validate($config);
            $warnings = $this->validator->checkForWarnings($config);
            
            return [
                'valid' => true,
                'errors' => [],
                'warnings' => $warnings,
                'metadata' => [
                    'total_formulas' => count($config['formulas'] ?? []),
                    'required_inputs' => $this->validator->extractRequiredInputs($config),
                    'validation_timestamp' => date('c')
                ]
            ];
        } catch (RuleFlowException $e) {
            return [
                'valid' => false,
                'errors' => $e->getValidationErrors(),
                'warnings' => [],
                'metadata' => [
                    'error_type' => $e->getErrorType(),
                    'suggestion' => $e->getSuggestion()
                ]
            ];
        }
    }

    /**
     * Test configuration with sample inputs
     */
    public function testConfig(array $config, array $sampleInputs = []): array
    {
        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'test_results' => [],
            'performance' => []
        ];

        try {
            $startTime = microtime(true);
            
            $validatedConfig = $this->validator->validate($config);
            
            if (!empty($sampleInputs)) {
                $testStartTime = microtime(true);
                $testResult = $this->processor->processAll($validatedConfig, $sampleInputs);
                $testEndTime = microtime(true);
                
                $result['test_results'] = $testResult;
                $result['performance'] = [
                    'execution_time_ms' => round(($testEndTime - $testStartTime) * 1000, 2),
                    'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                    'formulas_processed' => count($config['formulas'] ?? [])
                ];
            }
            
            $warnings = $this->validator->checkForWarnings($validatedConfig);
            $result['warnings'] = $warnings;
            
            $endTime = microtime(true);
            $result['performance']['total_time_ms'] = round(($endTime - $startTime) * 1000, 2);
            
        } catch (RuleFlowException $e) {
            $result['valid'] = false;
            $result['errors'] = is_array($e->getValidationErrors()) ? 
                $e->getValidationErrors() : [$e->getMessage()];
            $result['error_context'] = $e->getContext();
        } catch (Exception $e) {
            $result['valid'] = false;
            $result['errors'][] = "Test execution failed: " . $e->getMessage();
        }

        return $result;
    }

    /**
     * Get configuration templates
     */
    public function getTemplates(string $category = null): array
    {
        if ($category) {
            return $this->templateManager->getTemplatesByCategory($category);
        }
        
        return [
            'available_templates' => $this->templateManager->getAvailableTemplates(),
            'categories' => ['financial', 'hr', 'insurance', 'healthcare', 'education', 'ecommerce', 'real_estate']
        ];
    }

    /**
     * Get specific template
     */
    public function getTemplate(string $name, array $params = []): array
    {
        if (empty($params)) {
            return $this->templateManager->getTemplate($name);
        }
        
        return $this->templateManager->getTemplateWithParams($name, $params);
    }

    /**
     * Search templates
     */
    public function searchTemplates(string $keyword): array
    {
        return $this->templateManager->searchTemplates($keyword);
    }

    /**
     * Generate input schema
     */
    public function generateInputSchema(array $config, string $format = 'json')
    {
        switch ($format) {
            case 'json':
                return $this->schemaGenerator->generateJSONSchema($config);
            case 'typescript':
                return $this->schemaGenerator->generateTypeScriptInterface($config);
            case 'openapi':
                return $this->schemaGenerator->generateOpenAPISchema($config);
            case 'html':
                return $this->schemaGenerator->generateHTMLForm($config);
            case 'react':
                return $this->schemaGenerator->generateReactComponent($config);
            case 'laravel':
                return $this->schemaGenerator->generateValidationRules($config, 'laravel');
            case 'joi':
                return $this->schemaGenerator->generateValidationRules($config, 'joi');
            case 'yup':
                return $this->schemaGenerator->generateValidationRules($config, 'yup');
            default:
                return $this->schemaGenerator->generateInputSchema($config);
        }
    }

    /**
     * Generate documentation
     */
    public function generateDocumentation(array $config): array
    {
        return $this->schemaGenerator->generateDocumentation($config);
    }

    /**
     * Real-time field validation
     */
    public function validateField(string $fieldName, $value, array $config = []): array
    {
        return $this->validationAPI->validateField($fieldName, $value, $config);
    }

    /**
     * Validate partial form data
     */
    public function validatePartial(array $inputs, array $config): array
    {
        return $this->validationAPI->validatePartial($inputs, $config);
    }

    /**
     * Get validation status with progress
     */
    public function getValidationStatus(array $inputs, array $config): array
    {
        return $this->validationAPI->getValidationStatus($inputs, $config);
    }

    /**
     * Generate live preview
     */
    public function generateLivePreview(array $inputs, array $config): array
    {
        return $this->validationAPI->generateLivePreview($inputs, $config);
    }

    /**
     * Get field suggestions
     */
    public function getFieldSuggestions(string $fieldName, array $config, string $query = ''): array
    {
        return $this->validationAPI->getFieldSuggestions($fieldName, $config, $query);
    }

    /**
     * Batch validate multiple fields
     */
    public function batchValidateFields(array $fieldUpdates, array $currentInputs, array $config): array
    {
        return $this->validationAPI->batchValidateFields($fieldUpdates, $currentInputs, $config);
    }

    /**
     * Register custom function (manual registration)
     */
    public function registerFunction(string $name, callable $handler): void
    {
        $this->functions->register($name, $handler);
    }

    /**
     * Get available functions (includes auto-loaded)
     */
    public function getAvailableFunctions(): array
    {
        $builtin = $this->functions->getAvailableFunctions();
        $autoLoaded = array_keys($this->autoLoadedFunctions);
        
        return [
            'functions' => array_merge($builtin, $autoLoaded),
            'categories' => $this->functions->getFunctionsByCategory(),
            'auto_loaded' => $this->autoLoadedFunctions
        ];
    }

    /**
     * Register custom template
     */
    public function registerTemplate(string $name, array $config, array $metadata = []): void
    {
        $this->templateManager->registerTemplate($name, $config, $metadata);
    }

    /**
     * Export template
     */
    public function exportTemplate(string $name): string
    {
        return $this->templateManager->exportTemplate($name);
    }

    /**
     * Import template from JSON
     */
    public function importTemplate(string $json): void
    {
        $this->templateManager->importTemplate($json);
    }

    /**
     * Get system information (updated with auto-discovery info)
     */
    public function getSystemInfo(): array
    {
        return [
            'version' => '2.0.0-secure-autodiscovery',
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'available_functions' => count($this->functions->getAvailableFunctions()),
            'auto_loaded_functions' => count($this->autoLoadedFunctions),
            'available_templates' => count($this->templateManager->getAvailableTemplates()),
            'cached_configs' => count($this->configCache),
            'security_features' => [
                'eval_usage' => false,
                'input_sanitization' => true,
                'memory_limits' => true,
                'timeout_protection' => true,
                'config_caching' => true
            ],
            'auto_discovery' => [
                'enabled' => true,
                'functions_path' => $this->functionsPath,
                'loaded_providers' => array_unique(array_column($this->autoLoadedFunctions, 'provider'))
            ],
            'extensions' => [
                'json' => extension_loaded('json'),
                'mbstring' => extension_loaded('mbstring'),
                'pcre' => extension_loaded('pcre')
            ]
        ];
    }

    /**
     * Get input validator instance
     */
    public function getInputValidator(): InputValidator
    {
        return $this->inputValidator;
    }

    /**
     * Get config validator instance
     */
    public function getConfigValidator(): ConfigValidator
    {
        return $this->validator;
    }

    /**
     * Get template manager instance
     */
    public function getTemplateManager(): ConfigTemplateManager
    {
        return $this->templateManager;
    }

    /**
     * Get schema generator instance
     */
    public function getSchemaGenerator(): SchemaGenerator
    {
        return $this->schemaGenerator;
    }

    /**
     * Get validation API instance
     */
    public function getValidationAPI(): ValidationAPI
    {
        return $this->validationAPI;
    }

    /**
     * Debug configuration execution
     */
    public function debugEvaluate(array $config, array $inputs): array
    {
        $validatedConfig = $this->validator->validate($config);
        $debugInfo = [
            'execution_order' => [],
            'intermediate_values' => [],
            'formula_timing' => [],
            'memory_usage' => []
        ];
        
        $context = $inputs;
        $startMemory = memory_get_usage(true);
        
        foreach ($validatedConfig['formulas'] as $formula) {
            $startTime = microtime(true);
            $beforeMemory = memory_get_usage(true);
            
            $debugInfo['execution_order'][] = $formula['id'];
            $debugInfo['intermediate_values'][$formula['id']] = [
                'before' => $context,
                'formula' => $formula
            ];
            
            $this->processor->processFormula($formula, $context);
            
            $endTime = microtime(true);
            $afterMemory = memory_get_usage(true);
            
            $debugInfo['intermediate_values'][$formula['id']]['after'] = $context;
            $debugInfo['formula_timing'][$formula['id']] = round(($endTime - $startTime) * 1000, 4);
            $debugInfo['memory_usage'][$formula['id']] = [
                'before_mb' => round($beforeMemory / 1024 / 1024, 2),
                'after_mb' => round($afterMemory / 1024 / 1024, 2),
                'delta_mb' => round(($afterMemory - $beforeMemory) / 1024 / 1024, 2)
            ];
        }
        
        $debugInfo['total_memory_mb'] = round((memory_get_usage(true) - $startMemory) / 1024 / 1024, 2);
        $debugInfo['peak_memory_mb'] = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        
        return [
            'result' => $context,
            'debug_info' => $debugInfo
        ];
    }

    /**
     * 🔒 SECURE: Clear config cache to free memory
     */
    public function clearConfigCache(): void
    {
        $this->configCache = [];
    }

    /**
     * 🔒 SECURE: Get cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'cached_configs' => count($this->configCache),
            'memory_used_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'cache_keys' => array_keys($this->configCache)
        ];
    }
}