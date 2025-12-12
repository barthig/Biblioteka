<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Announcement\CreateAnnouncementCommand;
use App\Application\Handler\Command\CreateAnnouncementHandler;
use App\Entity\Announcement;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateAnnouncementHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private CreateAnnouncementHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new CreateAnnouncementHandler($this->entityManager);
    }

    public function testCreateAnnouncementSuccess(): void
    {
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CreateAnnouncementCommand(
            title: 'Test Announcement',
            content: 'Test content',
            priority: 'normal'
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(Announcement::class, $result);
    }
}
