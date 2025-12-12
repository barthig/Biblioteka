<?php
namespace App\Application\Command\Author;

class UpdateAuthorCommand
{
    public function __construct(
        public readonly int $authorId,
        public readonly ?string $name = null
    ) {
    }
}
