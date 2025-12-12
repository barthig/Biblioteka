<?php
namespace App\Application\Command\Favorite;

class RemoveFavoriteCommand
{
    public function __construct(
        public readonly int $favoriteId,
        public readonly int $userId
    ) {
    }
}
