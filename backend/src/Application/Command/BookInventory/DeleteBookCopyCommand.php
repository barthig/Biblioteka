<?php
namespace App\Application\Command\BookInventory;

class DeleteBookCopyCommand
{
    public function __construct(
        public readonly int $bookId,
        public readonly int $copyId
    ) {
    }
}
