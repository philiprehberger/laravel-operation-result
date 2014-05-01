<?php

declare(strict_types=1);

namespace PhilipRehberger\OperationResult;

/**
 * Result class for undo operations.
 *
 * Use this when undoing a previous bulk action or operation,
 * tracking how many items were restored vs failed.
 */
class UndoResult extends Result
{
    public function __construct(
        bool $success,
        string $message = '',
        ?string $errorCode = null,
        public readonly int $restored = 0,
        public readonly int $failed = 0
    ) {
        parent::__construct($success, $message, $errorCode);
    }

    /**
     * Create a successful undo result.
     */
    public static function success(int $restored, string $message = 'Undo completed successfully'): static
    {
        return new static(
            success: true,
            message: $message,
            restored: $restored
        );
    }

    /**
     * Create a partial success result.
     */
    public static function partial(int $restored, int $failed, string $message = ''): static
    {
        return new static(
            success: $restored > 0,
            message: $message,
            restored: $restored,
            failed: $failed
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
     * Check if there were any failures during undo.
     */
    public function hasFailures(): bool
    {
        return $this->failed > 0;
    }

    /**
     * Convert the result to an array.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'restored' => $this->restored,
            'failed' => $this->failed,
        ];
    }
}
