<?php
declare(strict_types=1);
namespace App\Application\Command\Fine;

class CancelFineCommand
{
    public function __construct(
        public readonly int $fineId
    ) {
    }
}
