<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Book;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a book's metadata is updated.
 * Consumed by Recommendation Service to refresh embeddings.
 */
final class BookUpdatedEvent extends Event
{
    public const NAME = 'book.updated';

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
