<?php
namespace App\Application\Command\Review;

readonly class DeleteReviewCommand
{
    public function __construct(
        public int $reviewId,
        public int $userId,
        public bool $isLibrarian = false
    ) {
    }
}
