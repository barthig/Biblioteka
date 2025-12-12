<?php
namespace App\Application\Command\Acquisition;

class DeactivateSupplierCommand
{
    public function __construct(public readonly int $id)
    {
    }
}
