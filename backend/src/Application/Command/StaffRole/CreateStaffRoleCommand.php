<?php
namespace App\Application\Command\StaffRole;

class CreateStaffRoleCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $roleKey,
        public readonly array $modules = [],
        public readonly ?string $description = null
    ) {
    }
}
