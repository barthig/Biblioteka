<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Rating;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a user rates a book.
 * Consumed by Recommendation Service to update user interaction data.
 */
final class RatingCreatedEvent extends Event
{
    public const NAME = 'rating.created';

    public function __construct(
        private readonly Rating $rating,
    ) {
    }

    public function getRating(): Rating
    {
        return $this->rating;
    }

    public function getUserId(): ?int
    {
        return $this->rating->getUser()?->getId();
    }

    public function getBookId(): ?int
    {
        return $this->rating->getBook()?->getId();
    }

    public function getRatingValue(): int
    {
        return $this->rating->getRating();
    }
}
