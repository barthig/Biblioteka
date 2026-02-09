<?php
declare(strict_types=1);
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
            throw new NotFoundHttpException('User not found');
        }

        if (!password_verify($command->currentPassword, $user->getPassword())) {
            throw new BadRequestHttpException('Current password is incorrect');
        }

        if (strlen($command->newPassword) < 8) {
            throw new BadRequestHttpException('New password must be at least 8 characters');
        }

        if ($command->currentPassword === $command->newPassword) {
            throw new BadRequestHttpException('New password must differ from the current one');
        }

        if ($command->newPassword !== $command->confirmPassword) {
            throw new BadRequestHttpException('Password confirmation does not match');
        }

        $user->setPassword(password_hash($command->newPassword, PASSWORD_BCRYPT));
        
        // Revoke all refresh tokens for security - force re-login on all devices
        $this->refreshTokenRepository->revokeAllUserTokens($user);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
