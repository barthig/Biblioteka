<?php
declare(strict_types=1);
namespace App\Application\Command\Author;

class DeleteAuthorCommand
{
    public function __construct(
        public readonly int $authorId
    ) {
    }
}
