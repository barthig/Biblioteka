<?php
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
