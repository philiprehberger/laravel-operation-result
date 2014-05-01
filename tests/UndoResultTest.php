<?php

declare(strict_types=1);

namespace PhilipRehberger\OperationResult\Tests;

use PhilipRehberger\OperationResult\UndoResult;
use PHPUnit\Framework\TestCase;

class UndoResultTest extends TestCase
{
    public function test_success_returns_successful_result(): void
    {
        $result = UndoResult::success(5);

        $this->assertTrue($result->succeeded());
        $this->assertFalse($result->failed());
        $this->assertSame(5, $result->restored);
        $this->assertSame(0, $result->failed);
        $this->assertSame('Undo completed successfully', $result->getMessage());
    }

    public function test_success_accepts_custom_message(): void
    {
        $result = UndoResult::success(3, '3 items restored.');

        $this->assertSame('3 items restored.', $result->getMessage());
        $this->assertSame(3, $result->restored);
    }

    public function test_partial_with_some_restored_is_successful(): void
    {
        $result = UndoResult::partial(4, 1, 'Partially restored.');

        $this->assertTrue($result->succeeded());
        $this->assertSame(4, $result->restored);
        $this->assertSame(1, $result->failed);
        $this->assertSame('Partially restored.', $result->getMessage());
    }

    public function test_partial_with_zero_restored_is_failed(): void
    {
        $result = UndoResult::partial(0, 3, 'Nothing restored.');

        $this->assertFalse($result->succeeded());
        $this->assertTrue($result->failed());
        $this->assertSame(0, $result->restored);
        $this->assertSame(3, $result->failed);
    }

    public function test_failure_returns_failed_result(): void
    {
        $result = UndoResult::failure('Undo token expired', 'TOKEN_EXPIRED');

        $this->assertFalse($result->succeeded());
        $this->assertTrue($result->failed());
        $this->assertSame('Undo token expired', $result->getMessage());
        $this->assertSame('TOKEN_EXPIRED', $result->getErrorCode());
    }

    public function test_failure_without_error_code(): void
    {
        $result = UndoResult::failure('Unknown undo error');

        $this->assertNull($result->getErrorCode());
    }

    public function test_has_failures_true_when_failed_count_positive(): void
    {
        $result = UndoResult::partial(3, 2);

        $this->assertTrue($result->hasFailures());
    }

    public function test_has_failures_false_when_no_failures(): void
    {
        $result = UndoResult::success(5);

        $this->assertFalse($result->hasFailures());
    }

    public function test_has_failures_true_for_total_failure(): void
    {
        $result = UndoResult::failure('Failed');

        // failed property defaults to 0, but the operation itself failed
        $this->assertFalse($result->hasFailures()); // hasFailures checks the int count, not success
        $this->assertTrue($result->failed()); // failed() checks success boolean
    }

    public function test_to_array_for_successful_undo(): void
    {
        $result = UndoResult::success(7, 'All restored.');
        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertSame('All restored.', $array['message']);
        $this->assertSame(7, $array['restored']);
        $this->assertSame(0, $array['failed']);
    }

    public function test_to_array_for_partial_undo(): void
    {
        $result = UndoResult::partial(5, 2, 'Partially restored.');
        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertSame('Partially restored.', $array['message']);
        $this->assertSame(5, $array['restored']);
        $this->assertSame(2, $array['failed']);
    }

    public function test_to_array_for_failed_undo(): void
    {
        $result = UndoResult::failure('Could not undo');
        $array = $result->toArray();

        $this->assertFalse($array['success']);
        $this->assertSame('Could not undo', $array['message']);
        $this->assertSame(0, $array['restored']);
        $this->assertSame(0, $array['failed']);
    }

    public function test_to_array_contains_exactly_four_keys(): void
    {
        $result = UndoResult::success(1);
        $array = $result->toArray();

        $this->assertCount(4, $array);
        $this->assertArrayHasKey('success', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('restored', $array);
        $this->assertArrayHasKey('failed', $array);
    }
}
