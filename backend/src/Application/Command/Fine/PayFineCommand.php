<?php
namespace App\Application\Command\Fine;

class PayFineCommand
{
    public function __construct(
        public readonly int $fineId,
        public readonly int $userId,
        public readonly bool $isLibrarian
    ) {
    }
}
