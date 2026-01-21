<?php
namespace App\Application\Query\Loan;

class ListLoansQuery
{
    public function __construct(
        public readonly ?int $userId = null,
        public readonly bool $isLibrarian = false,
        public readonly int $page = 1,
        public readonly int $limit = 20,
        public readonly ?string $status = null,
        public readonly ?bool $overdue = null,
        public readonly ?string $userQuery = null,
        public readonly ?string $bookQuery = null
    ) {
    }
}
