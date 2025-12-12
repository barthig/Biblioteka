<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Book\GetBookQuery;
use App\Entity\Book;
use App\Entity\Favorite;
use App\Repository\BookRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetBookHandler
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly ManagerRegistry $doctrine
    ) {
    }

    public function __invoke(GetBookQuery $query): Book
    {
        $book = $this->bookRepository->find($query->bookId);
        
        if (!$book) {
            throw new \RuntimeException('Book not found');
        }

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
