<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Announcement\ArchiveAnnouncementCommand;
use App\Entity\Announcement;
use App\Exception\NotFoundException;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ArchiveAnnouncementHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AnnouncementRepository $repository
    ) {
    }

    public function __invoke(ArchiveAnnouncementCommand $command): Announcement
    {
        $announcement = $this->repository->find($command->id);
        
        if (!$announcement) {
            throw NotFoundException::forEntity('Announcement', $command->id);
        }

        $announcement->archive();
        $this->entityManager->flush();

        return $announcement;
    }
}
