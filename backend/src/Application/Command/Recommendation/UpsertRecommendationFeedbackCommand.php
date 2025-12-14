<?php
namespace App\Application\Command\Recommendation;

class UpsertRecommendationFeedbackCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly int $bookId,
        public readonly string $feedbackType
    ) {
    }
}
