<?php

namespace App\Tests\Application\Handler;

use App\Application\Command\Announcement\DeleteAnnouncementCommand;
use App\Application\Handler\Command\DeleteAnnouncementHandler;
use App\Entity\Announcement;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DeleteAnnouncementHandlerTest extends TestCase
{
    public function testHandleDeletesAnnouncement(): void
    {
        $announcement = new Announcement();
        $announcement->setTitle('To Delete');
        $announcement->setContent('Content');
        $announcement->setStatus('draft');

        $repository = $this->createMock(AnnouncementRepository::class);
        $repository->expects($this->once())->method('find')->with(1)->willReturn($announcement);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($announcement);
        $entityManager->expects($this->once())->method('flush');

        $handler = new DeleteAnnouncementHandler($entityManager, $repository);
        $command = new DeleteAnnouncementCommand(1);

        ($handler)($command);
        
        // If no exception thrown, test passes
        $this->assertTrue(true);
    }

    public function testHandleThrowsExceptionWhenAnnouncementNotFound(): void
    {
        $repository = $this->createMock(AnnouncementRepository::class);
        $repository->expects($this->once())->method('find')->with(999)->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $handler = new DeleteAnnouncementHandler($entityManager, $repository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Announcement not found');

        $command = new DeleteAnnouncementCommand(999);
        ($handler)($command);
    }

    public function testHandleCanDeletePublishedAnnouncement(): void
    {
        $announcement = new Announcement();
        $announcement->setTitle('Published to Delete');
        $announcement->setContent('Content');
        $announcement->setStatus('published');

        $repository = $this->createMock(AnnouncementRepository::class);
        $repository->method('find')->willReturn($announcement);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove');
        $entityManager->expects($this->once())->method('flush');

        $handler = new DeleteAnnouncementHandler($entityManager, $repository);
        $command = new DeleteAnnouncementCommand(1);

        ($handler)($command);
        
        $this->assertTrue(true);
    }
}
