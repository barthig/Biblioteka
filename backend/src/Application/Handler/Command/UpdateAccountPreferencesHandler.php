<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Account\UpdateAccountPreferencesCommand;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class UpdateAccountPreferencesHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(UpdateAccountPreferencesCommand $command): \App\Entity\User
    {
        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        if ($command->defaultBranch !== null) {
            $user->setDefaultBranch($command->defaultBranch);
        }
        if ($command->newsletterSubscribed !== null) {
            $user->setNewsletterSubscribed($command->newsletterSubscribed);
        }
        if ($command->keepHistory !== null) {
            $user->setKeepHistory($command->keepHistory);
        }
        if ($command->emailLoans !== null) {
            $user->setEmailLoans($command->emailLoans);
        }
        if ($command->emailReservations !== null) {
            $user->setEmailReservations($command->emailReservations);
        }
        if ($command->emailFines !== null) {
            $user->setEmailFines($command->emailFines);
        }
        if ($command->emailAnnouncements !== null) {
            $user->setEmailAnnouncements($command->emailAnnouncements);
        }

        $this->entityManager->flush();

        return $user;
    }
}
