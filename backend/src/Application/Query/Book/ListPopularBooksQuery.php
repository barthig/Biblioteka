<?php
namespace App\Application\Query\Book;

class ListPopularBooksQuery
{
    public function __construct(
        public readonly int $limit = 20
    ) {
    }
}
