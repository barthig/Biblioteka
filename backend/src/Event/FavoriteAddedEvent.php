<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Favorite;
use Symfony\Contracts\EventDispatcher\Event;

final class FavoriteAddedEvent extends Event
{
    public const NAME = 'favorite.added';

    public function __construct(
        private readonly Favorite $favorite,
    ) {
    }

    public function getFavorite(): Favorite
    {
        return $this->favorite;
    }

    public function getUserId(): ?int
    {
        return $this->favorite->getUser()->getId();
    }

    public function getBookId(): ?int
    {
        return $this->favorite->getBook()->getId();
    }
}
