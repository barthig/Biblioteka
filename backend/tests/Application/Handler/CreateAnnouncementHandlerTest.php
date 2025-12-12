<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Announcement\CreateAnnouncementCommand;
use App\Application\Handler\Command\CreateAnnouncementHandler;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateAnnouncementHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private CreateAnnouncementHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->handler = new CreateAnnouncementHandler($this->entityManager, $this->userRepository);
    }

    public function testCreateAnnouncementSuccess(): void
    {
        $user = $this->createMock(\App\Entity\User::class);
        $this->userRepository->method('find')->with(1)->willReturn($user);
        
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CreateAnnouncementCommand(
            userId: 1,
            title: 'Test Announcement',
            content: 'Test content',
            type: 'info',
            isPinned: false,
            showOnHomepage: false,
            targetAudience: null,
            expiresAt: null
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(\App\Entity\Announcement::class, $result);
    }
}
