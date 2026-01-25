<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Announcement\PublishAnnouncementCommand;
use App\Entity\Announcement;
use App\Repository\AnnouncementRepository;
use App\Service\User\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PublishAnnouncementHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AnnouncementRepository $repository,
        private readonly NotificationService $notificationService
    ) {
    }

    public function __invoke(PublishAnnouncementCommand $command): Announcement
    {
        $announcement = $this->repository->find($command->id);
        
        if (!$announcement) {
            throw new \RuntimeException('Announcement not found');
        }

        $wasPublished = $announcement->getStatus() === 'published';
        $announcement->publish();
        $this->entityManager->flush();

        if (!$wasPublished) {
            $this->notificationService->notifyAnnouncementPublished($announcement);
        }

        return $announcement;
    }
}
