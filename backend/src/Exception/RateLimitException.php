<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception thrown when rate limit is exceeded.
 * HTTP Status: 429 Too Many Requests
 */
class RateLimitException extends AppException
{
    protected int $statusCode = 429;
    protected ?string $errorCode = 'RATE_LIMIT_EXCEEDED';

    public static function exceeded(int $retryAfterSeconds = 60): self
    {
        $exception = new self('Too many requests. Please try again later.');
        $exception->headers['Retry-After'] = (string) $retryAfterSeconds;
        $exception->setContext(['retry_after' => $retryAfterSeconds]);
        return $exception;
    }

    public static function apiLimitExceeded(string $endpoint, int $retryAfterSeconds = 60): self
    {
        $exception = new self(sprintf('Rate limit exceeded for endpoint: %s', $endpoint));
        $exception->headers['Retry-After'] = (string) $retryAfterSeconds;
        $exception->setContext(['endpoint' => $endpoint, 'retry_after' => $retryAfterSeconds]);
        return $exception;
    }
}
