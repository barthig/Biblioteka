<?php
namespace App\Application\Command\Account;

class CompleteOnboardingCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly ?array $preferredCategories = null
    ) {
    }
}
