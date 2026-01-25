<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception thrown when a requested resource is not found.
 * HTTP Status: 404 Not Found
 */
class NotFoundException extends AppException
{
    protected int $statusCode = 404;
    protected ?string $errorCode = 'RESOURCE_NOT_FOUND';

    public static function forEntity(string $entityName, mixed $id): self
    {
        $exception = new self(sprintf('%s with ID "%s" was not found.', $entityName, $id));
        $exception->setContext([
            'entity' => $entityName,
            'id' => $id,
        ]);
        return $exception;
    }

    public static function forBook(mixed $id): self
    {
        return self::forEntity('Book', $id);
    }

    public static function forUser(mixed $id): self
    {
        return self::forEntity('User', $id);
    }

    public static function forLoan(mixed $id): self
    {
        return self::forEntity('Loan', $id);
    }

    public static function forReservation(mixed $id): self
    {
        return self::forEntity('Reservation', $id);
    }

    public static function forCategory(mixed $id): self
    {
        return self::forEntity('Category', $id);
    }

    public static function forAuthor(mixed $id): self
    {
        return self::forEntity('Author', $id);
    }
}
