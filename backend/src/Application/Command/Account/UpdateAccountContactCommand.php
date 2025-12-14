<?php
namespace App\Application\Command\Account;

class UpdateAccountContactCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $phoneNumber = null,
        public readonly ?string $addressLine = null,
        public readonly ?string $city = null,
        public readonly ?string $postalCode = null
    ) {
    }
}
