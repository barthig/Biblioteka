<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Account\UpdateAccountUiPreferencesCommand;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class UpdateAccountUiPreferencesHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(UpdateAccountUiPreferencesCommand $command): \App\Entity\User
    {
        $user = $this->userRepository->find($command->userId);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        if ($command->theme !== null) {
            $user->setTheme($command->theme);
        }
        if ($command->fontSize !== null) {
            $user->setFontSize($command->fontSize);
        }
        if ($command->language !== null) {
            $user->setLanguage($command->language);
        }

        $this->entityManager->flush();

        return $user;
    }
}
