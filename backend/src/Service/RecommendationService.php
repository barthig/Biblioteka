<?php
namespace App\Service;

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
        $positiveBooks = $this->interactionRepository->findPositiveBooks($user, 4);
        $vector = $this->averageEmbedding($positiveBooks);

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
     * @param Book[] $books
     * @return float[]|null
     */
    private function averageEmbedding(array $books): ?array
    {
        $sum = [];
        $count = 0;
        $dimensions = null;

        foreach ($books as $book) {
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

            foreach ($embedding as $index => $value) {
                $sum[$index] += (float) $value;
            }

            ++$count;
        }

        if ($count === 0 || $dimensions === null) {
            return null;
        }

        return array_map(static fn (float $value) => $value / $count, $sum);
    }
}
