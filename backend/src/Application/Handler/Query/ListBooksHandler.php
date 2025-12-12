<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Book\ListBooksQuery;
use App\Entity\Book;
use App\Entity\Favorite;
use App\Repository\BookRepository;
use App\Repository\FavoriteRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListBooksHandler
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly ManagerRegistry $doctrine
    ) {
    }

    public function __invoke(ListBooksQuery $query): array
    {
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
            'available' => $query->available,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        $result = $this->bookRepository->searchPublic($filters);
        $books = $result['data'];

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
                        if ($book instanceof Book && $book->getId() !== null && isset($favoriteLookup[$book->getId()])) {
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
