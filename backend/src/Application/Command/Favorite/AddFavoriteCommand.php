<?php
namespace App\Application\Command\Favorite;

class AddFavoriteCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly int $bookId
    ) {
    }
}
