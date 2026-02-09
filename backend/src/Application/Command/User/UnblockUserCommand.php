<?php
declare(strict_types=1);
namespace App\Application\Command\User;

class UnblockUserCommand
{
    public function __construct(
        public readonly int $userId
    ) {
    }
}
