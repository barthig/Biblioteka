<?php
declare(strict_types=1);
namespace App\Application\Query\Acquisition;

class ListOrdersQuery
{
    public function __construct(
        public readonly int $page,
        public readonly int $limit,
        public readonly ?string $status,
        public readonly ?int $supplierId,
        public readonly ?int $budgetId
    ) {
    }
}
