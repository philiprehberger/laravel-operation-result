<?php

declare(strict_types=1);

namespace PhilipRehberger\OperationResult;

/**
 * Result class for bulk operations.
 *
 * Use this when performing operations on multiple items at once,
 * tracking success/failure counts and providing undo capability.
 */
class BulkActionResult extends Result
{
    public function __construct(
        bool $success,
        string $message = '',
        ?string $errorCode = null,
        public readonly int $processed = 0,
        public readonly int $failed = 0,
        public readonly array $details = [],
        public readonly ?string $undoToken = null,
        public readonly ?string $undoExpiresAt = null
    ) {
        parent::__construct($success, $message, $errorCode);
    }

    /**
     * Create a successful bulk action result.
     */
    public static function success(
        int $processed,
        string $message = '',
        array $details = [],
        ?string $undoToken = null,
        ?string $undoExpiresAt = null
    ): static {
        return new static(
            success: true,
            message: $message,
            processed: $processed,
            details: $details,
            undoToken: $undoToken,
            undoExpiresAt: $undoExpiresAt
        );
    }

    /**
     * Create a partial success result (some items failed).
     */
    public static function partial(
        int $processed,
        int $failed,
        string $message = '',
        array $details = [],
        ?string $undoToken = null,
        ?string $undoExpiresAt = null
    ): static {
        return new static(
            success: $processed > 0,
            message: $message,
            processed: $processed,
            failed: $failed,
            details: $details,
            undoToken: $undoToken,
            undoExpiresAt: $undoExpiresAt
        );
    }

    /**
     * Create a failure result.
     */
    public static function failure(string $message, ?string $errorCode = null, array $details = []): static
    {
        return new static(
            success: false,
            message: $message,
            errorCode: $errorCode,
            details: $details
        );
    }

    /**
     * Check if there were any failures.
     */
    public function hasFailures(): bool
    {
        return $this->failed > 0;
    }

    /**
     * Check if all items were processed successfully.
     */
    public function isComplete(): bool
    {
        return $this->failed === 0 && $this->processed > 0;
    }

    /**
     * Get failed item IDs.
     */
    public function getFailedIds(): array
    {
        return collect($this->details)
            ->where('success', false)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get success item IDs.
     */
    public function getSuccessIds(): array
    {
        return collect($this->details)
            ->where('success', true)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Check if action can be undone.
     */
    public function canUndo(): bool
    {
        return $this->undoToken !== null;
    }

    /**
     * Convert the result to an array.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'processed' => $this->processed,
            'failed' => $this->failed,
            'details' => $this->details,
            'undo_token' => $this->undoToken,
            'undo_expires_at' => $this->undoExpiresAt,
            'undoable' => $this->undoToken !== null,
        ];
    }
}
