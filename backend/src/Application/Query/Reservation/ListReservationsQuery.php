<?php
declare(strict_types=1);
namespace App\Application\Query\Reservation;

class ListReservationsQuery
{
    public function __construct(
        public readonly ?int $userId = null,
        public readonly bool $isLibrarian = false,
        public readonly int $page = 1,
        public readonly int $limit = 20,
        public readonly ?string $status = null,
        public readonly ?int $filterUserId = null,
        public readonly bool $includeHistory = false
    ) {
    }
}
