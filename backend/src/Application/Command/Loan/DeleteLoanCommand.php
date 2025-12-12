<?php
namespace App\Application\Command\Loan;

class DeleteLoanCommand
{
    public function __construct(
        public readonly int $loanId
    ) {
    }
}
