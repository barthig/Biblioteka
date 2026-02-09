<?php
declare(strict_types=1);
namespace App\Application\Command\Account;

class UpdateAccountUiPreferencesCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $theme = null,
        public readonly ?string $fontSize = null,
        public readonly ?string $language = null
    ) {
    }
}
