<?php
declare(strict_types=1);
namespace App\Application\Query\Loan;

class GetLoanQuery
{
    public function __construct(
        public readonly int $loanId,
        public readonly int $userId,
        public readonly bool $isLibrarian = false
    ) {
    }
}
