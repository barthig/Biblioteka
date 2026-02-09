<?php
declare(strict_types=1);
namespace App\Application\Command\Weeding;

class CreateWeedingRecordCommand
{
    public function __construct(
        public readonly int $bookId,
        public readonly ?int $copyId,
        public readonly string $reason,
        public readonly ?string $action,
        public readonly ?string $conditionState,
        public readonly ?string $notes,
        public readonly ?string $removedAt,
        public readonly ?int $userId
    ) {
    }
}
