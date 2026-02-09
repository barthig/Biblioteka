<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Announcement\UpdateAnnouncementCommand;
use App\Entity\Announcement;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateAnnouncementHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AnnouncementRepository $repository
    ) {
    }

    public function __invoke(UpdateAnnouncementCommand $command): Announcement
    {
        $announcement = $this->repository->find($command->id);
        
        if (!$announcement) {
            throw NotFoundException::forEntity('Announcement', $command->id);
        }

        if ($command->title !== null) {
            $announcement->setTitle($command->title);
        }

        if ($command->content !== null) {
            $announcement->setContent($command->content);
        }

        if ($command->location !== null) {
            $announcement->setLocation($command->location);
        }

        if ($command->type !== null) {
            $announcement->setType($command->type);
        }

        if ($command->isPinned !== null) {
            $announcement->setIsPinned($command->isPinned);
        }

        if ($command->showOnHomepage !== null) {
            $announcement->setShowOnHomepage($command->showOnHomepage);
        }

        if ($command->targetAudience !== null) {
            $announcement->setTargetAudience($command->targetAudience);
        }

        if ($command->expiresAt !== 'NOT_SET') {
            $announcement->setExpiresAt($command->expiresAt ? new \DateTimeImmutable($command->expiresAt) : null);
        }

        if ($command->eventAt !== 'NOT_SET') {
            if ($command->eventAt) {
                $eventAt = new \DateTimeImmutable($command->eventAt);
                $now = new \DateTimeImmutable();
                if ($eventAt <= $now) {
                    throw ValidationException::forField('eventAt', 'Event date must be in the future');
                }
                $announcement->setEventAt($eventAt);
            } else {
                $announcement->setEventAt(null);
            }
        }

        $this->entityManager->flush();

        return $announcement;
    }
}
