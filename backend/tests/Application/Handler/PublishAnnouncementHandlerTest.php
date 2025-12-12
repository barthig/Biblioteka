<?php

namespace App\Tests\Application\Handler;

use App\Application\Command\Announcement\PublishAnnouncementCommand;
use App\Application\Handler\Command\PublishAnnouncementHandler;
use App\Entity\Announcement;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class PublishAnnouncementHandlerTest extends TestCase
{
    public function testHandlePublishesDraftAnnouncement(): void
    {
        $announcement = new Announcement();
        $announcement->setTitle('Test Announcement');
        $announcement->setContent('Content');
        $announcement->setStatus('draft');

        $repository = $this->createMock(AnnouncementRepository::class);
        $repository->expects($this->once())->method('find')->with(1)->willReturn($announcement);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $handler = new PublishAnnouncementHandler($entityManager, $repository);
        $command = new PublishAnnouncementCommand(1);

        $result = ($handler)($command);

        $this->assertEquals('published', $result->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getPublishedAt());
    }

    public function testHandleThrowsExceptionWhenAnnouncementNotFound(): void
    {
        $repository = $this->createMock(AnnouncementRepository::class);
        $repository->expects($this->once())->method('find')->with(999)->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $handler = new PublishAnnouncementHandler($entityManager, $repository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Announcement not found');

        $command = new PublishAnnouncementCommand(999);
        ($handler)($command);
    }

    public function testHandleCanRepublishArchivedAnnouncement(): void
    {
        $announcement = new Announcement();
        $announcement->setTitle('Archived Announcement');
        $announcement->setContent('Content');
        $announcement->setStatus('archived');

        $repository = $this->createMock(AnnouncementRepository::class);
        $repository->method('find')->willReturn($announcement);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $handler = new PublishAnnouncementHandler($entityManager, $repository);
        $command = new PublishAnnouncementCommand(1);

        $result = ($handler)($command);

        $this->assertEquals('published', $result->getStatus());
    }
}
