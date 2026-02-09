<?php
declare(strict_types=1);
namespace App\Application\Query\Book;

class GetBookAvailabilityQuery
{
    public function __construct(
        public readonly int $bookId
    ) {
    }
}
