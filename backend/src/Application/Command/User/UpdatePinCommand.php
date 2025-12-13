<?php

declare(strict_types=1);

namespace App\Application\Command\User;

final class UpdatePinCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly string $currentPin,
        public readonly string $newPin
    ) {
    }
}
