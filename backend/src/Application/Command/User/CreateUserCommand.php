<?php
namespace App\Application\Command\User;

class CreateUserCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly string $password,
        /** @var string[] */
        public readonly array $roles = ['ROLE_USER'],
        public readonly ?string $membershipGroup = null,
        public readonly ?int $loanLimit = null,
        public readonly ?string $phoneNumber = null,
        public readonly ?string $addressLine = null,
        public readonly ?string $city = null,
        public readonly ?string $postalCode = null,
        public readonly bool $blocked = false,
        public readonly ?string $blockedReason = null,
        public readonly bool $pendingApproval = false,
        public readonly bool $verified = true
    ) {
    }
}
