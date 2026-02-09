<?php
declare(strict_types=1);
namespace App\Application\Query\BookAsset;

class GetBookAssetQuery
{
    public function __construct(
        public readonly int $bookId,
        public readonly int $assetId
    ) {
    }
}
