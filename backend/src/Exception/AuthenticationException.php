<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception thrown when user is not authenticated.
 * HTTP Status: 401 Unauthorized
 */
class AuthenticationException extends AppException
{
    protected int $statusCode = 401;
    protected ?string $errorCode = 'AUTHENTICATION_REQUIRED';

    public static function invalidCredentials(): self
    {
        $exception = new self('Invalid credentials provided.');
        $exception->errorCode = 'INVALID_CREDENTIALS';
        return $exception;
    }

    public static function tokenExpired(): self
    {
        $exception = new self('Authentication token has expired.');
        $exception->errorCode = 'TOKEN_EXPIRED';
        return $exception;
    }

    public static function tokenInvalid(): self
    {
        $exception = new self('Authentication token is invalid.');
        $exception->errorCode = 'TOKEN_INVALID';
        return $exception;
    }

    public static function tokenMissing(): self
    {
        $exception = new self('Authentication token is missing.');
        $exception->errorCode = 'TOKEN_MISSING';
        return $exception;
    }

    public static function refreshTokenExpired(): self
    {
        $exception = new self('Refresh token has expired. Please log in again.');
        $exception->errorCode = 'REFRESH_TOKEN_EXPIRED';
        return $exception;
    }

    public static function accountLocked(): self
    {
        $exception = new self('Account has been locked. Please contact support.');
        $exception->errorCode = 'ACCOUNT_LOCKED';
        return $exception;
    }

    public static function accountInactive(): self
    {
        $exception = new self('Account is inactive. Please verify your email.');
        $exception->errorCode = 'ACCOUNT_INACTIVE';
        return $exception;
    }
}
