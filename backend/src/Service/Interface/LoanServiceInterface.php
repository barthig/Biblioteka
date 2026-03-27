<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Entity\BookCopy;
use App\Entity\Loan;
use App\Entity\User;

interface LoanServiceInterface
{
    public function createLoan(User $user, BookCopy $bookCopy): Loan;

    public function returnLoan(Loan $loan): Loan;

    public function extendLoan(Loan $loan, int $days = 14): Loan;

    public function getUserActiveLoans(User $user): array;

    public function getUserLoanHistory(User $user, int $page = 1, int $limit = 20): array;

    public function getOverdueLoans(): array;

    public function canUserBorrow(User $user): bool;

    public function getUserLoanCount(User $user): int;

    public function getMaxLoansForUser(User $user): int;

    public function calculateDueDate(\DateTimeImmutable $borrowDate, int $loanDays = 30): \DateTimeImmutable;
}
