<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception thrown when resource already exists (conflict).
 * HTTP Status: 409 Conflict
 */
class ConflictException extends AppException
{
    protected int $statusCode = 409;
    protected ?string $errorCode = 'RESOURCE_CONFLICT';

    public static function duplicateEntry(string $field, mixed $value): self
    {
        $exception = new self(sprintf('A record with %s "%s" already exists.', $field, $value));
        $exception->setContext(['field' => $field, 'value' => $value]);
        return $exception;
    }

    public static function resourceAlreadyExists(string $resource): self
    {
        return new self(sprintf('The %s already exists.', $resource));
    }

    public static function concurrentModification(): self
    {
        $exception = new self('Resource was modified by another user. Please refresh and try again.');
        $exception->errorCode = 'CONCURRENT_MODIFICATION';
        return $exception;
    }
}
