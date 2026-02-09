<?php
declare(strict_types=1);
namespace App\Application\Query\StaffRole;

class GetStaffRoleQuery
{
    public function __construct(
        public readonly int $roleId
    ) {
    }
}
