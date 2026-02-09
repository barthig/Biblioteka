<?php
declare(strict_types=1);
namespace App\Application\Command\BookInventory;

class DeleteBookCopyCommand
{
    public function __construct(
        public readonly int $bookId,
        public readonly int $copyId
    ) {
    }
}
