<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Entity\Loan;
use App\Entity\User;
use App\Entity\BookCopy;

/**
 * Interface for Loan-related business operations
 */
interface LoanServiceInterface
{
    /**
     * Create a new loan
     * 
     * @throws \App\Exception\BookNotAvailableException
     * @throws \App\Exception\UserLoanLimitExceededException
     * @throws \App\Exception\UserBlockedException
     */
    public function createLoan(User $user, BookCopy $bookCopy): Loan;

    /**
     * Return a borrowed book
     * 
     * @throws \App\Exception\LoanAlreadyReturnedException
     */
    public function returnLoan(Loan $loan): Loan;

    /**
     * Extend loan due date
     * 
     * @throws \App\Exception\LoanExtensionNotAllowedException
     * @throws \App\Exception\MaxExtensionsReachedException
     */
    public function extendLoan(Loan $loan, int $days = 14): Loan;

    /**
     * Get user's active loans
     */
    public function getUserActiveLoans(User $user): array;

    /**
     * Get user's loan history
     */
    public function getUserLoanHistory(User $user, int $page = 1, int $limit = 20): array;

    /**
     * Get overdue loans
     */
    public function getOverdueLoans(): array;

    /**
     * Check if user can borrow more books
     */
    public function canUserBorrow(User $user): bool;

    /**
     * Get user's current loan count
     */
    public function getUserLoanCount(User $user): int;

    /**
     * Get maximum allowed loans for user
     */
    public function getMaxLoansForUser(User $user): int;

    /**
     * Calculate expected return date
     */
    public function calculateDueDate(\DateTimeImmutable $borrowDate, int $loanDays = 30): \DateTimeImmutable;
}
