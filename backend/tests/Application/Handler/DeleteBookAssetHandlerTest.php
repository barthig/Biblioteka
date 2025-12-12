<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\BookAsset\DeleteBookAssetCommand;
use App\Application\Handler\Command\DeleteBookAssetHandler;
use App\Entity\BookDigitalAsset;
use App\Repository\BookDigitalAssetRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class DeleteBookAssetHandlerTest extends TestCase
{
    private BookDigitalAssetRepository $bookAssetRepository;
    private BookRepository $bookRepository;
    private EntityManagerInterface $entityManager;
    private KernelInterface $kernel;
    private DeleteBookAssetHandler $handler;

    protected function setUp(): void
    {
        $this->bookAssetRepository = $this->createMock(BookDigitalAssetRepository::class);
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->handler = new DeleteBookAssetHandler($this->entityManager, $this->bookRepository, $this->bookAssetRepository, $this->kernel);
    }

    public function testDeleteBookAssetSuccess(): void
    {
        $book = $this->createMock(\App\Entity\Book::class);
        $bookAsset = $this->createMock(BookDigitalAsset::class);
        
        $this->bookRepository->method('find')->with(1)->willReturn($book);
        $this->bookAssetRepository->method('find')->with(1)->willReturn($bookAsset);
        $this->entityManager->expects($this->once())->method('remove')->with($bookAsset);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new DeleteBookAssetCommand(bookId: 1, assetId: 1);
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
