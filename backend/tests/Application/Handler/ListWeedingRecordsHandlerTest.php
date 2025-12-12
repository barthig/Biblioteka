<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListWeedingRecordsHandler;
use App\Application\Query\WeedingRecord\ListWeedingRecordsQuery;
use App\Repository\WeedingRecordRepository;
use PHPUnit\Framework\TestCase;

class ListWeedingRecordsHandlerTest extends TestCase
{
    private WeedingRecordRepository $weedingRecordRepository;
    private ListWeedingRecordsHandler $handler;

    protected function setUp(): void
    {
        $this->weedingRecordRepository = $this->createMock(WeedingRecordRepository::class);
        $this->handler = new ListWeedingRecordsHandler($this->weedingRecordRepository);
    }

    public function testListWeedingRecordsSuccess(): void
    {
        $this->weedingRecordRepository->method('findBy')->willReturn([]);

        $query = new \App\Application\Query\Weeding\ListWeedingRecordsQuery();
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
