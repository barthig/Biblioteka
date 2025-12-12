<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListBookAssetsHandler;
use App\Application\Query\BookAsset\ListBookAssetsQuery;
use App\Repository\BookAssetRepository;
use PHPUnit\Framework\TestCase;

class ListBookAssetsHandlerTest extends TestCase
{
    private BookAssetRepository $bookAssetRepository;
    private ListBookAssetsHandler $handler;

    protected function setUp(): void
    {
        $this->bookAssetRepository = $this->createMock(BookAssetRepository::class);
        $this->handler = new ListBookAssetsHandler($this->bookAssetRepository);
    }

    public function testListBookAssetsSuccess(): void
    {
        $this->bookAssetRepository->method('findBy')->willReturn([]);

        $query = new ListBookAssetsQuery(bookId: 1);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
