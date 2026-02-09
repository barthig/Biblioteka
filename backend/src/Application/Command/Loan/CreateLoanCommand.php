<?php
declare(strict_types=1);
namespace App\Application\Command\Loan;

class CreateLoanCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly int $bookId,
        public readonly ?int $reservationId = null,
        public readonly ?int $bookCopyId = null
    ) {
    }
}
