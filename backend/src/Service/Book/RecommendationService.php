<?php
declare(strict_types=1);
namespace App\Service\Book;

use App\Entity\Book;
use App\Entity\User;
use App\Repository\BookRepository;
use App\Repository\UserBookInteractionRepository;

class RecommendationService
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly UserBookInteractionRepository $interactionRepository
    ) {
    }

    /**
     * @return array{status: string, books: Book[]}
     */
    public function getPersonalizedRecommendations(User $user, int $limit = 10): array
    {
        $limit = max(1, $limit);

        $excludedIds = $this->interactionRepository->findBookIdsByUser($user);
        $likedInteractions = $this->interactionRepository->findLikedInteractions($user);
        $vector = $this->weightedAverageEmbedding($likedInteractions);

        if ($vector === null) {
            $starterVector = $user->getTasteEmbedding();
            if (is_array($starterVector) && $starterVector !== []) {
                $vector = $starterVector;
            }
        }

        if ($vector === null) {
            return [
                'status' => 'not_enough_data',
                'books' => $this->bookRepository->findPopularBooks($limit, $excludedIds),
            ];
        }

        return [
            'status' => 'ok',
            'books' => $this->bookRepository->findSimilarBooksExcluding($vector, $excludedIds, $limit),
        ];
    }

    /**
     * @param \App\Entity\UserBookInteraction[] $interactions
     * @return float[]|null
     */
    private function weightedAverageEmbedding(array $interactions): ?array
    {
        $sum = [];
        $dimensions = null;
        $weightSum = 0.0;
        $now = new \DateTimeImmutable();

        foreach ($interactions as $interaction) {
            $book = $interaction->getBook();
            $embedding = $book->getEmbedding();
            if (!is_array($embedding) || $embedding === []) {
                continue;
            }

            if ($dimensions === null) {
                $dimensions = count($embedding);
                $sum = array_fill(0, $dimensions, 0.0);
            }

            if ($dimensions !== count($embedding)) {
                continue;
            }

            $weight = $this->weightForInteraction($interaction->getCreatedAt(), $now);
            if ($weight <= 0.0) {
                continue;
            }

            foreach ($embedding as $index => $value) {
                $sum[$index] += (float) $value * $weight;
            }

            $weightSum += $weight;
        }

        if ($weightSum <= 0.0 || $dimensions === null) {
            return null;
        }

        return array_map(static fn (float $value) => $value / $weightSum, $sum);
    }

    private function weightForInteraction(\DateTimeImmutable $createdAt, \DateTimeImmutable $now): float
    {
        $days = (int) $createdAt->diff($now)->format('%a');

        if ($days <= 7) {
            return 1.0;
        }
        if ($days > 90) {
            return 0.2;
        }
        if ($days > 30) {
            return 0.5;
        }

        return 1.0;
    }
}

