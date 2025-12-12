<?php
namespace App\Application\Command\Book;

class DeleteBookCommand
{
    public function __construct(
        public readonly int $bookId
    ) {
    }
}
