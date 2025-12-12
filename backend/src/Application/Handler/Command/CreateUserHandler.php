<?php
namespace App\Application\Handler\Command;

use App\Application\Command\User\CreateUserCommand;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateUserHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
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
            throw new \RuntimeException('Unknown membership group');
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

        $user->setPassword(password_hash($command->password, PASSWORD_BCRYPT));
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
            throw new \RuntimeException('Błąd podczas tworzenia użytkownika');
        }

        return $user;
    }
}
