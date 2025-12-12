<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\BookAsset\UploadBookAssetCommand;
use App\Application\Handler\Command\UploadBookAssetHandler;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class UploadBookAssetHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private BookRepository $bookRepository;
    private KernelInterface $kernel;
    private UploadBookAssetHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->handler = new UploadBookAssetHandler($this->entityManager, $this->bookRepository, $this->kernel);
    }

    public function testUploadBookAssetSuccess(): void
    {
        $book = $this->createMock(\App\Entity\Book::class);
        
        $this->bookRepository->method('find')->with(1)->willReturn($book);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new UploadBookAssetCommand(
            bookId: 1,
            label: 'cover',
            originalFilename: 'file.jpg',
            mimeType: 'image/jpeg',
            content: base64_encode('test file content')
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(\App\Entity\BookDigitalAsset::class, $result);
    }
}
