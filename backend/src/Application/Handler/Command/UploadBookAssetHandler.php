<?php
namespace App\Application\Handler\Command;

use App\Application\Command\BookAsset\UploadBookAssetCommand;
use App\Entity\BookDigitalAsset;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UploadBookAssetHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookRepository $bookRepository,
        private readonly KernelInterface $kernel
    ) {
    }

    public function __invoke(UploadBookAssetCommand $command): BookDigitalAsset
    {
        $book = $this->bookRepository->find($command->bookId);
        if (!$book) {
            throw new \RuntimeException('Book not found');
        }

        $binary = base64_decode($command->content, true);
        if ($binary === false) {
            throw new \RuntimeException('Invalid base64 payload');
        }

        try {
            $storageName = $this->storeBinary($binary, pathinfo($command->originalFilename, PATHINFO_EXTENSION));
        } catch (\RuntimeException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        $asset = (new BookDigitalAsset())
            ->setBook($book)
            ->setLabel($command->label)
            ->setOriginalFilename($command->originalFilename)
            ->setMimeType($command->mimeType)
            ->setSize(strlen($binary))
            ->setStorageName($storageName);

        $book->addDigitalAsset($asset);
        $this->entityManager->persist($asset);
        $this->entityManager->flush();

        return $asset;
    }

    private function storeBinary(string $binary, string $extension): string
    {
        $extension = trim($extension) !== '' ? strtolower($extension) : 'bin';
        $filename = sprintf('%s.%s', bin2hex(random_bytes(12)), $extension);
        $dir = $this->assetDirectory();
        
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Unable to create asset storage directory: ' . $dir);
        }

        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        if (file_put_contents($path, $binary) === false) {
            throw new \RuntimeException('Unable to store uploaded asset');
        }

        return $filename;
    }

    private function assetDirectory(): string
    {
        return $this->kernel->getProjectDir() . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'digital-assets';
    }
}
