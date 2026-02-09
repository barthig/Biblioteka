<?php
declare(strict_types=1);
namespace App\Application\Command\Loan;

class UpdateLoanCommand
{
    public function __construct(
        public readonly int $loanId,
        public readonly ?string $dueAt = null,
        public readonly ?string $status = null,
        public readonly ?int $bookId = null,
        public readonly ?int $bookCopyId = null
    ) {
    }
}
