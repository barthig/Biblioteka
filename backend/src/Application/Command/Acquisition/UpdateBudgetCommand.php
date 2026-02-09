<?php
declare(strict_types=1);
namespace App\Application\Command\Acquisition;

class UpdateBudgetCommand
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly ?string $fiscalYear,
        public readonly ?string $allocatedAmount,
        public readonly ?string $currency
    ) {
    }
}
