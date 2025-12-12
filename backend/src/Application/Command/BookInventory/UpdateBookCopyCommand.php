<?php
namespace App\Application\Command\BookInventory;

class UpdateBookCopyCommand
{
    public function __construct(
        public readonly int $bookId,
        public readonly int $copyId,
        public readonly ?string $status = null,
        public readonly ?string $accessType = null,
        public readonly ?string $location = null,
        public readonly ?string $condition = null
    ) {
    }
}
