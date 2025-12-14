<?php
namespace App\Application\Command\Rating;

class RateBookCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly int $bookId,
        public readonly int $rating,
        public readonly ?string $review = null
    ) {
    }
}
