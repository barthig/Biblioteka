<?php

declare(strict_types=1);

namespace App\Application\Command\User;

final class UpdateContactCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $phoneNumber,
        public readonly ?string $addressLine,
        public readonly ?string $city,
        public readonly ?string $postalCode,
        public readonly string $preferredContact
    ) {
    }
}
