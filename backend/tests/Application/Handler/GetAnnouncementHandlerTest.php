<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\GetAnnouncementHandler;
use App\Application\Query\Announcement\GetAnnouncementQuery;
use App\Entity\Announcement;
use App\Repository\AnnouncementRepository;
use PHPUnit\Framework\TestCase;

class GetAnnouncementHandlerTest extends TestCase
{
    private AnnouncementRepository $announcementRepository;
    private GetAnnouncementHandler $handler;

    protected function setUp(): void
    {
        $this->announcementRepository = $this->createMock(AnnouncementRepository::class);
        $this->handler = new GetAnnouncementHandler($this->announcementRepository);
    }

    public function testGetAnnouncementSuccess(): void
    {
        $announcement = $this->createMock(Announcement::class);
        $announcement->method('isActive')->willReturn(true);
        $announcement->method('isVisibleForUser')->willReturn(true);
        
        $this->announcementRepository->method('find')->with(1)->willReturn($announcement);

        $query = new GetAnnouncementQuery(id: 1, user: null, isLibrarian: false);
        $result = ($this->handler)($query);

        $this->assertSame($announcement, $result);
    }
}
