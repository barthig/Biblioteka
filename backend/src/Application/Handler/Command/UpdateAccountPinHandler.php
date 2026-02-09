<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Account\UpdateAccountPinCommand;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class UpdateAccountPinHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(UpdateAccountPinCommand $command): \App\Entity\User
    {
        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        if ($user->getPin() !== $command->currentPin) {
            throw new BadRequestHttpException('Current PIN is incorrect');
        }

        if (!preg_match('/^[0-9]{4}$/', $command->newPin)) {
            throw new BadRequestHttpException('PIN must be 4 digits');
        }

        $user->setPin($command->newPin);
        $this->entityManager->flush();

        return $user;
    }
}
