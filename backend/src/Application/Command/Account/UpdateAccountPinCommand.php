<?php
declare(strict_types=1);
namespace App\Application\Command\Account;

class UpdateAccountPinCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly string $currentPin,
        public readonly string $newPin
    ) {
    }
}
