<?php
namespace App\Application\Command\User;

class UnblockUserCommand
{
    public function __construct(
        public readonly int $userId
    ) {
    }
}
