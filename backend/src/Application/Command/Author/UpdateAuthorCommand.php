<?php
declare(strict_types=1);
namespace App\Application\Command\Author;

class UpdateAuthorCommand
{
    public function __construct(
        public readonly int $authorId,
        public readonly ?string $name = null
    ) {
    }
}
