<?php
namespace App\Application\Command\Acquisition;

class AddBudgetExpenseCommand
{
    public function __construct(
        public readonly int $budgetId,
        public readonly string $amount,
        public readonly string $description,
        public readonly ?string $type,
        public readonly ?string $postedAt
    ) {
    }
}
