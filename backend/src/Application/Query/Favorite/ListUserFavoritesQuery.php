<?php
declare(strict_types=1);
namespace App\Application\Query\Favorite;

class ListUserFavoritesQuery
{
    public function __construct(
        public readonly int $userId,
        public readonly int $page = 1,
        public readonly int $limit = 20
    ) {
    }
}
