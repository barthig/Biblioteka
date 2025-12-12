<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\BookAsset\DeleteBookAssetCommand;
use App\Application\Handler\Command\DeleteBookAssetHandler;
use App\Entity\BookAsset;
use App\Repository\BookAssetRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DeleteBookAssetHandlerTest extends TestCase
{
    private BookAssetRepository $bookAssetRepository;
    private EntityManagerInterface $entityManager;
    private DeleteBookAssetHandler $handler;

    protected function setUp(): void
    {
        $this->bookAssetRepository = $this->createMock(BookAssetRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new DeleteBookAssetHandler($this->bookAssetRepository, $this->entityManager);
    }

    public function testDeleteBookAssetSuccess(): void
    {
        $bookAsset = $this->createMock(BookAsset::class);
        $this->bookAssetRepository->method('find')->with(1)->willReturn($bookAsset);
        $this->entityManager->expects($this->once())->method('remove')->with($bookAsset);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new DeleteBookAssetCommand(assetId: 1);
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
