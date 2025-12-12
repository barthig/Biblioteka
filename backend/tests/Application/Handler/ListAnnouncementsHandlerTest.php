<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListAnnouncementsHandler;
use App\Application\Query\Announcement\ListAnnouncementsQuery;
use App\Repository\AnnouncementRepository;
use PHPUnit\Framework\TestCase;

class ListAnnouncementsHandlerTest extends TestCase
{
    private AnnouncementRepository $announcementRepository;
    private ListAnnouncementsHandler $handler;

    protected function setUp(): void
    {
        $this->announcementRepository = $this->createMock(AnnouncementRepository::class);
        $this->handler = new ListAnnouncementsHandler($this->announcementRepository);
    }

    public function testListAnnouncementsSuccess(): void
    {
        $this->announcementRepository->method('findBy')->willReturn([]);

        $query = new ListAnnouncementsQuery(page: 1, limit: 50);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
