<?php
namespace App\Application\Command\User;

class BlockUserCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $reason = null
    ) {
    }
}
