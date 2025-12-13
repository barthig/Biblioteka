<?php

namespace App\Application\Handler\Command;

use App\Application\Command\User\UpdateUserCommand;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateUserCommandHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em
    ) {
    }

    public function __invoke(UpdateUserCommand $command)
    {
        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        if ($command->name !== null) {
            $user->setName($command->name);
        }

        if ($command->email !== null) {
            $user->setEmail($command->email);
        }

        if ($command->roles !== null) {
            $user->setRoles($command->roles);
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

        if ($command->pesel !== null) {
            $user->setPesel($command->pesel);
        }

        if ($command->cardNumber !== null) {
            $user->setCardNumber($command->cardNumber);
        }

        if ($command->pendingApproval !== null) {
            $user->setPendingApproval($command->pendingApproval);
        }

        if ($command->verified !== null) {
            $user->setVerified($command->verified);
        }

        if ($command->blocked !== null) {
            $user->setBlocked($command->blocked);
        }

        if ($command->blockedReason !== null) {
            $user->setBlockedReason($command->blockedReason);
        }

        $this->em->flush();

        return $user;
    }
}
