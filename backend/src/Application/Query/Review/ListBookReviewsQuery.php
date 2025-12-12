<?php
namespace App\Application\Query\Review;

class ListBookReviewsQuery
{
    public function __construct(
        public readonly int $bookId,
        public readonly int $page = 1,
        public readonly int $limit = 20
    ) {
    }
}
