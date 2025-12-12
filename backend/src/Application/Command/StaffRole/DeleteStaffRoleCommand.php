<?php
namespace App\Application\Command\StaffRole;

class DeleteStaffRoleCommand
{
    public function __construct(
        public readonly int $roleId
    ) {
    }
}
