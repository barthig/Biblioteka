<?php
namespace App\Application\Command\Acquisition;

class CancelOrderCommand
{
    public function __construct(public readonly int $id)
    {
    }
}
