<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\GetBookAssetHandler;
use App\Application\Query\BookAsset\GetBookAssetQuery;
use App\Entity\BookAsset;
use App\Repository\BookAssetRepository;
use PHPUnit\Framework\TestCase;

class GetBookAssetHandlerTest extends TestCase
{
    private BookAssetRepository $bookAssetRepository;
    private GetBookAssetHandler $handler;

    protected function setUp(): void
    {
        $this->bookAssetRepository = $this->createMock(BookAssetRepository::class);
        $this->handler = new GetBookAssetHandler($this->bookAssetRepository);
    }

    public function testGetBookAssetSuccess(): void
    {
        $bookAsset = $this->createMock(BookAsset::class);
        $this->bookAssetRepository->method('find')->with(1)->willReturn($bookAsset);

        $query = new GetBookAssetQuery(assetId: 1);
        $result = ($this->handler)($query);

        $this->assertSame($bookAsset, $result);
    }
}
