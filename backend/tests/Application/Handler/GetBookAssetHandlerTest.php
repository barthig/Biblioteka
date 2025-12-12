<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\GetBookAssetHandler;
use App\Application\Query\BookAsset\GetBookAssetQuery;
use App\Entity\BookDigitalAsset;
use App\Repository\BookDigitalAssetRepository;
use App\Repository\BookRepository;
use PHPUnit\Framework\TestCase;

class GetBookAssetHandlerTest extends TestCase
{
    private BookDigitalAssetRepository $bookAssetRepository;
    private BookRepository $bookRepository;
    private GetBookAssetHandler $handler;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->bookAssetRepository = $this->createMock(BookDigitalAssetRepository::class);
        $this->handler = new GetBookAssetHandler($this->bookRepository, $this->bookAssetRepository);
    }

    public function testGetBookAssetSuccess(): void
    {
        $book = $this->createMock(\App\Entity\Book::class);
        $bookAsset = $this->createMock(BookDigitalAsset::class);
        
        $this->bookRepository->method('find')->with(1)->willReturn($book);
        $this->bookAssetRepository->method('find')->with(1)->willReturn($bookAsset);

        $query = new GetBookAssetQuery(bookId: 1, assetId: 1);
        $result = ($this->handler)($query);

        $this->assertSame($bookAsset, $result);
    }
}
