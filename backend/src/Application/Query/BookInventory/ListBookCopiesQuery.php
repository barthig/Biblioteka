<?php
declare(strict_types=1);
namespace App\Application\Query\BookInventory;

class ListBookCopiesQuery
{
    public function __construct(
        public readonly int $bookId
    ) {
    }
}
