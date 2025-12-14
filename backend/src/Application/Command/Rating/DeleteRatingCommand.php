<?php
namespace App\Application\Command\Rating;

class DeleteRatingCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly int $ratingId,
        public readonly bool $isAdmin = false
    ) {
    }
}
