<?php
namespace App\Application\Command\Acquisition;

class CreateBudgetCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $fiscalYear,
        public readonly string $allocatedAmount,
        public readonly string $currency,
        public readonly ?string $spentAmount
    ) {
    }
}
