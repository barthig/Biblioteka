<?php
namespace App\Application\Command\BookAsset;

class DeleteBookAssetCommand
{
    public function __construct(
        public readonly int $bookId,
        public readonly int $assetId
    ) {
    }
}
