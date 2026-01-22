<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Favorite\ListUserFavoritesQuery;
use App\Entity\Favorite;
use App\Repository\FavoriteRepository;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListUserFavoritesHandler
{
    public function __construct(
        private readonly FavoriteRepository $favoriteRepository
    ) {
    }

    public function __invoke(ListUserFavoritesQuery $query): array
    {
        $favorites = $this->favoriteRepository->createQueryBuilder('f')
            ->leftJoin('f.user', 'u')->addSelect('u')
            ->leftJoin('f.book', 'b')->addSelect('b')
            ->where('u.id = :userId')
            ->setParameter('userId', $query->userId)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return [
            'data' => $favorites,
            'meta' => [
                'total' => count($favorites)
            ]
        ];
    }
}
