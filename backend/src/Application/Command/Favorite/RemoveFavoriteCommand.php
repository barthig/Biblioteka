<?php
declare(strict_types=1);
namespace App\Application\Command\Favorite;

class RemoveFavoriteCommand
{
    public function __construct(
        public readonly int $favoriteId,
        public readonly int $userId
    ) {
    }
}
