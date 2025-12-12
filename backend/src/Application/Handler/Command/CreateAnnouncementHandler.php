<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Announcement\CreateAnnouncementCommand;
use App\Entity\Announcement;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
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
            throw new \RuntimeException('User not found');
        }

        $announcement = new Announcement();
        $announcement->setTitle($command->title);
        $announcement->setContent($command->content);
        $announcement->setCreatedBy($user);

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

        $this->entityManager->persist($announcement);
        $this->entityManager->flush();

        return $announcement;
    }
}
