<?php
namespace App\Application\Command\Loan;

class ReturnLoanCommand
{
    public function __construct(
        public readonly int $loanId,
        public readonly int $userId
    ) {
    }
}
