<?php
namespace App\Application\Command\StaffRole;

class UpdateStaffRoleCommand
{
    public function __construct(
        public readonly int $roleId,
        public readonly ?string $name = null,
        /** @var string[]|null */
        public readonly ?array $modules = null,
        public readonly ?string $description = null
    ) {
    }
}
