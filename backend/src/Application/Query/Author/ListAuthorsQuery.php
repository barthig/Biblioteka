<?php
namespace App\Application\Query\Author;

class ListAuthorsQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 20,
        public readonly ?string $search = null
    ) {
    }
}
