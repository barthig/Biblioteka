<?php

namespace App\Tests\Application\Handler;

use App\Application\Command\Announcement\ArchiveAnnouncementCommand;
use App\Application\Handler\Command\ArchiveAnnouncementHandler;
use App\Entity\Announcement;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class ArchiveAnnouncementHandlerTest extends TestCase
{
    public function testHandleArchivesPublishedAnnouncement(): void
    {
        $announcement = new Announcement();
        $announcement->setTitle('Published Announcement');
        $announcement->setContent('Content');
        $announcement->setStatus('published');

        $repository = $this->createMock(AnnouncementRepository::class);
        $repository->expects($this->once())->method('find')->with(1)->willReturn($announcement);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $handler = new ArchiveAnnouncementHandler($entityManager, $repository);
        $command = new ArchiveAnnouncementCommand(1);

        $result = ($handler)($command);

        $this->assertEquals('archived', $result->getStatus());
        $this->assertNotNull($result->getUpdatedAt());
    }

    public function testHandleThrowsExceptionWhenAnnouncementNotFound(): void
    {
        $repository = $this->createMock(AnnouncementRepository::class);
        $repository->expects($this->once())->method('find')->with(999)->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $handler = new ArchiveAnnouncementHandler($entityManager, $repository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Announcement not found');

        $command = new ArchiveAnnouncementCommand(999);
        ($handler)($command);
    }

    public function testHandleArchivesDraftAnnouncement(): void
    {
        $announcement = new Announcement();
        $announcement->setTitle('Draft Announcement');
        $announcement->setContent('Content');
        $announcement->setStatus('draft');

        $repository = $this->createMock(AnnouncementRepository::class);
        $repository->method('find')->willReturn($announcement);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $handler = new ArchiveAnnouncementHandler($entityManager, $repository);
        $command = new ArchiveAnnouncementCommand(1);

        $result = ($handler)($command);

        $this->assertEquals('archived', $result->getStatus());
    }
}
