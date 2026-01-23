<?php

namespace App\Dto;

/**
 * Unified error response DTO
 */
class ApiError
{
    /**
     * Machine-readable error code (e.g., USER_NOT_FOUND, VALIDATION_FAILED)
     */
    public string $code;

    /**
     * Human-readable error message
     */
    public string $message;

    /**
     * HTTP status code
     */
    public int $statusCode;

    /**
     * Optional: Additional error details (validation errors, nested exceptions, etc.)
     * @var mixed
     */
    public mixed $details = null;

    public function __construct(
        string $code,
        string $message,
        int $statusCode,
        mixed $details = null
    ) {
        $this->code = $code;
        $this->message = $message;
        $this->statusCode = $statusCode;
        $this->details = $details;
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        $result = [
            'code' => $this->code,
            'message' => $this->message,
            'statusCode' => $this->statusCode,
        ];

        if ($this->details !== null) {
            $result['details'] = $this->details;
        }

        return $result;
    }

    /**
     * Factory for common error codes
     */
    public static function notFound(string $resource = 'Resource', ?array $details = null): self
    {
        return new self(
            code: 'NOT_FOUND',
            message: "$resource not found",
            statusCode: 404,
            details: $details
        );
    }

    public static function unauthorized(?array $details = null): self
    {
        return new self(
            code: 'UNAUTHORIZED',
            message: 'Authentication required',
            statusCode: 401,
            details: $details
        );
    }

    public static function forbidden(?array $details = null): self
    {
        return new self(
            code: 'FORBIDDEN',
            message: 'Access denied',
            statusCode: 403,
            details: $details
        );
    }

    public static function validationFailed(array $fieldErrors): self
    {
        return new self(
            code: 'VALIDATION_FAILED',
            message: 'Validation failed',
            statusCode: 400,
            details: $fieldErrors
        );
    }

    public static function conflict(string $message, ?array $details = null): self
    {
        return new self(
            code: 'CONFLICT',
            message: $message,
            statusCode: 409,
            details: $details
        );
    }

    public static function gone(string $message, ?array $details = null): self
    {
        return new self(
            code: 'GONE',
            message: $message,
            statusCode: 410,
            details: $details
        );
    }

    public static function badRequest(string $message, ?array $details = null): self
    {
        return new self(
            code: 'BAD_REQUEST',
            message: $message,
            statusCode: 400,
            details: $details
        );
    }

    public static function internalError(string $message = 'Internal server error', ?array $details = null): self
    {
        return new self(
            code: 'INTERNAL_ERROR',
            message: $message,
            statusCode: 500,
            details: $details
        );
    }

    public static function unprocessable(string $message, ?array $details = null): self
    {
        return new self(
            code: 'UNPROCESSABLE_ENTITY',
            message: $message,
            statusCode: 422,
            details: $details
        );
    }

    public static function tooManyRequests(string $message = 'Too many requests', ?array $details = null): self
    {
        return new self(
            code: 'RATE_LIMIT_EXCEEDED',
            message: $message,
            statusCode: 429,
            details: $details
        );
    }

    public static function serviceUnavailable(string $message = 'Service temporarily unavailable', ?array $details = null): self
    {
        return new self(
            code: 'SERVICE_UNAVAILABLE',
            message: $message,
            statusCode: 503,
            details: $details
        );
    }

    public static function locked(string $message, ?array $details = null): self
    {
        return new self(
            code: 'LOCKED',
            message: $message,
            statusCode: 423,
            details: $details
        );
    }

    public static function fromException(\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e): self
    {
        $status = $e->getStatusCode();
        $code = match ($status) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            410 => 'GONE',
            422 => 'UNPROCESSABLE_ENTITY',
            423 => 'LOCKED',
            429 => 'RATE_LIMIT_EXCEEDED',
            500 => 'INTERNAL_ERROR',
            503 => 'SERVICE_UNAVAILABLE',
            default => 'ERROR',
        };
        return new self($code, $e->getMessage(), $status);
    }
}
