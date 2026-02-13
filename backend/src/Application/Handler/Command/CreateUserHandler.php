<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\User\CreateUserCommand;
use App\Entity\User;
use App\Exception\BusinessLogicException;
use App\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler(bus: 'command.bus')]
class CreateUserHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function __invoke(CreateUserCommand $command): User
    {
        $user = (new User())
            ->setEmail($command->email)
            ->setName($command->name)
            ->setRoles($command->roles);

        $group = $command->membershipGroup ?? User::GROUP_STANDARD;
        try {
            $user->setMembershipGroup($group);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::forField('membershipGroup', 'Unknown membership group');
        }

        if ($command->loanLimit !== null) {
            $user->setLoanLimit($command->loanLimit);
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

        if ($command->blocked) {
            $user->block($command->blockedReason);
        }

        // Validate password policy
        if (strlen($command->password) < 10) {
            throw ValidationException::forField('password', 'Password must be at least 10 characters');
        }
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $command->password)) {
            throw ValidationException::forField('password', 'Password must contain lowercase, uppercase letters and a digit');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $command->password));
        $user->setPendingApproval($command->pendingApproval);

        if ($command->verified) {
            $user->markVerified();
        } else {
            $user->requireVerification();
        }

        $user->recordPrivacyConsent();

        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();
        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw BusinessLogicException::operationFailed('Create user', $e->getMessage());
        }

        return $user;
    }
}
