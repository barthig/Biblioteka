<?php
declare(strict_types=1);
namespace App\Application\Command\BookAsset;

class DeleteBookAssetCommand
{
    public function __construct(
        public readonly int $bookId,
        public readonly int $assetId
    ) {
    }
}
