<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\User\UnblockUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class UnblockUserHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository
    ) {
    }

    public function __invoke(UnblockUserCommand $command): User
    {
        $user = $this->userRepository->find($command->userId);
        
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        $user->unblock();

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
