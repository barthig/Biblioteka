<?php
declare(strict_types=1);
namespace App\Application\Command\BookInventory;

class CreateBookCopyCommand
{
    public function __construct(
        public readonly int $bookId,
        public readonly ?string $inventoryCode = null,
        public readonly string $status = 'AVAILABLE',
        public readonly string $accessType = 'STORAGE',
        public readonly ?string $location = null,
        public readonly ?string $condition = null
    ) {
    }
}
