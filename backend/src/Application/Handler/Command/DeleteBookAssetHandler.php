<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\BookAsset\DeleteBookAssetCommand;
use App\Exception\NotFoundException;
use App\Repository\BookDigitalAssetRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteBookAssetHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookRepository $bookRepository,
        private readonly BookDigitalAssetRepository $assetRepository,
        private readonly KernelInterface $kernel
    ) {
    }

    public function __invoke(DeleteBookAssetCommand $command): void
    {
        $book = $this->bookRepository->find($command->bookId);
        if (!$book) {
            throw NotFoundException::forBook($command->bookId);
        }

        $asset = $this->assetRepository->find($command->assetId);
        if (!$asset || $asset->getBook()->getId() !== $book->getId()) {
            throw NotFoundException::forEntity('BookDigitalAsset', $command->assetId);
        }

        $path = $this->assetDirectory() . DIRECTORY_SEPARATOR . $asset->getStorageName();
        if (is_file($path)) {
            @unlink($path);
        }

        $book->removeDigitalAsset($asset);
        $this->entityManager->remove($asset);
        $this->entityManager->flush();
    }

    private function assetDirectory(): string
    {
        return $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'digital-assets';
    }
}
