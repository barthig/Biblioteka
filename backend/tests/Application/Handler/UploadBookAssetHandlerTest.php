<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\BookAsset\UploadBookAssetCommand;
use App\Application\Handler\Command\UploadBookAssetHandler;
use App\Entity\Book;
use App\Entity\BookAsset;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UploadBookAssetHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private BookRepository $bookRepository;
    private UploadBookAssetHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->handler = new UploadBookAssetHandler($this->entityManager, $this->bookRepository);
    }

    public function testUploadBookAssetSuccess(): void
    {
        $book = $this->createMock(Book::class);
        
        $this->bookRepository->method('find')->with(1)->willReturn($book);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new UploadBookAssetCommand(
            bookId: 1,
            assetType: 'cover',
            filePath: '/path/to/file.jpg'
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(BookAsset::class, $result);
    }
}
