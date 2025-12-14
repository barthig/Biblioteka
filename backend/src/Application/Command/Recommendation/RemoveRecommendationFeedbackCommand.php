<?php
namespace App\Application\Command\Recommendation;

class RemoveRecommendationFeedbackCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly int $bookId
    ) {
    }
}
