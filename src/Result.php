<?php

declare(strict_types=1);

namespace PhilipRehberger\OperationResult;

use PhilipRehberger\OperationResult\Contracts\ResultContract;

/**
 * Base result class for service operations.
 *
 * Provides a consistent way to return success/failure from service methods
 * without relying on exceptions for control flow.
 */
abstract class Result implements ResultContract
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message = '',
        public readonly ?string $errorCode = null,
    ) {}

    /**
     * Check if the operation was successful.
     */
    public function succeeded(): bool
    {
        return $this->success;
    }

    /**
     * Check if the operation failed.
     */
    public function failed(): bool
    {
        return ! $this->success;
    }

    /**
     * Get the result message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the error code if present.
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Convert the result to an array.
     */
    public function toArray(): array
    {
        $data = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        if ($this->errorCode !== null) {
            $data['error_code'] = $this->errorCode;
        }

        return $data;
    }
}
