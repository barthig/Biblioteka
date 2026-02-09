<?php
declare(strict_types=1);
namespace App\Application\Command\User;

class BlockUserCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $reason = null
    ) {
    }
}
