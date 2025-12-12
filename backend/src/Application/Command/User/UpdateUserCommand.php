<?php
namespace App\Application\Command\User;

class UpdateUserCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?array $roles = null,
        public readonly ?string $phoneNumber = null,
        public readonly ?string $addressLine = null,
        public readonly ?string $city = null,
        public readonly ?string $postalCode = null,
        public readonly ?bool $pendingApproval = null,
        public readonly ?bool $verified = null,
        public readonly ?string $membershipGroup = null,
        public readonly ?int $loanLimit = null,
        public readonly ?bool $blocked = null,
        public readonly ?string $blockedReason = null
    ) {
    }
}
