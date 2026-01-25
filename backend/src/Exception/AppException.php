<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Base exception class for all application exceptions.
 * All custom exceptions should extend this class.
 */
abstract class AppException extends \RuntimeException implements HttpExceptionInterface
{
    protected int $statusCode = 500;
    protected array $headers = [];
    protected ?string $errorCode = null;
    protected array $context = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Convert exception to API response format
     */
    public function toArray(): array
    {
        return [
            'error' => true,
            'code' => $this->errorCode ?? 'UNKNOWN_ERROR',
            'message' => $this->getMessage(),
            'status' => $this->statusCode,
            'context' => $this->context,
        ];
    }
}
