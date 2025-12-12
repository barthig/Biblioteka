<?php
namespace App\Application\Command\Author;

class DeleteAuthorCommand
{
    public function __construct(
        public readonly int $authorId
    ) {
    }
}
