<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\BookAsset\GetBookAssetQuery;
use App\Entity\BookDigitalAsset;
use App\Exception\NotFoundException;
use App\Repository\BookDigitalAssetRepository;
use App\Repository\BookRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
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
            throw NotFoundException::forBook($query->bookId);
        }

        $asset = $this->assetRepository->find($query->assetId);
        if (!$asset || $asset->getBook()->getId() !== $book->getId()) {
            throw NotFoundException::forEntity('BookDigitalAsset', $query->assetId);
        }

        return $asset;
    }
}
