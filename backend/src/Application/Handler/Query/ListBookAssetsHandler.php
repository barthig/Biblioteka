<?php
namespace App\Application\Handler\Query;

use App\Application\Query\BookAsset\ListBookAssetsQuery;
use App\Entity\BookDigitalAsset;
use App\Exception\NotFoundException;
use App\Repository\BookDigitalAssetRepository;
use App\Repository\BookRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListBookAssetsHandler
{
    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly BookDigitalAssetRepository $assetRepository
    ) {
    }

    public function __invoke(ListBookAssetsQuery $query): array
    {
        $book = $this->bookRepository->find($query->bookId);
        if (!$book) {
            throw NotFoundException::forBook($query->bookId);
        }

        $items = array_map(
            fn (BookDigitalAsset $asset) => [
                'id' => $asset->getId(),
                'label' => $asset->getLabel(),
                'filename' => $asset->getOriginalFilename(),
                'mimeType' => $asset->getMimeType(),
                'size' => $asset->getSize(),
                'createdAt' => $asset->getCreatedAt()->format(DATE_ATOM),
            ],
            $this->assetRepository->findForBook($book)
        );

        return ['items' => $items];
    }
}
