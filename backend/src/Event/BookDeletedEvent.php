<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a book is deleted from the catalog.
 * Consumed by Recommendation Service to remove its embedding.
 */
final class BookDeletedEvent extends Event
{
    public const NAME = 'book.deleted';

    public function __construct(
        private readonly int $bookId,
        private readonly string $bookTitle,
    ) {
    }

    public function getBookId(): int
    {
        return $this->bookId;
    }

    public function getBookTitle(): string
    {
        return $this->bookTitle;
    }
}
