<?php
namespace App\Application\Query\Loan;

class ListUserLoansQuery
{
    public function __construct(
        public readonly int $userId,
        public readonly int $page = 1,
        public readonly int $limit = 20
    ) {
    }
}
