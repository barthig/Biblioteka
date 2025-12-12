<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Announcement\PublishAnnouncementCommand;
use App\Entity\Announcement;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PublishAnnouncementHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AnnouncementRepository $repository
    ) {
    }

    public function __invoke(PublishAnnouncementCommand $command): Announcement
    {
        $announcement = $this->repository->find($command->id);
        
        if (!$announcement) {
            throw new \RuntimeException('Announcement not found');
        }

        $announcement->publish();
        $this->entityManager->flush();

        return $announcement;
    }
}
