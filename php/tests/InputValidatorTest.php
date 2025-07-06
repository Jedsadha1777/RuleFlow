<?php

require_once __DIR__ . '/../src/InputValidator.php';
require_once __DIR__ . '/../src/RuleFlowException.php';

class InputValidatorTest
{
    private InputValidator $validator;
    
    public function setUp(): void
    {
        $this->validator = new InputValidator();
    }
    
    /**
     * Test boundary validation
     */
    public function testBoundaryValidation(): void
    {
        $inputs = [
            'age' => 150,        // Too high
            'score' => -5,       // Too low
            'price' => 50        // Within range
        ];
        
        $constraints = [
            'age' => ['min' => 0, 'max' => 120],
            'score' => ['min' => 0, 'max' => 100],
            'price' => ['min' => 10, 'max' => 100]
        ];
        
        $errors = $this->validator->validateBoundaries($inputs, $constraints);
        
        // Should have 2 errors (age and score)
        $this->assertEquals(2, count($errors));
        
        // Check age error
        $this->assertEquals('age', $errors[0]['field']);
        $this->assertEquals('BOUNDARY_ERROR', $errors[0]['type']);
        $this->assertStringContains('must be <=', $errors[0]['message']);
        
        // Check score error
        $this->assertEquals('score', $errors[1]['field']);
        $this->assertEquals('BOUNDARY_ERROR', $errors[1]['type']);
        $this->assertStringContains('must be >=', $errors[1]['message']);
        
        echo "✅ Boundary validation passed\n";
    }
    
    /**
     * Test string length validation
     */
    public function testStringLengthValidation(): void
    {
        $inputs = [
            'name' => 'Jo',           // Too short
            'description' => 'Valid', // Valid length
            'code' => ''              // Empty
        ];
        
        $constraints = [
            'name' => ['minLength' => 3],
            'description' => ['minLength' => 3],
            'code' => ['minLength' => 2]
        ];
        
        $errors = $this->validator->validateBoundaries($inputs, $constraints);
        
        // Should have 2 errors (name and code)
        $this->assertEquals(2, count($errors));
        $this->assertEquals('name', $errors[0]['field']);
        $this->assertEquals('LENGTH_ERROR', $errors[0]['type']);
        
        echo "✅ String length validation passed\n";
    }
    
    /**
     * Test enum validation
     */
    public function testEnumValidation(): void
    {
        $inputs = [
            'status' => 'pending',     // Valid
            'priority' => 'urgent',    // Invalid
            'category' => 'business'   // Valid
        ];
        
        $constraints = [
            'status' => ['enum' => ['active', 'inactive', 'pending']],
            'priority' => ['enum' => ['low', 'medium', 'high']],
            'category' => ['enum' => ['personal', 'business', 'other']]
        ];
        
        $errors = $this->validator->validateBoundaries($inputs, $constraints);
        
        // Should have 1 error (priority)
        $this->assertEquals(1, count($errors));
        $this->assertEquals('priority', $errors[0]['field']);
        $this->assertEquals('ENUM_ERROR', $errors[0]['type']);
        $this->assertStringContains('must be one of', $errors[0]['message']);
        
        echo "✅ Enum validation passed\n";
    }
    
    /**
     * Test pattern validation
     */
    public function testPatternValidation(): void
    {
        $inputs = [
            'email' => 'invalid-email',        // Invalid email
            'phone' => '0812345678',          // Valid phone
            'postal_code' => '12AB3'          // Invalid postal code
        ];
        
        $constraints = [
            'email' => ['pattern' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/'],
            'phone' => ['pattern' => '/^[0-9]{10}$/'],
            'postal_code' => ['pattern' => '/^[0-9]{5}$/']
        ];
        
        $errors = $this->validator->validateBoundaries($inputs, $constraints);
        
        // Should have 2 errors (email and postal_code)
        $this->assertEquals(2, count($errors));
        $this->assertEquals('email', $errors[0]['field']);
        $this->assertEquals('PATTERN_ERROR', $errors[0]['type']);
        
        echo "✅ Pattern validation passed\n";
    }
    
    /**
     * Test advanced type conversion
     */
    public function testAdvancedTypeConversion(): void
    {
        $inputs = [
            'age' => '25',
            'price' => '19.99',
            'active' => 'true',
            'birthday' => '1998-05-15',
            'discount' => '15%',
            'salary' => '$50,000.00',
            'email' => 'USER@EXAMPLE.COM'
        ];
        
        $typeMap = [
            'age' => 'integer',
            'price' => 'float',
            'active' => 'boolean',
            'birthday' => 'date',
            'discount' => 'percentage',
            'salary' => 'currency',
            'email' => 'email'
        ];
        
        $result = $this->validator->convertAdvancedTypes($inputs, $typeMap);
        
        // Check conversions
        $this->assertEquals(25, $result['age']);
        $this->assertEquals(19.99, $result['price']);
        $this->assertEquals(true, $result['active']);
        $this->assertEquals(15.0, $result['discount']);
        $this->assertEquals(50000.0, $result['salary']);
        $this->assertEquals('user@example.com', $result['email']);
        $this->assertInstanceOf(DateTime::class, $result['birthday']);
        
        echo "✅ Advanced type conversion passed\n";
    }
    
    /**
     * Test boolean conversion
     */
    public function testBooleanConversion(): void
    {
        $testCases = [
            'true' => true,
            'false' => false,
            '1' => true,
            '0' => false,
            'yes' => true,
            'no' => false,
            'on' => true,
            'off' => false,
            'enabled' => true,
            'disabled' => false
        ];
        
        foreach ($testCases as $input => $expected) {
            $result = $this->validator->convertAdvancedTypes(
                ['test' => $input],
                ['test' => 'boolean']
            );
            $this->assertEquals($expected, $result['test'], "Failed for input: $input");
        }
        
        echo "✅ Boolean conversion passed\n";
    }
    
    /**
     * Test auto type detection
     */
    public function testAutoTypeDetection(): void
    {
        $inputs = [
            'integer_value' => '123',
            'float_value' => '45.67',
            'boolean_true' => 'true',
            'boolean_false' => 'false',
            'string_value' => 'hello world'
        ];
        
        $typeMap = [
            'integer_value' => 'auto',
            'float_value' => 'auto',
            'boolean_true' => 'auto',
            'boolean_false' => 'auto',
            'string_value' => 'auto'
        ];
        
        $result = $this->validator->convertAdvancedTypes($inputs, $typeMap);
        
        $this->assertEquals(123, $result['integer_value']);
        $this->assertEquals(45.67, $result['float_value']);
        $this->assertEquals(true, $result['boolean_true']);
        $this->assertEquals(false, $result['boolean_false']);
        $this->assertEquals('hello world', $result['string_value']);
        
        echo "✅ Auto type detection passed\n";
    }
    
    /**
     * Test applying default values
     */
    public function testApplyDefaults(): void
    {
        $inputs = [
            'name' => 'John',
            'age' => 30,
            'email' => null,
            'active' => ''
        ];
        
        $defaults = [
            'email' => 'no-email@example.com',
            'active' => true,
            'role' => 'user',
            'created_at' => '2024-01-01'
        ];
        
        $result = $this->validator->applyDefaults($inputs, $defaults);
        
        // Should keep existing values
        $this->assertEquals('John', $result['name']);
        $this->assertEquals(30, $result['age']);
        
        // Should apply defaults for null/empty values
        $this->assertEquals('no-email@example.com', $result['email']);
        $this->assertEquals(true, $result['active']);
        
        // Should add missing fields
        $this->assertEquals('user', $result['role']);
        $this->assertEquals('2024-01-01', $result['created_at']);
        
        echo "✅ Apply defaults passed\n";
    }
    
    /**
     * Test input sanitization
     */
    public function testInputSanitization(): void
    {
        $inputs = [
            'name' => '  John Doe  ',
            'email' => 'JOHN@EXAMPLE.COM',
            'bio' => '<script>alert("xss")</script>Hello World',
            'title' => 'mr. smith'
        ];
        
        $rules = [
            'name' => ['trim'],
            'email' => ['trim', 'lowercase'],
            'bio' => ['trim', 'strip_tags'],
            'title' => ['trim', 'uppercase']
        ];
        
        $result = $this->validator->sanitizeInputs($inputs, $rules);
        
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
        $this->assertEquals('Hello World', $result['bio']);
        $this->assertEquals('MR. SMITH', $result['title']);
        
        echo "✅ Input sanitization passed\n";
    }
    
    /**
     * Test error handling for invalid conversions
     */
    public function testInvalidConversions(): void
    {
        $inputs = [
            'age' => 'not-a-number',
            'price' => 'invalid-float',
            'date' => 'invalid-date-format'
        ];
        
        $typeMap = [
            'age' => 'integer',
            'price' => 'float',
            'date' => 'date'
        ];
        
        // Should throw exception for invalid conversion
        try {
            $this->validator->convertAdvancedTypes($inputs, $typeMap);
            $this->fail('Should throw exception for invalid conversion');
        } catch (RuleFlowException $e) {
            $this->assertStringContains('Type conversion failed', $e->getMessage());
        }
        
        echo "✅ Invalid conversion error handling passed\n";
    }
    
    /**
     * Test email validation
     */
    public function testEmailValidation(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.th',
            'user+tag@example.org'
        ];
        
        $invalidEmails = [
            'invalid-email',
            '@missing-local.com',
            'missing-at-sign.com'
        ];
        
        // Test valid emails
        foreach ($validEmails as $email) {
            $result = $this->validator->convertAdvancedTypes(
                ['email' => $email],
                ['email' => 'email']
            );
            $this->assertEquals(strtolower($email), $result['email']);
        }
        
        // Test invalid emails
        foreach ($invalidEmails as $email) {
            try {
                $this->validator->convertAdvancedTypes(
                    ['email' => $email],
                    ['email' => 'email']
                );
                $this->fail("Should fail for invalid email: $email");
            } catch (Exception $e) {
                $this->assertStringContains('Invalid email format', $e->getMessage());
            }
        }
        
        echo "✅ Email validation passed\n";
    }
    
    /**
     * Test phone number formatting
     */
    public function testPhoneFormatting(): void
    {
        $phoneNumbers = [
            '081-234-5678' => '0812345678',
            '(02) 123-4567' => '021234567',
            '+66 81 234 5678' => '66812345678',
            '081.234.5678' => '0812345678'
        ];
        
        foreach ($phoneNumbers as $input => $expected) {
            $result = $this->validator->convertAdvancedTypes(
                ['phone' => $input],
                ['phone' => 'phone']
            );
            $this->assertEquals($expected, $result['phone']);
        }
        
        echo "✅ Phone formatting passed\n";
    }
    
    /**
     * Test currency parsing
     */
    public function testCurrencyParsing(): void
    {
        $currencies = [
            '$1,000.50' => 1000.50,
            '฿25,000' => 25000.0,
            '€1.234,56' => 1234.56,
            '(500.00)' => 500.0
        ];
        
        foreach ($currencies as $input => $expected) {
            $result = $this->validator->convertAdvancedTypes(
                ['amount' => $input],
                ['amount' => 'currency']
            );
            $this->assertEquals($expected, $result['amount']);
        }
        
        echo "✅ Currency parsing passed\n";
    }
    
    /**
     * Test percentage parsing
     */
    public function testPercentageParsing(): void
    {
        $percentages = [
            '50%' => 50.0,
            '12.5%' => 12.5,
            '100%' => 100.0,
            '0.5%' => 0.5
        ];
        
        foreach ($percentages as $input => $expected) {
            $result = $this->validator->convertAdvancedTypes(
                ['rate' => $input],
                ['rate' => 'percentage']
            );
            $this->assertEquals($expected, $result['rate']);
        }
        
        echo "✅ Percentage parsing passed\n";
    }
    
    // Helper assertion methods
    private function assertEquals($expected, $actual, string $message = ''): void
    {
        if (is_float($expected) || is_float($actual)) {
            if (abs((float)$expected - (float)$actual) > 0.01) {
                throw new Exception("Assertion failed: Expected $expected, got $actual. $message");
            }
        } else {
            if ($expected !== $actual) {
                throw new Exception("Assertion failed: Expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ". $message");
            }
        }
    }
    
    private function assertInstanceOf(string $expected, $actual): void
    {
        if (!($actual instanceof $expected)) {
            throw new Exception("Assertion failed: Expected instance of $expected, got " . get_class($actual));
        }
    }
    
    private function assertStringContains(string $needle, string $haystack): void
    {
        if (strpos($haystack, $needle) === false) {
            throw new Exception("Assertion failed: '$needle' not found in '$haystack'");
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
        echo "🧪 Running InputValidator Tests...\n\n";
        
        $this->setUp();
        
        try {
            $this->testBoundaryValidation();
            $this->testStringLengthValidation();
            $this->testEnumValidation();
            $this->testPatternValidation();
            $this->testAdvancedTypeConversion();
            $this->testBooleanConversion();
            $this->testAutoTypeDetection();
            $this->testApplyDefaults();
            $this->testInputSanitization();
            $this->testInvalidConversions();
            $this->testEmailValidation();
            $this->testPhoneFormatting();
            $this->testCurrencyParsing();
            $this->testPercentageParsing();
            
            echo "\n🎉 All InputValidator tests passed!\n\n";
        } catch (Exception $e) {
            echo "\n❌ Test failed: " . $e->getMessage() . "\n\n";
            throw $e;
        }
    }
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new InputValidatorTest();
    $test->runAllTests();
}