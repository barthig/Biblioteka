<?php
namespace App\Application\Query\StaffRole;

class GetStaffRoleQuery
{
    public function __construct(
        public readonly int $roleId
    ) {
    }
}
