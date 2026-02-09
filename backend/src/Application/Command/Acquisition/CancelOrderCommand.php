<?php
declare(strict_types=1);
namespace App\Application\Command\Acquisition;

class CancelOrderCommand
{
    public function __construct(public readonly int $id)
    {
    }
}
