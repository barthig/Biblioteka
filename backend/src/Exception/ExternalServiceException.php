<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception thrown when external service fails.
 * HTTP Status: 503 Service Unavailable
 */
class ExternalServiceException extends AppException
{
    protected int $statusCode = 503;
    protected ?string $errorCode = 'EXTERNAL_SERVICE_ERROR';

    public static function serviceUnavailable(string $serviceName): self
    {
        $exception = new self(sprintf('External service "%s" is temporarily unavailable.', $serviceName));
        $exception->setContext(['service' => $serviceName]);
        return $exception;
    }

    public static function timeout(string $serviceName, int $timeoutSeconds): self
    {
        $exception = new self(sprintf('Request to "%s" timed out after %d seconds.', $serviceName, $timeoutSeconds));
        $exception->errorCode = 'SERVICE_TIMEOUT';
        $exception->setContext(['service' => $serviceName, 'timeout' => $timeoutSeconds]);
        return $exception;
    }

    public static function connectionFailed(string $serviceName): self
    {
        $exception = new self(sprintf('Failed to connect to "%s".', $serviceName));
        $exception->errorCode = 'CONNECTION_FAILED';
        $exception->setContext(['service' => $serviceName]);
        return $exception;
    }

    public static function openAIError(string $message): self
    {
        $exception = new self(sprintf('OpenAI API error: %s', $message));
        $exception->errorCode = 'OPENAI_ERROR';
        return $exception;
    }

    public static function elasticsearchError(string $message): self
    {
        $exception = new self(sprintf('Elasticsearch error: %s', $message));
        $exception->errorCode = 'ELASTICSEARCH_ERROR';
        return $exception;
    }

    public static function rabbitMQError(string $message): self
    {
        $exception = new self(sprintf('Message queue error: %s', $message));
        $exception->errorCode = 'RABBITMQ_ERROR';
        return $exception;
    }
}
