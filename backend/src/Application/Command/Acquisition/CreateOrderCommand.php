<?php
declare(strict_types=1);
namespace App\Application\Command\Acquisition;

class CreateOrderCommand
{
    public function __construct(
        public readonly int $supplierId,
        public readonly ?int $budgetId,
        public readonly string $title,
        public readonly string $totalAmount,
        public readonly string $currency,
        public readonly ?string $description,
        public readonly ?string $referenceNumber,
        /** @var array<int, array<string, mixed>>|null */
        public readonly ?array $items,
        public readonly ?string $expectedAt,
        public readonly ?string $status
    ) {
    }
}
