<?php
declare(strict_types=1);
namespace App\Application\Command\Loan;

class ExtendLoanCommand
{
    public function __construct(
        public readonly int $loanId,
        public readonly int $userId
    ) {
    }
}
