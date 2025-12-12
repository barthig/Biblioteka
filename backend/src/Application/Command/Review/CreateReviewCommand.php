<?php
namespace App\Application\Command\Review;

class CreateReviewCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly int $bookId,
        public readonly int $rating,
        public readonly string $comment
    ) {
    }
}
