<?php
namespace App\Application\Query\BookInventory;

class ListBookCopiesQuery
{
    public function __construct(
        public readonly int $bookId
    ) {
    }
}
