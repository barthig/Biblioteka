<?php

declare(strict_types=1);

namespace App\Application\Command\User;

final class UpdateUIPreferencesCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly string $theme,
        public readonly string $fontSize,
        public readonly string $language
    ) {
    }
}
