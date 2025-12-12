<?php
namespace App\Application\Handler\Query;

use App\Application\Query\BookAsset\GetBookAssetQuery;
use App\Entity\BookDigitalAsset;
use App\Repository\BookDigitalAssetRepository;
use App\Repository\BookRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetBookAssetHandler
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly BookDigitalAssetRepository $assetRepository
    ) {
    }

    public function __invoke(GetBookAssetQuery $query): BookDigitalAsset
    {
        $book = $this->bookRepository->find($query->bookId);
        if (!$book) {
            throw new \RuntimeException('Book not found');
        }

        $asset = $this->assetRepository->find($query->assetId);
        if (!$asset || $asset->getBook()->getId() !== $book->getId()) {
            throw new \RuntimeException('Asset not found');
        }

        return $asset;
    }
}
