<?php

declare(strict_types=1);

namespace PhilipRehberger\OperationResult\Contracts;

interface ResultContract
{
    /**
     * Check if the operation was successful.
     */
    public function succeeded(): bool;

    /**
     * Check if the operation failed.
     */
    public function failed(): bool;

    /**
     * Get the result message.
     */
    public function getMessage(): string;

    /**
     * Convert the result to an array.
     */
    public function toArray(): array;
}
