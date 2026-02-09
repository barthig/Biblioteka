<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Announcement\CreateAnnouncementCommand;
use App\Entity\Announcement;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class CreateAnnouncementHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository
    ) {
    }

    public function __invoke(CreateAnnouncementCommand $command): Announcement
    {
        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw NotFoundException::forUser($command->userId);
        }

        $announcement = new Announcement();
        $announcement->setTitle($command->title);
        $announcement->setContent($command->content);
        $announcement->setCreatedBy($user);
        $announcement->setLocation($command->location);

        if ($command->type) {
            $announcement->setType($command->type);
        }

        if ($command->isPinned !== null) {
            $announcement->setIsPinned($command->isPinned);
        }

        if ($command->showOnHomepage !== null) {
            $announcement->setShowOnHomepage($command->showOnHomepage);
        }

        if ($command->targetAudience) {
            $announcement->setTargetAudience($command->targetAudience);
        }

        if ($command->expiresAt) {
            $announcement->setExpiresAt(new \DateTimeImmutable($command->expiresAt));
        }

        if ($command->eventAt) {
            $eventAt = new \DateTimeImmutable($command->eventAt);
            $now = new \DateTimeImmutable();
            if ($eventAt <= $now) {
                throw ValidationException::forField('eventAt', 'Event date must be in the future');
            }
            $announcement->setEventAt($eventAt);
        }

        $this->entityManager->persist($announcement);
        $this->entityManager->flush();

        return $announcement;
    }
}
