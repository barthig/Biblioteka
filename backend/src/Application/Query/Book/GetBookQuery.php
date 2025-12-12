<?php
namespace App\Application\Query\Book;

class GetBookQuery
{
    public function __construct(
        public readonly int $bookId,
        public readonly ?int $userId = null
    ) {
    }
}
