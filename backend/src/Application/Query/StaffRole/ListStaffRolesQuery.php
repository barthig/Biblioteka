<?php
namespace App\Application\Query\StaffRole;

class ListStaffRolesQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 50
    ) {
    }
}
