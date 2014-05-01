<?php

declare(strict_types=1);

namespace PhilipRehberger\OperationResult;

/**
 * Result class for validation operations.
 *
 * Use this when validating data or templates, tracking both
 * hard errors and soft warnings.
 */
class ValidationResult extends Result
{
    public function __construct(
        bool $success,
        string $message = '',
        ?string $errorCode = null,
        public readonly array $errors = [],
        public readonly array $warnings = [],
    ) {
        parent::__construct($success, $message, $errorCode);
    }

    /**
     * Create a valid result with optional warnings.
     */
    public static function valid(array $warnings = []): static
    {
        return new static(
            success: true,
            message: empty($warnings) ? 'Validation passed' : 'Validation passed with warnings',
            warnings: $warnings
        );
    }

    /**
     * Create an invalid result with errors.
     */
    public static function invalid(array $errors, array $warnings = []): static
    {
        return new static(
            success: false,
            message: 'Validation failed',
            errorCode: 'VALIDATION_FAILED',
            errors: $errors,
            warnings: $warnings
        );
    }

    /**
     * Create a failure result.
     */
    public static function failure(string $message, ?string $errorCode = null): static
    {
        return new static(
            success: false,
            message: $message,
            errorCode: $errorCode
        );
    }

    /**
     * Check if the validation passed.
     */
    public function isValid(): bool
    {
        return $this->success;
    }

    /**
     * Get all errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get all warnings.
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    /**
     * Check if there are any warnings.
     */
    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
    }

    /**
     * Convert the result to an array.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'valid' => $this->success,
            'message' => $this->message,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
