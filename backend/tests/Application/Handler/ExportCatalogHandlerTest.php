<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ExportCatalogHandler;
use App\Application\Query\Catalog\ExportCatalogQuery;
use App\Repository\BookRepository;
use PHPUnit\Framework\TestCase;

class ExportCatalogHandlerTest extends TestCase
{
    private BookRepository $bookRepository;
    private ExportCatalogHandler $handler;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->handler = new ExportCatalogHandler($this->bookRepository);
    }

    public function testExportCatalogSuccess(): void
    {
        $this->bookRepository->method('findBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn([]);

        $query = new ExportCatalogQuery();
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
