<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Entity\Fine;
use App\Entity\User;
use App\Entity\Loan;

/**
 * Interface for Fine-related business operations
 */
interface FineServiceInterface
{
    /**
     * Create a fine for overdue loan
     */
    public function createFine(Loan $loan, float $amount, string $reason): Fine;

    /**
     * Pay a fine
     * 
     * @throws \App\Exception\FineAlreadyPaidException
     */
    public function payFine(Fine $fine, string $paymentMethod = 'cash'): Fine;

    /**
     * Cancel a fine
     * 
     * @throws \App\Exception\FineAlreadyPaidException
     */
    public function cancelFine(Fine $fine, string $reason): void;

    /**
     * Get user's unpaid fines
     */
    public function getUserUnpaidFines(User $user): array;

    /**
     * Get user's total unpaid amount
     */
    public function getUserTotalDebt(User $user): float;

    /**
     * Calculate fine amount for overdue days
     */
    public function calculateFineAmount(int $overdueDays): float;

    /**
     * Check if user has unpaid fines
     */
    public function userHasUnpaidFines(User $user): bool;

    /**
     * Get fine history for user
     */
    public function getUserFineHistory(User $user, int $page = 1, int $limit = 20): array;

    /**
     * Process automatic fines for all overdue loans
     * 
     * @return int Number of fines created
     */
    public function processOverdueFines(): int;
}
