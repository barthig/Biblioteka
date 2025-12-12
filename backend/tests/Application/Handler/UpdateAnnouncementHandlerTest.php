<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Announcement\UpdateAnnouncementCommand;
use App\Application\Handler\Command\UpdateAnnouncementHandler;
use App\Entity\Announcement;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateAnnouncementHandlerTest extends TestCase
{
    private AnnouncementRepository $announcementRepository;
    private EntityManagerInterface $entityManager;
    private UpdateAnnouncementHandler $handler;

    protected function setUp(): void
    {
        $this->announcementRepository = $this->createMock(AnnouncementRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new UpdateAnnouncementHandler($this->announcementRepository, $this->entityManager);
    }

    public function testUpdateAnnouncementSuccess(): void
    {
        $announcement = $this->createMock(Announcement::class);
        $announcement->expects($this->once())->method('setTitle')->with('Updated Title');
        
        $this->announcementRepository->method('find')->with(1)->willReturn($announcement);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new UpdateAnnouncementCommand(announcementId: 1, title: 'Updated Title');
        $result = ($this->handler)($command);

        $this->assertSame($announcement, $result);
    }
}
