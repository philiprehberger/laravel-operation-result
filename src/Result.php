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
     * Check if the error code is NOT_FOUND.
     */
    public function isNotFound(): bool
    {
        return $this->errorCode === 'NOT_FOUND';
    }

    /**
     * Check if the error code is UNAUTHORIZED.
     */
    public function isUnauthorized(): bool
    {
        return $this->errorCode === 'UNAUTHORIZED';
    }

    /**
     * Check if the error code is VALIDATION_FAILED.
     */
    public function isValidationFailed(): bool
    {
        return $this->errorCode === 'VALIDATION_FAILED';
    }

    /**
     * Get the result data or throw on failure.
     *
     * @throws \RuntimeException
     */
    public function getOrThrow(): mixed
    {
        if ($this->failed()) {
            throw new \RuntimeException($this->message);
        }

        return null;
    }

    /**
     * Return a new instance with the updated message.
     */
    public function withMessage(string $message): static
    {
        return new static(
            success: $this->success,
            message: $message,
            errorCode: $this->errorCode,
        );
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
