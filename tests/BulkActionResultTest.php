<?php

declare(strict_types=1);

namespace PhilipRehberger\OperationResult\Tests;

use PhilipRehberger\OperationResult\BulkActionResult;
use PHPUnit\Framework\TestCase;

class BulkActionResultTest extends TestCase
{
    public function test_success_returns_successful_result(): void
    {
        $result = BulkActionResult::success(10, 'All items processed.');

        $this->assertTrue($result->succeeded());
        $this->assertFalse($result->failed());
        $this->assertSame(10, $result->processed);
        $this->assertSame(0, $result->failed);
        $this->assertSame('All items processed.', $result->getMessage());
    }

    public function test_success_with_details_and_undo_token(): void
    {
        $details = [
            ['id' => 1, 'success' => true],
            ['id' => 2, 'success' => true],
        ];

        $result = BulkActionResult::success(
            processed: 2,
            message: 'Done',
            details: $details,
            undoToken: 'abc-token-123',
            undoExpiresAt: '2026-03-05T12:00:00Z'
        );

        $this->assertTrue($result->succeeded());
        $this->assertSame($details, $result->details);
        $this->assertSame('abc-token-123', $result->undoToken);
        $this->assertSame('2026-03-05T12:00:00Z', $result->undoExpiresAt);
    }

    public function test_partial_with_some_processed_is_successful(): void
    {
        $result = BulkActionResult::partial(8, 2, 'Partially completed.');

        $this->assertTrue($result->succeeded());
        $this->assertSame(8, $result->processed);
        $this->assertSame(2, $result->failed);
    }

    public function test_partial_with_zero_processed_is_failed(): void
    {
        $result = BulkActionResult::partial(0, 5, 'All failed.');

        $this->assertFalse($result->succeeded());
        $this->assertSame(0, $result->processed);
        $this->assertSame(5, $result->failed);
    }

    public function test_failure_returns_failed_result(): void
    {
        $result = BulkActionResult::failure('Bulk operation failed', 'BULK_ERROR');

        $this->assertFalse($result->succeeded());
        $this->assertTrue($result->failed());
        $this->assertSame('Bulk operation failed', $result->getMessage());
        $this->assertSame('BULK_ERROR', $result->getErrorCode());
    }

    public function test_failure_with_details(): void
    {
        $details = [['id' => 3, 'error' => 'not found']];
        $result = BulkActionResult::failure('Failed', null, $details);

        $this->assertSame($details, $result->details);
    }

    public function test_has_failures_true_when_failed_count_positive(): void
    {
        $result = BulkActionResult::partial(5, 3);

        $this->assertTrue($result->hasFailures());
    }

    public function test_has_failures_false_when_no_failures(): void
    {
        $result = BulkActionResult::success(5);

        $this->assertFalse($result->hasFailures());
    }

    public function test_is_complete_true_when_no_failures_and_processed_positive(): void
    {
        $result = BulkActionResult::success(5);

        $this->assertTrue($result->isComplete());
    }

    public function test_is_complete_false_when_there_are_failures(): void
    {
        $result = BulkActionResult::partial(5, 2);

        $this->assertFalse($result->isComplete());
    }

    public function test_is_complete_false_when_processed_is_zero(): void
    {
        $result = BulkActionResult::success(0);

        $this->assertFalse($result->isComplete());
    }

    public function test_get_failed_ids_returns_ids_where_success_is_false(): void
    {
        $details = [
            ['id' => 1, 'success' => true],
            ['id' => 2, 'success' => false],
            ['id' => 3, 'success' => false],
            ['id' => 4, 'success' => true],
        ];

        $result = BulkActionResult::partial(2, 2, '', $details);

        $this->assertSame([2, 3], $result->getFailedIds());
    }

    public function test_get_success_ids_returns_ids_where_success_is_true(): void
    {
        $details = [
            ['id' => 1, 'success' => true],
            ['id' => 2, 'success' => false],
            ['id' => 3, 'success' => true],
        ];

        $result = BulkActionResult::partial(2, 1, '', $details);

        $this->assertSame([1, 3], $result->getSuccessIds());
    }

    public function test_get_failed_ids_returns_empty_array_when_no_details(): void
    {
        $result = BulkActionResult::success(5);

        $this->assertSame([], $result->getFailedIds());
    }

    public function test_can_undo_true_when_undo_token_set(): void
    {
        $result = BulkActionResult::success(5, '', [], 'token-xyz');

        $this->assertTrue($result->canUndo());
    }

    public function test_can_undo_false_when_no_undo_token(): void
    {
        $result = BulkActionResult::success(5);

        $this->assertFalse($result->canUndo());
    }

    public function test_to_array_contains_all_fields(): void
    {
        $details = [['id' => 1, 'success' => true]];
        $result = BulkActionResult::success(1, 'Done', $details, 'tok-123', '2026-03-05T12:00:00Z');
        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertSame('Done', $array['message']);
        $this->assertSame(1, $array['processed']);
        $this->assertSame(0, $array['failed']);
        $this->assertSame($details, $array['details']);
        $this->assertSame('tok-123', $array['undo_token']);
        $this->assertSame('2026-03-05T12:00:00Z', $array['undo_expires_at']);
        $this->assertTrue($array['undoable']);
    }

    public function test_to_array_undoable_false_when_no_token(): void
    {
        $result = BulkActionResult::success(3);
        $array = $result->toArray();

        $this->assertFalse($array['undoable']);
        $this->assertNull($array['undo_token']);
        $this->assertNull($array['undo_expires_at']);
    }
}
