<?php
declare(strict_types=1);
namespace App\Application\Query\Author;

class GetAuthorQuery
{
    public function __construct(
        public readonly int $authorId
    ) {
    }
}
