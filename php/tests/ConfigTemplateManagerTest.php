<?php

require_once __DIR__ . '/../src/Templates/ConfigTemplateManager.php';
require_once __DIR__ . '/../src/RuleFlowException.php';

class ConfigTemplateManagerTest
{
    private ConfigTemplateManager $templateManager;
    
    public function setUp(): void
    {
        $this->templateManager = new ConfigTemplateManager();
    }
    
    /**
     * Test getting available templates
     */
    public function testGetAvailableTemplates(): void
    {
        $templates = $this->templateManager->getAvailableTemplates();
        $this->assertNotEmpty($templates);
        $this->assertContains('loan_application', $templates);
        $this->assertContains('bmi_health_assessment', $templates);
        
        echo "✅ Get available templates passed\n";
    }
    
    /**
     * Test getting specific template
     */
    public function testGetSpecificTemplate(): void
    {
        $loanTemplate = $this->templateManager->getTemplate('loan_application');
        $this->assertArrayHasKey('config', $loanTemplate);
        $this->assertArrayHasKey('metadata', $loanTemplate);
        $this->assertArrayHasKey('formulas', $loanTemplate['config']);
        
        // Test template structure
        $config = $loanTemplate['config'];
        $this->assertIsArray($config['formulas']);
        $this->assertNotEmpty($config['formulas']);
        
        echo "✅ Get specific template passed\n";
    }
    
    /**
     * Test template metadata
     */
    public function testTemplateMetadata(): void
    {
        $metadata = $this->templateManager->getTemplateMetadata('loan_application');
        $this->assertEquals('financial', $metadata['category']);
        $this->assertArrayHasKey('inputs', $metadata);
        $this->assertArrayHasKey('outputs', $metadata);
        $this->assertArrayHasKey('name', $metadata);
        $this->assertArrayHasKey('description', $metadata);
                
        // Test BMI template metadata
        $bmiMetadata = $this->templateManager->getTemplateMetadata('bmi_health_assessment');
        $this->assertEquals('healthcare', $bmiMetadata['category']);
        
        echo "✅ Template metadata passed\n";
    }
    
    /**
     * Test getting templates by category
     */
    public function testGetTemplatesByCategory(): void
    {
        $financialTemplates = $this->templateManager->getTemplatesByCategory('financial');
        $this->assertNotEmpty($financialTemplates);
        $this->assertArrayHasKey('loan_application', $financialTemplates);
        
        $healthTemplates = $this->templateManager->getTemplatesByCategory('healthcare');
        $this->assertNotEmpty($healthTemplates);
        $this->assertArrayHasKey('bmi_health_assessment', $healthTemplates);
        
        // Test non-existent category
        $emptyCategory = $this->templateManager->getTemplatesByCategory('nonexistent');
        $this->assertEmpty($emptyCategory);
        
        echo "✅ Get templates by category passed\n";
    }
    
    /**
     * Test search functionality
     */
    public function testSearchTemplates(): void
    {
        // Search for loan templates
        $loanResults = $this->templateManager->searchTemplates('loan');
        $this->assertNotEmpty($loanResults);
        $this->assertArrayHasKey('loan_application', $loanResults);
        
        // Search for BMI templates
        $bmiResults = $this->templateManager->searchTemplates('bmi');
        $this->assertNotEmpty($bmiResults);
        
        // Search for health templates
        $healthResults = $this->templateManager->searchTemplates('health');
        $this->assertNotEmpty($healthResults);
        
        // Search with no results
        $noResults = $this->templateManager->searchTemplates('nonexistent_keyword');
        $this->assertEmpty($noResults);
        
        echo "✅ Search templates passed\n";
    }
    
    /**
     * Test custom template registration
     */
    public function testCustomTemplateRegistration(): void
    {
        $customConfig = [
            'formulas' => [
                [
                    'id' => 'test_calc',
                    'formula' => 'a + b',
                    'inputs' => ['a', 'b']
                ]
            ]
        ];
        
        $customMetadata = [
            'name' => 'Custom Test Template',
            'description' => 'A simple test template',
            'category' => 'test',
            'inputs' => ['a', 'b'],
            'outputs' => ['test_calc'],
            'author' => 'User',
            'version' => '1.0.0',
        ];
        
        // Register template
        $this->templateManager->registerTemplate('custom_test', $customConfig, $customMetadata);
        
        // Verify registration
        $customTemplate = $this->templateManager->getTemplate('custom_test');
        $this->assertEquals($customConfig, $customTemplate['config']);
        $this->assertEquals($customMetadata, $customTemplate['metadata']);
        
        // Check it appears in available templates
        $templates = $this->templateManager->getAvailableTemplates();
        $this->assertContains('custom_test', $templates);
        
        // Check it appears in category
        $testTemplates = $this->templateManager->getTemplatesByCategory('test');
        $this->assertArrayHasKey('custom_test', $testTemplates);
        
        echo "✅ Custom template registration passed\n";
    }
    
    /**
     * Test template with parameters
     */
    public function testTemplateWithParams(): void
    {
        $customParams = ['min_credit_score' => 600, 'max_loan_amount' => 500000];
        $customTemplate = $this->templateManager->getTemplateWithParams('loan_application', $customParams);
        
        $this->assertIsArray($customTemplate);
        $this->assertArrayHasKey('config', $customTemplate);
        $this->assertArrayHasKey('metadata', $customTemplate);
        $this->assertArrayHasKey('parameters', $customTemplate);
        $this->assertEquals($customParams, $customTemplate['parameters']);
        
        echo "✅ Template with parameters passed\n";
    }
    
    /**
     * Test template validation
     */
    public function testTemplateValidation(): void
    {
        // Test valid template
        $validConfig = [
            'formulas' => [
                [
                    'id' => 'valid_calc',
                    'formula' => 'x + y',
                    'inputs' => ['x', 'y']
                ]
            ]
        ];
        
        $isValid = $this->templateManager->validateTemplate($validConfig);
        $this->assertTrue($isValid);
        
        // Test invalid template
        $invalidConfig = [
            'formulas' => [
                [
                    // Missing 'id' field
                    'formula' => 'x + y',
                    'inputs' => ['x', 'y']
                ]
            ]
        ];
        
        $isInvalid = $this->templateManager->validateTemplate($invalidConfig);
        $this->assertFalse($isInvalid);
        
        echo "✅ Template validation passed\n";
    }
    
    /**
     * Test template cloning and modification
     */
    public function testTemplateCloning(): void
    {
        // Clone existing template
        $clonedTemplate = $this->templateManager->cloneTemplate('loan_application', 'custom_loan');
        
        $this->assertIsArray($clonedTemplate);
        $this->assertArrayHasKey('config', $clonedTemplate);
        
        // Verify it's registered as new template
        $templates = $this->templateManager->getAvailableTemplates();
        $this->assertContains('custom_loan', $templates);
        
        // Modify cloned template
        $modifications = [
            'formulas.0.formula' => 'annual_income / 10' // Change first formula
        ];
        
        $modifiedTemplate = $this->templateManager->modifyTemplate('custom_loan', $modifications);
        $this->assertIsArray($modifiedTemplate);
        
        echo "✅ Template cloning passed\n";
    }
    
    /**
     * Test error handling
     */
    public function testErrorHandling(): void
    {
        // Test getting non-existent template
        try {
            $this->templateManager->getTemplate('nonexistent_template');
            $this->fail('Should throw exception for nonexistent template');
        } catch (RuleFlowException $e) {
            $this->assertStringContains('Template', $e->getMessage());
        }
        
        // Test registering template with invalid config
        try {
            $invalidConfig = ['invalid' => 'structure'];
            $this->templateManager->registerTemplate('invalid_test', $invalidConfig, []);
            $this->fail('Should throw exception for invalid config');
        } catch (RuleFlowException $e) {
            $this->assertStringContains('validation', $e->getMessage());
        }
        
        // Test getting metadata for non-existent template
        try {
            $this->templateManager->getTemplateMetadata('nonexistent');
            $this->fail('Should throw exception for nonexistent template metadata');
        } catch (RuleFlowException $e) {
            $this->assertStringContains('Template', $e->getMessage());
        }
        
        echo "✅ Error handling passed\n";
    }
    
    /**
     * Test template categories and organization
     */
    public function testTemplateOrganization(): void
    {
        // Get all categories
        $categories = $this->templateManager->getAvailableCategories();
        $this->assertNotEmpty($categories);
        $this->assertContains('financial', $categories);
        $this->assertContains('healthcare', $categories);
        
        // Test template counts per category
        $categoryCounts = $this->templateManager->getTemplateCounts();
        $this->assertIsArray($categoryCounts);
        $this->assertArrayHasKey('financial', $categoryCounts);
        $this->assertGreaterThan(0, $categoryCounts['financial']);
        
        echo "✅ Template organization passed\n";
    }
    
    /**
     * Test template import/export
     */

    private function assertIsString($value): void
    {
        if (!is_string($value)) {
            throw new Exception("Assertion failed: Expected string, got " . gettype($value));
        }
    }

    public function testTemplateImportExport(): void
    {
        // Export template as array data (ใช้ exportTemplateData แทน exportTemplate)
        $exportedData = $this->templateManager->exportTemplateData('loan_application');
        $this->assertIsArray($exportedData);
        $this->assertArrayHasKey('config', $exportedData);
        $this->assertArrayHasKey('metadata', $exportedData);
        $this->assertArrayHasKey('version', $exportedData);
        
        // Import template from array data (ใช้ importTemplateData แทน importTemplate)
        $importedTemplate = $this->templateManager->importTemplateData('imported_loan', $exportedData);
        $this->assertIsArray($importedTemplate);
        
        // Verify imported template exists
        $templates = $this->templateManager->getAvailableTemplates();
        $this->assertContains('imported_loan', $templates);
        
        // Test JSON export/import as well
        $jsonExport = $this->templateManager->exportTemplate('loan_application');
        $this->assertIsString($jsonExport); // Should be JSON string
        
        // Test JSON import
        $this->templateManager->importTemplate($jsonExport); // This should work with JSON string
        
        echo "✅ Template import/export passed\n";
    }
    
    /**
     * Test real-world template usage scenarios
     */
    public function testRealWorldScenarios(): void
    {
        // Scenario 1: User browses templates by category
        $financialTemplates = $this->templateManager->getTemplatesByCategory('financial');
        $this->assertNotEmpty($financialTemplates);
        
        foreach ($financialTemplates as $templateId => $template) {
            $metadata = $this->templateManager->getTemplateMetadata($templateId);
            $this->assertEquals('financial', $metadata['category']);
        }
        
        // Scenario 2: User searches for specific functionality
        $loanTemplates = $this->templateManager->searchTemplates('loan');
        $this->assertNotEmpty($loanTemplates);
        
        // Scenario 3: User customizes template for their use case
        $originalTemplate = $this->templateManager->getTemplate('loan_application');
        $customParams = [
            'min_credit_score' => 650,
            'max_debt_ratio' => 35
        ];
        
        $customizedTemplate = $this->templateManager->getTemplateWithParams('loan_application', $customParams);
        $this->assertArrayHasKey('parameters', $customizedTemplate);
        
        // Scenario 4: Developer creates custom template based on existing one
        $clonedTemplate = $this->templateManager->cloneTemplate('loan_application', 'mortgage_application');
        $modifications = [
            'metadata.name' => 'Mortgage Application Template',
            'metadata.description' => 'Template for mortgage loan applications'
        ];
        
        $modifiedTemplate = $this->templateManager->modifyTemplate('mortgage_application', $modifications);
        $this->assertIsArray($modifiedTemplate);
        
        echo "✅ Real-world scenarios passed\n";
    }
    
    // Helper assertion methods
    private function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected != $actual) {
            throw new Exception("Assertion failed: Expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ". $message");
        }
    }
    
    private function assertNotEmpty($value): void
    {
        if (empty($value)) {
            throw new Exception("Assertion failed: Value should not be empty");
        }
    }
    
    private function assertEmpty($value): void
    {
        if (!empty($value)) {
            throw new Exception("Assertion failed: Value should be empty");
        }
    }
    
    private function assertContains($needle, array $haystack): void
    {
        if (!in_array($needle, $haystack, true)) {
            throw new Exception("Assertion failed: '$needle' not found in array");
        }
    }
    
    private function assertArrayHasKey($key, array $array): void
    {
        if (!array_key_exists($key, $array)) {
            throw new Exception("Assertion failed: Key '$key' not found in array");
        }
    }
    
    private function assertStringContains(string $needle, string $haystack): void
    {
        if (strpos($haystack, $needle) === false) {
            throw new Exception("Assertion failed: '$needle' not found in '$haystack'");
        }
    }
    
    private function assertTrue(bool $condition, string $message = ''): void
    {
        if (!$condition) {
            throw new Exception("Assertion failed: Expected true. $message");
        }
    }
    
    private function assertFalse(bool $condition, string $message = ''): void
    {
        if ($condition) {
            throw new Exception("Assertion failed: Expected false. $message");
        }
    }
    
    private function assertIsArray($value): void
    {
        if (!is_array($value)) {
            throw new Exception("Assertion failed: Expected array, got " . gettype($value));
        }
    }
    
    private function assertGreaterThan($expected, $actual): void
    {
        if ($actual <= $expected) {
            throw new Exception("Assertion failed: $actual should be greater than $expected");
        }
    }
    
    private function fail(string $message): void
    {
        throw new Exception("Test failed: $message");
    }
    
    /**
     * Run all tests
     */
    public function runAllTests(): void
    {
        echo "🧪 Running ConfigTemplateManager Tests...\n\n";
        
        $this->setUp();
        
        try {
            $this->testGetAvailableTemplates();
            $this->testGetSpecificTemplate();
            $this->testTemplateMetadata();
            $this->testGetTemplatesByCategory();
            $this->testSearchTemplates();
            $this->testCustomTemplateRegistration();
            $this->testTemplateWithParams();
            $this->testTemplateValidation();
            $this->testTemplateCloning();
            $this->testErrorHandling();
            $this->testTemplateOrganization();
            $this->testTemplateImportExport();
            $this->testRealWorldScenarios();
            
            echo "\n🎉 All ConfigTemplateManager tests passed!\n\n";
        } catch (Exception $e) {
            echo "\n❌ Test failed: " . $e->getMessage() . "\n\n";
            throw $e;
        }
    }
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new ConfigTemplateManagerTest();
    $test->runAllTests();
}