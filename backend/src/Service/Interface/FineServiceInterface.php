<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Entity\Fine;
use App\Entity\Loan;
use App\Entity\User;

interface FineServiceInterface
{
    public function createFine(Loan $loan, float $amount, string $reason): Fine;

    public function payFine(Fine $fine, string $paymentMethod = 'cash'): Fine;

    public function cancelFine(Fine $fine, string $reason): void;

    public function getUserUnpaidFines(User $user): array;

    public function getUserTotalDebt(User $user): float;

    public function calculateFineAmount(int $overdueDays): float;

    public function userHasUnpaidFines(User $user): bool;

    public function getUserFineHistory(User $user, int $page = 1, int $limit = 20): array;

    public function processOverdueFines(): int;
}
