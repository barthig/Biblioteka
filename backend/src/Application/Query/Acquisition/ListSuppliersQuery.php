<?php
declare(strict_types=1);
namespace App\Application\Query\Acquisition;

class ListSuppliersQuery
{
    public function __construct(public readonly ?bool $active = null)
    {
    }
}
