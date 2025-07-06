<?php

declare(strict_types=1);

require_once __DIR__ . '/TemplateProviderInterface.php';
require_once __DIR__ . '/../RuleFlowException.php';

/**
 * Pre-built configuration templates for common business use cases
 */
class ConfigTemplateManager
{
    private array $templates = [];
    private array $providers = [];
    private string $providersPath;
    private array $providerCache = [];
    private bool $cacheEnabled = true;
    private string $cacheFile;
    
    public function __construct(string $providersPath = null, bool $enableCache = true, string $cacheDir = null)
    {
        $this->providersPath = $providersPath ?? __DIR__ . '/Providers';
        $this->cacheEnabled = $enableCache;
        
        // Set cache directory - prefer order: custom > providers/cache directory
        if ($cacheDir) {
            $this->cacheFile = rtrim($cacheDir, '/') . '/template_provider_cache.json';
        } else {
            // Default: use cache subdirectory in providers directory
            $providersCacheDir = $this->providersPath . '/cache';
            $this->cacheFile = $providersCacheDir . '/.template_cache.json';
        }
        
        // Debug: Log cache file path
        error_log("Template cache file will be: " . $this->cacheFile);
        
        // Ensure cache directory exists
        $this->ensureCacheDirectory();
        
        if ($this->cacheEnabled && $this->isCacheValid()) {
            $this->loadFromCache();
        } else {
            $this->loadBuiltInTemplates();
            if ($this->cacheEnabled) {
                $this->saveToCache();
            }
        }
    }
    
    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectory(): void
    {
        $cacheDir = dirname($this->cacheFile);
        
        // Debug: Log directory creation attempt
        error_log("Checking cache directory: $cacheDir");
        
        if (!is_dir($cacheDir)) {
            error_log("Creating cache directory: $cacheDir");
            if (!mkdir($cacheDir, 0755, true)) {
                error_log("Failed to create cache directory: $cacheDir");
                // If can't create directory, fall back to temp file in system temp
                if (function_exists('sys_get_temp_dir')) {
                    $this->cacheFile = tempnam(sys_get_temp_dir(), 'ruleflow_cache_') . '.json';
                    error_log("Fallback cache file: " . $this->cacheFile);
                } else {
                    // Last resort: disable cache
                    $this->cacheEnabled = false;
                    error_log("Template cache disabled: Cannot create cache directory");
                    return;
                }
            }
        }
        
        // Check if cache directory is writable
        $cacheDir = dirname($this->cacheFile);
        if (!is_writable($cacheDir)) {
            error_log("Cache directory not writable: $cacheDir");
            // Try to make it writable
            if (!chmod($cacheDir, 0755)) {
                // Disable cache if not writable
                $this->cacheEnabled = false;
                error_log("Template cache disabled: Cache directory not writable: $cacheDir");
            }
        }
        
        error_log("Cache enabled: " . ($this->cacheEnabled ? 'true' : 'false'));
    }
    
    /**
     * Check if cache is valid (compare file modification times)
     */
    private function isCacheValid(): bool
    {
        if (!file_exists($this->cacheFile)) {
            return false;
        }
        
        $cacheTime = filemtime($this->cacheFile);
        $providerFiles = glob($this->providersPath . '/*.php');
        
        foreach ($providerFiles as $file) {
            if (filemtime($file) > $cacheTime) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Load templates from cache
     */
    private function loadFromCache(): void
    {
        if (!$this->cacheEnabled || !file_exists($this->cacheFile)) {
            return;
        }
        
        $cacheContent = file_get_contents($this->cacheFile);
        if ($cacheContent === false) {
            error_log("Failed to read cache file: " . $this->cacheFile);
            return;
        }
        
        $cacheData = json_decode($cacheContent, true);
        if ($cacheData === null) {
            error_log("Invalid JSON in cache file: " . $this->cacheFile);
            // Delete corrupted cache file
            unlink($this->cacheFile);
            return;
        }
        
        // Validate cache data structure
        if (isset($cacheData['templates']) && isset($cacheData['providers_path'])) {
            // Check if cache is for the same providers path
            if ($cacheData['providers_path'] === $this->providersPath) {
                $this->templates = $cacheData['templates'];
                $this->providerCache = $cacheData['providers'] ?? [];
            }
        }
    }
    
    /**
     * Save templates to cache
     */
    private function saveToCache(): void
    {
        if (!$this->cacheEnabled) {
            error_log("Cache save skipped: cache disabled");
            return;
        }
        
        $cacheData = [
            'templates' => $this->templates,
            'providers' => $this->providerCache,
            'cached_at' => time(),
            'providers_path' => $this->providersPath,
            'cache_version' => '1.0'
        ];
        
        $jsonData = json_encode($cacheData, JSON_PRETTY_PRINT);
        if ($jsonData === false) {
            error_log("Failed to encode cache data to JSON");
            return;
        }
        
        error_log("Attempting to save cache to: " . $this->cacheFile);
        $result = file_put_contents($this->cacheFile, $jsonData, LOCK_EX);
        
        if ($result === false) {
            error_log("Failed to write cache file: " . $this->cacheFile);
            $this->cacheEnabled = false;
        } else {
            error_log("Cache saved successfully: " . $this->cacheFile . " (" . $result . " bytes)");
        }
    }
    
    /**
     * Load all built-in templates from providers by scanning directory
     */
    private function loadBuiltInTemplates(): void
    {
        $this->providers = [];
        
        // Check if providers directory exists
        if (!is_dir($this->providersPath)) {
            throw new RuleFlowException("Providers directory not found: " . $this->providersPath);
        }
        
        // Scan for PHP files in providers directory
        $providerFiles = glob($this->providersPath . '/*.php');
        
        foreach ($providerFiles as $file) {
            try {
                // Include the file
                require_once $file;
                
                // Get class name from filename
                $className = basename($file, '.php');
                
                // Check if class exists and implements TemplateProviderInterface
                if (class_exists($className)) {
                    $reflection = new ReflectionClass($className);
                    
                    // Check if it implements TemplateProviderInterface
                    if ($reflection->implementsInterface('TemplateProviderInterface')) {
                        $provider = new $className();
                        $this->providers[] = $provider;
                        
                        // Store provider info in cache
                        $this->providerCache[] = [
                            'class' => $className,
                            'file' => $file,
                            'modified' => filemtime($file)
                        ];
                        
                        // Load templates from this provider
                        $providerTemplates = $provider->getTemplates();
                        $this->templates = array_merge($this->templates, $providerTemplates);
                    }
                }
            } catch (Exception $e) {
                // Log error but continue loading other providers
                error_log("Failed to load provider from file: $file. Error: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Reload all templates from providers
     */
    public function reloadTemplates(): void
    {
        $this->templates = [];
        $this->loadBuiltInTemplates();
        
        if ($this->cacheEnabled) {
            $this->saveToCache();
        }
    }
    
    /**
     * Clear cache and reload
     */
    public function clearCache(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
        $this->reloadTemplates();
    }
    
    /**
     * Get cache information
     */
    public function getCacheInfo(): array
    {
        $info = [
            'enabled' => $this->cacheEnabled,
            'file' => $this->cacheFile,
            'exists' => file_exists($this->cacheFile),
            'size' => file_exists($this->cacheFile) ? filesize($this->cacheFile) : 0,
            'modified' => file_exists($this->cacheFile) ? filemtime($this->cacheFile) : 0,
            'readable' => file_exists($this->cacheFile) ? is_readable($this->cacheFile) : false,
            'writable' => is_writable(dirname($this->cacheFile))
        ];
        
        if ($info['exists'] && $info['readable']) {
            $cacheContent = file_get_contents($this->cacheFile);
            $cacheData = json_decode($cacheContent, true);
            if ($cacheData) {
                $info['cached_at'] = $cacheData['cached_at'] ?? null;
                $info['cache_version'] = $cacheData['cache_version'] ?? 'unknown';
                $info['templates_count'] = count($cacheData['templates'] ?? []);
                $info['providers_count'] = count($cacheData['providers'] ?? []);
            }
        }
        
        return $info;
    }
    
    /**
     * Set custom cache file path
     */
    public function setCacheFile(string $cacheFile): void
    {
        $this->cacheFile = $cacheFile;
        $this->ensureCacheDirectory();
    }
    
    /**
     * Get loaded providers information
     */
    public function getLoadedProviders(): array
    {
        // If loaded from cache, return cached info
        if (!empty($this->providerCache) && empty($this->providers)) {
            return $this->providerCache;
        }
        
        $providerInfo = [];
        foreach ($this->providers as $provider) {
            $reflection = new ReflectionClass($provider);
            $providerInfo[] = [
                'class' => $reflection->getName(),
                'file' => $reflection->getFileName(),
                'templates_count' => count($provider->getTemplates())
            ];
        }
        return $providerInfo;
    }
    
    /**
     * Get available template names
     */
    public function getAvailableTemplates(): array
    {
        return array_keys($this->templates);
    }
    
    /**
     * Get template by name
     */
    public function getTemplate(string $name): array
    {
        if (!isset($this->templates[$name])) {
            throw new RuleFlowException("Template '$name' not found", [
                'available_templates' => $this->getAvailableTemplates()
            ]);
        }
        
        return $this->templates[$name];
    }
    
    /**
     * Get template with custom parameters
     */
    public function getTemplateWithParams(string $name, array $params = []): array
    {
        $template = $this->getTemplate($name);
        $result = $this->applyParameters($template, $params);
        
        // Add parameters to the result for testing/debugging
        $result['parameters'] = $params;
        
        return $result;
    }
    
    /**
     * Register custom template
     */
    public function registerTemplate(string $name, array $config, array $metadata = []): void
    {
        // Validate config before registering
        if (!$this->validateTemplate($config)) {
            throw new RuleFlowException("Template validation failed", [
                'template_name' => $name,
                'validation_error' => 'Template configuration does not meet requirements'
            ]);
        }
        
        $this->templates[$name] = [
            'config' => $config,
            'metadata' => array_merge([
                'name' => $name,
                'category' => 'custom',
                'description' => "Custom template: $name",
                'author' => 'User',
                'version' => '1.0.0'
            ], $metadata)
        ];
    }
    
    /**
     * Get templates by category
     */
    public function getTemplatesByCategory(string $category): array
    {
        $filtered = [];
        foreach ($this->templates as $name => $template) {
            if (($template['metadata']['category'] ?? 'general') === $category) {
                $filtered[$name] = $template;
            }
        }
        return $filtered;
    }
    
    /**
     * Validate template configuration
     */
    public function validateTemplate(array $config): bool
    {
        // Basic structure validation
        if (!isset($config['formulas']) || !is_array($config['formulas'])) {
            return false;
        }
        
        if (empty($config['formulas'])) {
            return false;
        }
        
        // Validate each formula has required fields
        foreach ($config['formulas'] as $formula) {
            if (!isset($formula['id']) || empty($formula['id'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Clone template with new name
     */
    public function cloneTemplate(string $originalName, string $newName): array
    {
        $original = $this->getTemplate($originalName);
        
        // Deep copy the template
        $cloned = [
            'config' => $original['config'],
            'metadata' => $original['metadata']
        ];
        
        // Update metadata
        $cloned['metadata']['name'] = $newName;
        $cloned['metadata']['description'] = "Cloned from {$originalName}";
        $cloned['metadata']['version'] = '1.0.0';
        
        // Register the cloned template
        $this->registerTemplate($newName, $cloned['config'], $cloned['metadata']);
        
        return $cloned;
    }
    
    /**
     * Modify template with path-based changes
     */
    public function modifyTemplate(string $name, array $modifications): array
    {
        $template = $this->getTemplate($name);
        
        foreach ($modifications as $path => $value) {
            $this->setNestedValue($template, $path, $value);
        }
        
        // Update the stored template
        $this->templates[$name] = $template;
        
        return $template;
    }
    
    /**
     * Get available categories
     */
    public function getAvailableCategories(): array
    {
        $categories = [];
        foreach ($this->templates as $template) {
            $category = $template['metadata']['category'] ?? 'general';
            if (!in_array($category, $categories)) {
                $categories[] = $category;
            }
        }
        return $categories;
    }
    
    /**
     * Get template counts per category
     */
    public function getTemplateCounts(): array
    {
        $counts = [];
        foreach ($this->templates as $template) {
            $category = $template['metadata']['category'] ?? 'general';
            $counts[$category] = ($counts[$category] ?? 0) + 1;
        }
        return $counts;
    }
    
    /**
     * Export template with version info (returns array)
     */
    public function exportTemplateData(string $name): array
    {
        $template = $this->getTemplate($name);
        return [
            'config' => $template['config'],
            'metadata' => $template['metadata'],
            'version' => '1.0.0',
            'exported_at' => date('c')
        ];
    }
    
    /**
     * Import template from data
     */
    public function importTemplateData(string $name, array $templateData): array
    {
        if (!isset($templateData['config']) || !isset($templateData['metadata'])) {
            throw new RuleFlowException("Invalid template data format", [
                'required_fields' => ['config', 'metadata']
            ]);
        }
        
        $this->registerTemplate($name, $templateData['config'], $templateData['metadata']);
        
        return $this->getTemplate($name);
    }
    
    /**
     * Get template metadata
     */
    public function getTemplateMetadata(string $name): array
    {
        $template = $this->getTemplate($name);
        return $template['metadata'];
    }
    
    /**
     * Search templates by keyword
     */
    public function searchTemplates(string $keyword): array
    {
        $results = [];
        $keyword = strtolower($keyword);
        
        foreach ($this->templates as $name => $template) {
            $metadata = $template['metadata'];
            
            if (
                strpos(strtolower($name), $keyword) !== false ||
                strpos(strtolower($metadata['name'] ?? ''), $keyword) !== false ||
                strpos(strtolower($metadata['description'] ?? ''), $keyword) !== false ||
                strpos(strtolower($metadata['category'] ?? ''), $keyword) !== false
            ) {
                $results[$name] = $template;
            }
        }
        
        return $results;
    }
    
    /**
     * Export template to JSON
     */
    public function exportTemplate(string $name): string
    {
        $template = $this->getTemplate($name);
        return json_encode($template, JSON_PRETTY_PRINT);
    }
    
    /**
     * Import template from JSON
     */
    public function importTemplate(string $json): void
    {
        $template = json_decode($json, true);
        
        if (!isset($template['metadata']['name'])) {
            throw new RuleFlowException("Template must have metadata.name field");
        }
        
        $name = strtolower(str_replace(' ', '_', $template['metadata']['name']));
        $this->registerTemplate($name, $template['config'], $template['metadata']);
    }
    
    /**
     * Apply parameters to template
     */
    private function applyParameters(array $template, array $params): array
    {
        if (empty($params)) {
            return $template;
        }
        
        $config = $template['config'];
        
        // Replace parameter placeholders in formulas
        $configStr = json_encode($config);
        foreach ($params as $key => $value) {
            $placeholder = "{{$key}}";
            $configStr = str_replace($placeholder, (string)$value, $configStr);
        }
        
        $template['config'] = json_decode($configStr, true);
        return $template;
    }
    
    /**
     * Set nested value using dot notation
     */
    private function setNestedValue(array &$array, string $path, $value): void
    {
        $keys = explode('.', $path);
        $current = &$array;
        
        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        
        $current = $value;
    }
}