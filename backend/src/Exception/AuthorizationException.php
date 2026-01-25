<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception thrown when user doesn't have permission to perform action.
 * HTTP Status: 403 Forbidden
 */
class AuthorizationException extends AppException
{
    protected int $statusCode = 403;
    protected ?string $errorCode = 'ACCESS_DENIED';

    public static function accessDenied(string $resource = 'resource'): self
    {
        return new self(sprintf('You do not have permission to access this %s.', $resource));
    }

    public static function insufficientRole(string $requiredRole): self
    {
        $exception = new self(sprintf('Role "%s" is required to perform this action.', $requiredRole));
        $exception->errorCode = 'INSUFFICIENT_ROLE';
        $exception->setContext(['required_role' => $requiredRole]);
        return $exception;
    }

    public static function notOwner(): self
    {
        $exception = new self('You can only modify your own resources.');
        $exception->errorCode = 'NOT_OWNER';
        return $exception;
    }

    public static function resourceLocked(): self
    {
        $exception = new self('This resource is locked and cannot be modified.');
        $exception->errorCode = 'RESOURCE_LOCKED';
        return $exception;
    }

    public static function operationNotAllowed(string $operation): self
    {
        $exception = new self(sprintf('Operation "%s" is not allowed.', $operation));
        $exception->errorCode = 'OPERATION_NOT_ALLOWED';
        $exception->setContext(['operation' => $operation]);
        return $exception;
    }
}
