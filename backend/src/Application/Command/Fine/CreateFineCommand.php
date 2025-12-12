<?php
namespace App\Application\Command\Fine;

class CreateFineCommand
{
    public function __construct(
        public readonly int $loanId,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $reason
    ) {
    }
}
