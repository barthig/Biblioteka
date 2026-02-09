<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Book\GetBookQuery;
use App\Entity\Book;
use App\Entity\Favorite;
use App\Exception\NotFoundException;
use App\Repository\BookRepository;
use App\Repository\RatingRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetBookHandler
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly ManagerRegistry $doctrine,
        private readonly RatingRepository $ratingRepository
    ) {
    }

    public function __invoke(GetBookQuery $query): Book
    {
        $book = $this->bookRepository->find($query->bookId);
        
        if (!$book) {
            throw NotFoundException::forBook($query->bookId);
        }

        // Add rating information
        $averageRating = $this->ratingRepository->getAverageRatingForBook($book->getId());
        $ratingCount = $this->ratingRepository->getRatingCountForBook($book->getId());
        
        if ($averageRating !== null) {
            $book->setAverageRating($averageRating);
        }
        $book->setRatingCount($ratingCount);

        // Mark as favorite if userId provided
        if ($query->userId !== null) {
            $user = $this->doctrine->getRepository(\App\Entity\User::class)->find($query->userId);
            if ($user) {
                /** @var \App\Repository\FavoriteRepository $favoriteRepo */
                $favoriteRepo = $this->doctrine->getRepository(Favorite::class);
                $favoriteBookIds = $favoriteRepo->getBookIdsForUser($user);
                if (in_array($book->getId(), $favoriteBookIds, true)) {
                    $book->setIsFavorite(true);
                }
            }
        }

        return $book;
    }
}
