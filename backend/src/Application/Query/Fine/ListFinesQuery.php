<?php
namespace App\Application\Query\Fine;

class ListFinesQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 20,
        public readonly ?int $userId = null,
        public readonly bool $isLibrarian = false
    ) {
    }
}
