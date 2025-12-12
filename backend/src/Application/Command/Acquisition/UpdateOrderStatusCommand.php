<?php
namespace App\Application\Command\Acquisition;

class UpdateOrderStatusCommand
{
    public function __construct(
        public readonly int $id,
        public readonly string $status,
        public readonly ?string $orderedAt,
        public readonly ?string $receivedAt,
        public readonly ?string $expectedAt,
        public readonly ?string $totalAmount,
        public readonly ?array $items
    ) {
    }
}
