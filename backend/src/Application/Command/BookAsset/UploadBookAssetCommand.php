<?php
namespace App\Application\Command\BookAsset;

class UploadBookAssetCommand
{
    public function __construct(
        public readonly int $bookId,
        public readonly string $label,
        public readonly string $originalFilename,
        public readonly string $mimeType,
        public readonly string $content
    ) {
    }
}
