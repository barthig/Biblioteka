<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Book;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a new book is created in the catalog.
 * Consumed by Recommendation Service to create initial embeddings.
 */
final class BookCreatedEvent extends Event
{
    public const NAME = 'book.created';

    public function __construct(
        private readonly Book $book,
    ) {
    }

    public function getBook(): Book
    {
        return $this->book;
    }

    public function getBookId(): ?int
    {
        return $this->book->getId();
    }
}
