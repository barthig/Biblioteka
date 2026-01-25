<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Account\ChangePasswordCommand;
use App\Repository\RefreshTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ChangePasswordHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly RefreshTokenRepository $refreshTokenRepository
    ) {
    }

    public function __invoke(ChangePasswordCommand $command): void
    {
        $user = $this->userRepository->find($command->userId);
        
        if (!$user) {
            throw new NotFoundHttpException('Użytkownik nie istnieje');
        }

        if (!password_verify($command->currentPassword, $user->getPassword())) {
            throw new BadRequestHttpException('Aktualne hasło jest niepoprawne');
        }

        if (strlen($command->newPassword) < 8) {
            throw new BadRequestHttpException('Nowe hasło musi mieć co najmniej 8 znaków');
        }

        if ($command->currentPassword === $command->newPassword) {
            throw new BadRequestHttpException('Nowe hasło musi się różnić od poprzedniego');
        }

        if ($command->newPassword !== $command->confirmPassword) {
            throw new BadRequestHttpException('Potwierdzenie hasła nie zgadza się z nowym hasłem');
        }

        $user->setPassword(password_hash($command->newPassword, PASSWORD_BCRYPT));
        
        // Revoke all refresh tokens for security - force re-login on all devices
        $this->refreshTokenRepository->revokeAllUserTokens($user);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
