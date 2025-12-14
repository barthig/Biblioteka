<?php
namespace App\Application\Command\Account;

class UpdateAccountPreferencesCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $defaultBranch = null,
        public readonly ?bool $newsletterSubscribed = null,
        public readonly ?bool $keepHistory = null,
        public readonly ?bool $emailLoans = null,
        public readonly ?bool $emailReservations = null,
        public readonly ?bool $emailFines = null,
        public readonly ?bool $emailAnnouncements = null
    ) {
    }
}
