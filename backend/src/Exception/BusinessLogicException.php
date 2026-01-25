<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception thrown when business logic violation occurs.
 * HTTP Status: 422 Unprocessable Entity
 */
class BusinessLogicException extends AppException
{
    protected int $statusCode = 422;
    protected ?string $errorCode = 'BUSINESS_LOGIC_ERROR';

    // ============================================
    // LOAN EXCEPTIONS
    // ============================================

    public static function bookNotAvailable(mixed $bookId): self
    {
        $exception = new self('This book is not available for loan.');
        $exception->errorCode = 'BOOK_NOT_AVAILABLE';
        $exception->setContext(['book_id' => $bookId]);
        return $exception;
    }

    public static function maxLoansReached(int $maxLoans): self
    {
        $exception = new self(sprintf('Maximum loan limit (%d) has been reached.', $maxLoans));
        $exception->errorCode = 'MAX_LOANS_REACHED';
        $exception->setContext(['max_loans' => $maxLoans]);
        return $exception;
    }

    public static function loanAlreadyReturned(): self
    {
        $exception = new self('This loan has already been returned.');
        $exception->errorCode = 'LOAN_ALREADY_RETURNED';
        return $exception;
    }

    public static function loanOverdue(int $daysOverdue): self
    {
        $exception = new self(sprintf('This loan is %d days overdue. Please return it first.', $daysOverdue));
        $exception->errorCode = 'LOAN_OVERDUE';
        $exception->setContext(['days_overdue' => $daysOverdue]);
        return $exception;
    }

    public static function cannotExtendLoan(string $reason): self
    {
        $exception = new self(sprintf('Cannot extend loan: %s', $reason));
        $exception->errorCode = 'CANNOT_EXTEND_LOAN';
        $exception->setContext(['reason' => $reason]);
        return $exception;
    }

    // ============================================
    // RESERVATION EXCEPTIONS
    // ============================================

    public static function bookAlreadyReserved(): self
    {
        $exception = new self('You have already reserved this book.');
        $exception->errorCode = 'ALREADY_RESERVED';
        return $exception;
    }

    public static function maxReservationsReached(int $maxReservations): self
    {
        $exception = new self(sprintf('Maximum reservation limit (%d) has been reached.', $maxReservations));
        $exception->errorCode = 'MAX_RESERVATIONS_REACHED';
        $exception->setContext(['max_reservations' => $maxReservations]);
        return $exception;
    }

    public static function reservationExpired(): self
    {
        $exception = new self('This reservation has expired.');
        $exception->errorCode = 'RESERVATION_EXPIRED';
        return $exception;
    }

    public static function cannotCancelReservation(string $reason): self
    {
        $exception = new self(sprintf('Cannot cancel reservation: %s', $reason));
        $exception->errorCode = 'CANNOT_CANCEL_RESERVATION';
        $exception->setContext(['reason' => $reason]);
        return $exception;
    }

    // ============================================
    // USER EXCEPTIONS
    // ============================================

    public static function emailAlreadyExists(string $email): self
    {
        $exception = new self('A user with this email already exists.');
        $exception->errorCode = 'EMAIL_ALREADY_EXISTS';
        $exception->setContext(['email' => $email]);
        return $exception;
    }

    public static function userHasActiveLoans(): self
    {
        $exception = new self('Cannot delete user with active loans.');
        $exception->errorCode = 'USER_HAS_ACTIVE_LOANS';
        return $exception;
    }

    public static function userHasUnpaidFees(float $amount): self
    {
        $exception = new self(sprintf('User has unpaid fees: %.2f PLN', $amount));
        $exception->errorCode = 'USER_HAS_UNPAID_FEES';
        $exception->setContext(['amount' => $amount]);
        return $exception;
    }

    // ============================================
    // BOOK EXCEPTIONS
    // ============================================

    public static function bookHasActiveLoans(): self
    {
        $exception = new self('Cannot delete book with active loans.');
        $exception->errorCode = 'BOOK_HAS_ACTIVE_LOANS';
        return $exception;
    }

    public static function isbnAlreadyExists(string $isbn): self
    {
        $exception = new self('A book with this ISBN already exists.');
        $exception->errorCode = 'ISBN_ALREADY_EXISTS';
        $exception->setContext(['isbn' => $isbn]);
        return $exception;
    }

    public static function noCopiesAvailable(): self
    {
        $exception = new self('No copies of this book are currently available.');
        $exception->errorCode = 'NO_COPIES_AVAILABLE';
        return $exception;
    }

    // ============================================
    // GENERIC BUSINESS LOGIC
    // ============================================

    public static function invalidState(string $message): self
    {
        $exception = new self($message);
        $exception->errorCode = 'INVALID_STATE';
        return $exception;
    }

    public static function operationFailed(string $operation, string $reason): self
    {
        $exception = new self(sprintf('%s failed: %s', $operation, $reason));
        $exception->errorCode = 'OPERATION_FAILED';
        $exception->setContext(['operation' => $operation, 'reason' => $reason]);
        return $exception;
    }
}
