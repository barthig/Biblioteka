<?php
declare(strict_types=1);
namespace App\Message;

final class UpdateBookEmbeddingMessage
{
    public function __construct(private int $bookId)
    {
    }

    public function getBookId(): int
    {
        return $this->bookId;
    }
}
