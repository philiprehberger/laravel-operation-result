<?php

declare(strict_types=1);

namespace PhilipRehberger\OperationResult\Tests;

use PhilipRehberger\OperationResult\ValidationResult;
use PHPUnit\Framework\TestCase;

class ValidationResultTest extends TestCase
{
    public function test_valid_returns_success_with_default_message(): void
    {
        $result = ValidationResult::valid();

        $this->assertTrue($result->succeeded());
        $this->assertFalse($result->failed());
        $this->assertSame('Validation passed', $result->getMessage());
        $this->assertTrue($result->isValid());
    }

    public function test_valid_with_warnings_sets_appropriate_message(): void
    {
        $warnings = ['deprecated_field' => 'This field is deprecated.'];
        $result = ValidationResult::valid($warnings);

        $this->assertTrue($result->succeeded());
        $this->assertSame('Validation passed with warnings', $result->getMessage());
        $this->assertTrue($result->hasWarnings());
        $this->assertSame($warnings, $result->getWarnings());
    }

    public function test_valid_with_no_warnings_has_no_warnings(): void
    {
        $result = ValidationResult::valid();

        $this->assertFalse($result->hasWarnings());
        $this->assertSame([], $result->getWarnings());
    }

    public function test_invalid_returns_failed_result_with_errors(): void
    {
        $errors = ['name' => ['The name is required.']];
        $result = ValidationResult::invalid($errors);

        $this->assertFalse($result->succeeded());
        $this->assertTrue($result->failed());
        $this->assertFalse($result->isValid());
        $this->assertSame('Validation failed', $result->getMessage());
        $this->assertSame('VALIDATION_FAILED', $result->getErrorCode());
        $this->assertSame($errors, $result->getErrors());
    }

    public function test_invalid_with_both_errors_and_warnings(): void
    {
        $errors = ['email' => ['Invalid email.']];
        $warnings = ['phone' => 'Phone format is unusual.'];
        $result = ValidationResult::invalid($errors, $warnings);

        $this->assertTrue($result->hasErrors());
        $this->assertTrue($result->hasWarnings());
        $this->assertSame($errors, $result->getErrors());
        $this->assertSame($warnings, $result->getWarnings());
    }

    public function test_failure_returns_generic_failure(): void
    {
        $result = ValidationResult::failure('Template is corrupt', 'CORRUPT_TEMPLATE');

        $this->assertFalse($result->succeeded());
        $this->assertSame('Template is corrupt', $result->getMessage());
        $this->assertSame('CORRUPT_TEMPLATE', $result->getErrorCode());
    }

    public function test_failure_without_error_code(): void
    {
        $result = ValidationResult::failure('Unknown error');

        $this->assertNull($result->getErrorCode());
    }

    public function test_is_valid_reflects_success(): void
    {
        $valid = ValidationResult::valid();
        $invalid = ValidationResult::invalid(['field' => ['Error.']]);

        $this->assertTrue($valid->isValid());
        $this->assertFalse($invalid->isValid());
    }

    public function test_has_errors_true_when_errors_present(): void
    {
        $result = ValidationResult::invalid(['field' => ['Required.']]);

        $this->assertTrue($result->hasErrors());
    }

    public function test_has_errors_false_when_no_errors(): void
    {
        $result = ValidationResult::valid();

        $this->assertFalse($result->hasErrors());
        $this->assertSame([], $result->getErrors());
    }

    public function test_to_array_for_valid_result(): void
    {
        $result = ValidationResult::valid(['warning' => 'Something to note.']);
        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertTrue($array['valid']);
        $this->assertSame('Validation passed with warnings', $array['message']);
        $this->assertSame([], $array['errors']);
        $this->assertSame(['warning' => 'Something to note.'], $array['warnings']);
    }

    public function test_to_array_for_invalid_result(): void
    {
        $errors = ['name' => ['Required.']];
        $result = ValidationResult::invalid($errors);
        $array = $result->toArray();

        $this->assertFalse($array['success']);
        $this->assertFalse($array['valid']);
        $this->assertSame('Validation failed', $array['message']);
        $this->assertSame($errors, $array['errors']);
        $this->assertSame([], $array['warnings']);
    }

    public function test_to_array_does_not_include_error_code_key(): void
    {
        // ValidationResult::toArray() uses a custom format without the inherited error_code field
        $result = ValidationResult::invalid(['f' => ['e']]);
        $array = $result->toArray();

        $this->assertArrayNotHasKey('error_code', $array);
    }
}
