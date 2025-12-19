<?php
namespace App\Application\Command\Acquisition;

class ReceiveOrderCommand
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $receivedAt,
        public readonly ?string $totalAmount,
        /** @var array<int, array<string, mixed>>|null */
        public readonly ?array $items,
        public readonly ?string $expenseAmount,
        public readonly ?string $expenseDescription
    ) {
    }
}
