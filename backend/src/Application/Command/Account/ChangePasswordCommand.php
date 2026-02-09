<?php
declare(strict_types=1);
namespace App\Application\Command\Account;

class ChangePasswordCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly string $currentPassword,
        public readonly string $newPassword,
        public readonly string $confirmPassword
    ) {
    }
}
