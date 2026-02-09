<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Book\ListBooksQuery;
use App\Entity\Book;
use App\Entity\Favorite;
use App\Repository\BookRepository;
use App\Repository\FavoriteRepository;
use App\Repository\RatingRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListBooksHandler
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly ManagerRegistry $doctrine,
        private readonly RatingRepository $ratingRepository
    ) {
    }

    public function __invoke(ListBooksQuery $query): array
    {
        $available = $query->available;
        if (is_string($available)) {
            $available = filter_var($available, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $filters = [
            'q' => $query->q,
            'authorId' => $query->authorId,
            'categoryId' => $query->categoryId,
            'publisher' => $query->publisher,
            'resourceType' => $query->resourceType,
            'signature' => $query->signature,
            'yearFrom' => $query->yearFrom,
            'yearTo' => $query->yearTo,
            'ageGroup' => $query->ageGroup,
            'available' => $available,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        $result = $this->bookRepository->searchPublic($filters);
        $books = $result['data'];

        // Add rating information for all books in one query
        if (!empty($books)) {
            $bookIds = [];
            foreach ($books as $book) {
                if ($book->getId() !== null) {
                    $bookIds[] = $book->getId();
                }
            }
            
            if (!empty($bookIds)) {
                $ratingStats = $this->ratingRepository->getRatingStatsForBooks($bookIds);
                
                foreach ($books as $book) {
                    $bookId = $book->getId();
                    if ($bookId !== null && isset($ratingStats[$bookId])) {
                        $stats = $ratingStats[$bookId];
                        if ($stats['avg'] !== null) {
                            $book->setAverageRating($stats['avg']);
                        }
                        $book->setRatingCount($stats['count']);
                    }
                }
            }
        }

        // Mark favorites if userId provided
        if ($query->userId !== null && !empty($books)) {
            $user = $this->doctrine->getRepository(\App\Entity\User::class)->find($query->userId);
            if ($user) {
                /** @var FavoriteRepository $favoriteRepo */
                $favoriteRepo = $this->doctrine->getRepository(Favorite::class);
                $favoriteBookIds = $favoriteRepo->getBookIdsForUser($user);
                if (!empty($favoriteBookIds)) {
                    $favoriteLookup = array_flip($favoriteBookIds);
                    foreach ($books as $book) {
                        $bookId = $book->getId();
                        if ($bookId !== null && isset($favoriteLookup[$bookId])) {
                            $book->setIsFavorite(true);
                        }
                    }
                }
            }
        }

        return [
            'data' => $books,
            'meta' => $result['meta']
        ];
    }
}
