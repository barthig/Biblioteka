<?php
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
