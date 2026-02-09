<?php
declare(strict_types=1);
namespace App\Application\Command\Favorite;

class AddFavoriteCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly int $bookId
    ) {
    }
}
