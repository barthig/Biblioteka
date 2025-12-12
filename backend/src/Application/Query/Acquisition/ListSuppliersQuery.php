<?php
namespace App\Application\Query\Acquisition;

class ListSuppliersQuery
{
    public function __construct(public readonly ?bool $active = null)
    {
    }
}
