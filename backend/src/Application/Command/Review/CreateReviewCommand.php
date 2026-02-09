<?php
declare(strict_types=1);
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
