<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Account\UpdateAccountContactCommand;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class UpdateAccountContactHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(UpdateAccountContactCommand $command): \App\Entity\User
    {
        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        if ($command->phoneNumber !== null) {
            $user->setPhoneNumber($command->phoneNumber);
        }
        if ($command->addressLine !== null) {
            $user->setAddressLine($command->addressLine);
        }
        if ($command->city !== null) {
            $user->setCity($command->city);
        }
        if ($command->postalCode !== null) {
            $user->setPostalCode($command->postalCode);
        }

        $this->entityManager->flush();

        return $user;
    }
}
