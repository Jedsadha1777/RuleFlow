<?php

declare(strict_types=1);

/**
 * Custom exception class for RuleFlow with enhanced context
 */
class RuleFlowException extends Exception
{
    private array $context;
    private string $errorType;

    public function __construct(string $message, array $context = [], string $errorType = 'GENERAL_ERROR')
    {
        parent::__construct($message);
        $this->context = $context;
        $this->errorType = $errorType;
    }

    /**
     * Get additional context information
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get error type
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * Get formula ID if available
     */
    public function getFormulaId(): ?string
    {
        return $this->context['formula_id'] ?? null;
    }

    /**
     * Get suggestion if available
     */
    public function getSuggestion(): ?string
    {
        return $this->context['suggestion'] ?? null;
    }

    /**
     * Get validation errors if this is a validation exception
     */
    public function getValidationErrors(): array
    {
        return $this->context['validation_errors'] ?? [$this->getMessage()];
    }

    /**
     * Get available context keys
     */
    public function getAvailableContext(): array
    {
        return $this->context['available_context'] ?? [];
    }

    /**
     * Get missing input information
     */
    public function getMissingInput(): ?string
    {
        return $this->context['missing_input'] ?? null;
    }

    /**
     * Get function name if this is a function error
     */
    public function getFunctionName(): ?string
    {
        return $this->context['function_name'] ?? null;
    }

    /**
     * Get formula type if available
     */
    public function getFormulaType(): ?string
    {
        return $this->context['formula_type'] ?? null;
    }

    /**
     * Format error message with context for display
     */
    public function getFormattedMessage(): string
    {
        $message = $this->getMessage();
        
        if ($formulaId = $this->getFormulaId()) {
            $message = "Formula '{$formulaId}': {$message}";
        }
        
        if ($suggestion = $this->getSuggestion()) {
            $message .= "\nSuggestion: {$suggestion}";
        }
        
        if ($missingInput = $this->getMissingInput()) {
            $availableInputs = $this->getAvailableContext();
            if (!empty($availableInputs)) {
                $message .= "\nAvailable inputs: " . implode(', ', $availableInputs);
            }
        }
        
        return $message;
    }

    /**
     * Convert to array for API responses
     */
    public function toArray(): array
    {
        return [
            'error' => true,
            'type' => $this->errorType,
            'message' => $this->getMessage(),
            'formatted_message' => $this->getFormattedMessage(),
            'context' => $this->context,
            'formula_id' => $this->getFormulaId(),
            'suggestion' => $this->getSuggestion()
        ];
    }

    /**
     * Static factory methods for common error types
     */
    public static function missingInput(string $input, string $formulaId, array $availableInputs = []): self
    {
        return new self(
            "Missing required input: {$input}",
            [
                'missing_input' => $input,
                'formula_id' => $formulaId,
                'available_context' => $availableInputs,
                'suggestion' => "Add '{$input}' to your input data"
            ],
            'MISSING_INPUT'
        );
    }

    public static function invalidType(string $field, $currentValue, string $expectedType): self
    {
        return new self(
            "Invalid type for field '{$field}': expected {$expectedType}",
            [
                'field' => $field,
                'current_value' => $currentValue,
                'current_type' => gettype($currentValue),
                'expected_type' => $expectedType,
                'suggestion' => "Convert '{$field}' to {$expectedType}"
            ],
            'INVALID_TYPE'
        );
    }

    public static function divisionByZero(string $formulaId): self
    {
        return new self(
            "Division by zero",
            [
                'formula_id' => $formulaId,
                'suggestion' => "Check for zero values in denominators"
            ],
            'DIVISION_BY_ZERO'
        );
    }

    public static function unknownFunction(string $functionName, array $availableFunctions = []): self
    {
        return new self(
            "Unknown function: {$functionName}",
            [
                'function_name' => $functionName,
                'available_functions' => $availableFunctions,
                'suggestion' => "Use one of the available functions: " . implode(', ', array_slice($availableFunctions, 0, 5))
            ],
            'UNKNOWN_FUNCTION'
        );
    }

    public static function validationFailed(array $errors): self
    {
        return new self(
            "Configuration validation failed",
            [
                'validation_errors' => $errors,
                'suggestion' => "Fix the configuration errors and try again"
            ],
            'VALIDATION_ERROR'
        );
    }

    public static function circularDependency(string $formulaId): self
    {
        return new self(
            "Circular dependency detected",
            [
                'formula_id' => $formulaId,
                'suggestion' => "Review formula dependencies and remove circular references"
            ],
            'CIRCULAR_DEPENDENCY'
        );
    }

    public static function expressionError(string $expression, string $error): self
    {
        return new self(
            "Expression evaluation error: {$error}",
            [
                'expression' => $expression,
                'error' => $error,
                'suggestion' => "Check expression syntax and variable names"
            ],
            'EXPRESSION_ERROR'
        );
    }
}