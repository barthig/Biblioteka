<?php
declare(strict_types=1);

namespace App\Application\Command\User;

final class UpdateUserRolesCommand
{
    /** @param string[] $roles */
    public function __construct(
        public readonly int $userId,
        public readonly array $roles
    ) {
    }
}
