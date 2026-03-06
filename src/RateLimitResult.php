<?php

declare(strict_types=1);

namespace PhilipRehberger\OperationResult;

/**
 * Result class for rate limiting checks.
 *
 * Use this when checking or enforcing rate limits on API requests,
 * providing rate limit information in response headers.
 */
class RateLimitResult extends Result
{
    public function __construct(
        bool $success,
        string $message = '',
        ?string $errorCode = null,
        public readonly bool $allowed = true,
        public readonly int $limit = 0,
        public readonly int $remaining = 0,
        public readonly int $resetAt = 0,
        public readonly ?int $retryAfter = null
    ) {
        parent::__construct($success, $message, $errorCode);
    }

    /**
     * Create an allowed (within limit) result.
     */
    public static function allowed(int $limit, int $remaining, int $resetAt): static
    {
        return new static(
            success: true,
            allowed: true,
            limit: $limit,
            remaining: $remaining,
            resetAt: $resetAt
        );
    }

    /**
     * Create a denied (rate limited) result.
     */
    public static function denied(int $limit, int $resetAt, int $retryAfter): static
    {
        return new static(
            success: false,
            message: 'Rate limit exceeded',
            errorCode: 'RATE_LIMITED',
            allowed: false,
            limit: $limit,
            remaining: 0,
            resetAt: $resetAt,
            retryAfter: $retryAfter
        );
    }

    /**
     * Check if the request is allowed.
     */
    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    /**
     * Check if the request is denied.
     */
    public function isDenied(): bool
    {
        return ! $this->allowed;
    }

    /**
     * Get rate limit headers for the response.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        $headers = [
            'X-RateLimit-Limit' => (string) $this->limit,
            'X-RateLimit-Remaining' => (string) $this->remaining,
            'X-RateLimit-Reset' => (string) $this->resetAt,
        ];

        if ($this->retryAfter !== null) {
            $headers['Retry-After'] = (string) $this->retryAfter;
        }

        return $headers;
    }

    /**
     * Convert the result to an array.
     */
    public function toArray(): array
    {
        $array = [
            'success' => $this->success,
            'allowed' => $this->allowed,
            'limit' => $this->limit,
            'remaining' => $this->remaining,
            'reset_at' => $this->resetAt,
        ];

        if ($this->retryAfter !== null) {
            $array['retry_after'] = $this->retryAfter;
        }

        return $array;
    }
}
