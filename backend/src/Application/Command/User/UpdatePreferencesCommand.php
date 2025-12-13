<?php

declare(strict_types=1);

namespace App\Application\Command\User;

final class UpdatePreferencesCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $defaultBranch,
        public readonly bool $newsletter,
        public readonly bool $keepHistory,
        public readonly bool $emailLoans,
        public readonly bool $emailReservations,
        public readonly bool $emailFines,
        public readonly bool $emailAnnouncements
    ) {
    }
}
