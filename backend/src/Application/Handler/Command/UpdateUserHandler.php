<?php
namespace App\Application\Handler\Command;

use App\Application\Command\User\UpdateUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateUserHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository
    ) {
    }

    public function __invoke(UpdateUserCommand $command): User
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
            $user->setRoles(array_values(array_unique($command->roles)));
        }

        if ($command->accountStatus !== null) {
            $user->setAccountStatus($command->accountStatus);
        }

        if ($command->pesel !== null) {
            $user->setPesel($command->pesel);
        }

        if ($command->cardNumber !== null) {
            $user->setCardNumber($command->cardNumber);
        }

        // Apply contact data
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

        if ($command->pendingApproval !== null) {
            $user->setPendingApproval($command->pendingApproval);
        }

        if ($command->verified !== null) {
            if ($command->verified) {
                $user->markVerified();
            } else {
                $user->requireVerification();
            }
        }

        if ($command->membershipGroup !== null) {
            try {
                $user->setMembershipGroup($command->membershipGroup);
            } catch (\InvalidArgumentException $exception) {
                throw new \RuntimeException('Unknown membership group');
            }
        }

        if ($command->loanLimit !== null) {
            $user->setLoanLimit($command->loanLimit);
        }

        if ($command->blocked !== null) {
            if ($command->blocked) {
                $user->block($command->blockedReason);
            } else {
                $user->unblock();
            }
        } elseif ($command->blockedReason !== null) {
            $user->setBlockedReason($command->blockedReason);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
