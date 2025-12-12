<?php
namespace App\Application\Command\User;

class DeleteUserCommand
{
    public function __construct(
        public readonly int $userId
    ) {
    }
}
