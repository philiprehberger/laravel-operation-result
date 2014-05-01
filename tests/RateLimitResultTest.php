<?php

declare(strict_types=1);

namespace PhilipRehberger\OperationResult\Tests;

use PhilipRehberger\OperationResult\RateLimitResult;
use PHPUnit\Framework\TestCase;

class RateLimitResultTest extends TestCase
{
    public function test_allowed_returns_successful_result(): void
    {
        $resetAt = time() + 60;
        $result = RateLimitResult::allowed(100, 75, $resetAt);

        $this->assertTrue($result->succeeded());
        $this->assertFalse($result->failed());
        $this->assertTrue($result->isAllowed());
        $this->assertFalse($result->isDenied());
        $this->assertSame(100, $result->limit);
        $this->assertSame(75, $result->remaining);
        $this->assertSame($resetAt, $result->resetAt);
        $this->assertNull($result->retryAfter);
    }

    public function test_denied_returns_failed_result(): void
    {
        $resetAt = time() + 30;
        $result = RateLimitResult::denied(100, $resetAt, 30);

        $this->assertFalse($result->succeeded());
        $this->assertTrue($result->failed());
        $this->assertFalse($result->isAllowed());
        $this->assertTrue($result->isDenied());
        $this->assertSame('Rate limit exceeded', $result->getMessage());
        $this->assertSame('RATE_LIMITED', $result->getErrorCode());
        $this->assertSame(100, $result->limit);
        $this->assertSame(0, $result->remaining);
        $this->assertSame($resetAt, $result->resetAt);
        $this->assertSame(30, $result->retryAfter);
    }

    public function test_is_allowed_true_for_allowed_result(): void
    {
        $result = RateLimitResult::allowed(60, 40, time() + 60);

        $this->assertTrue($result->isAllowed());
    }

    public function test_is_denied_true_for_denied_result(): void
    {
        $result = RateLimitResult::denied(60, time() + 30, 30);

        $this->assertTrue($result->isDenied());
    }

    public function test_get_headers_for_allowed_result_excludes_retry_after(): void
    {
        $resetAt = 1741132800;
        $result = RateLimitResult::allowed(100, 50, $resetAt);
        $headers = $result->getHeaders();

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
        $this->assertArrayNotHasKey('Retry-After', $headers);

        $this->assertSame('100', $headers['X-RateLimit-Limit']);
        $this->assertSame('50', $headers['X-RateLimit-Remaining']);
        $this->assertSame((string) $resetAt, $headers['X-RateLimit-Reset']);
    }

    public function test_get_headers_for_denied_result_includes_retry_after(): void
    {
        $resetAt = 1741132800;
        $result = RateLimitResult::denied(100, $resetAt, 45);
        $headers = $result->getHeaders();

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
        $this->assertArrayHasKey('Retry-After', $headers);

        $this->assertSame('100', $headers['X-RateLimit-Limit']);
        $this->assertSame('0', $headers['X-RateLimit-Remaining']);
        $this->assertSame('45', $headers['Retry-After']);
    }

    public function test_to_array_for_allowed_result(): void
    {
        $resetAt = time() + 60;
        $result = RateLimitResult::allowed(1000, 999, $resetAt);
        $array = $result->toArray();

        $this->assertTrue($array['success']);
        $this->assertTrue($array['allowed']);
        $this->assertSame(1000, $array['limit']);
        $this->assertSame(999, $array['remaining']);
        $this->assertSame($resetAt, $array['reset_at']);
        $this->assertArrayNotHasKey('retry_after', $array);
    }

    public function test_to_array_for_denied_result_includes_retry_after(): void
    {
        $resetAt = time() + 30;
        $result = RateLimitResult::denied(1000, $resetAt, 30);
        $array = $result->toArray();

        $this->assertFalse($array['success']);
        $this->assertFalse($array['allowed']);
        $this->assertSame(1000, $array['limit']);
        $this->assertSame(0, $array['remaining']);
        $this->assertSame($resetAt, $array['reset_at']);
        $this->assertSame(30, $array['retry_after']);
    }

    public function test_all_header_values_are_strings(): void
    {
        $result = RateLimitResult::denied(500, 9999999, 60);
        $headers = $result->getHeaders();

        foreach ($headers as $value) {
            $this->assertIsString($value);
        }
    }
}
