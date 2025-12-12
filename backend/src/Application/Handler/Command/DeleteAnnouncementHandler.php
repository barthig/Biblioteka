<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Announcement\DeleteAnnouncementCommand;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteAnnouncementHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AnnouncementRepository $repository
    ) {
    }

    public function __invoke(DeleteAnnouncementCommand $command): void
    {
        $announcement = $this->repository->find($command->id);
        
        if (!$announcement) {
            throw new \RuntimeException('Announcement not found');
        }

        $this->entityManager->remove($announcement);
        $this->entityManager->flush();
    }
}
