<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListBookAssetsHandler;
use App\Application\Query\BookAsset\ListBookAssetsQuery;
use App\Repository\BookDigitalAssetRepository;
use App\Repository\BookRepository;
use PHPUnit\Framework\TestCase;

class ListBookAssetsHandlerTest extends TestCase
{
    private BookDigitalAssetRepository $bookAssetRepository;
    private BookRepository $bookRepository;
    private ListBookAssetsHandler $handler;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->bookAssetRepository = $this->createMock(BookDigitalAssetRepository::class);
        $this->handler = new ListBookAssetsHandler($this->bookRepository, $this->bookAssetRepository);
    }

    public function testListBookAssetsSuccess(): void
    {
        $book = $this->createMock(\App\Entity\Book::class);
        
        $this->bookRepository->method('find')->with(1)->willReturn($book);
        $this->bookAssetRepository->method('findBy')->willReturn([]);

        $query = new ListBookAssetsQuery(bookId: 1);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
