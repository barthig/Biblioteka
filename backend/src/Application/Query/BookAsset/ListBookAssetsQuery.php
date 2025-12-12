<?php
namespace App\Application\Query\BookAsset;

class ListBookAssetsQuery
{
    public function __construct(public readonly int $bookId)
    {
    }
}
