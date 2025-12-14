<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Account\CompleteOnboardingCommand;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class CompleteOnboardingHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(CompleteOnboardingCommand $command): \App\Entity\User
    {
        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        if ($command->preferredCategories !== null) {
            $user->setPreferredCategories($command->preferredCategories);
        }

        $user->setOnboardingCompleted(true);
        $this->entityManager->flush();

        return $user;
    }
}
