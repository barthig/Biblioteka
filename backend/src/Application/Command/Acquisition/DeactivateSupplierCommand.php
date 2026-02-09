<?php
declare(strict_types=1);
namespace App\Application\Command\Acquisition;

class DeactivateSupplierCommand
{
    public function __construct(public readonly int $id)
    {
    }
}
