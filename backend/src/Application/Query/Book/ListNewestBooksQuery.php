<?php
declare(strict_types=1);
namespace App\Application\Query\Book;

class ListNewestBooksQuery
{
    public function __construct(
        public readonly int $limit = 20
    ) {
    }
}
