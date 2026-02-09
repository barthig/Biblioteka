<?php
declare(strict_types=1);
namespace App\Application\Command\Account;

class UpdateAccountCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $email = null,
        public readonly ?string $name = null,
        public readonly ?string $phoneNumber = null,
        public readonly ?string $addressLine = null,
        public readonly ?string $city = null,
        public readonly ?string $postalCode = null,
        public readonly ?bool $newsletterSubscribed = null
    ) {
    }
}
