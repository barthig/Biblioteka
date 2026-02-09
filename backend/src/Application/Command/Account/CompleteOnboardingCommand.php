<?php
declare(strict_types=1);
namespace App\Application\Command\Account;

class CompleteOnboardingCommand
{
    public function __construct(
        public readonly int $userId,
        /** @var int[]|null */
        public readonly ?array $preferredCategories = null
    ) {
    }
}
