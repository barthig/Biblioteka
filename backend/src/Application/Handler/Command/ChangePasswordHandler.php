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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler(bus: 'command.bus')]
class ChangePasswordHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function __invoke(ChangePasswordCommand $command): void
    {
        $user = $this->userRepository->find($command->userId);
        
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $command->currentPassword)) {
            throw new BadRequestHttpException('Current password is incorrect');
        }

        if (strlen($command->newPassword) < 10) {
            throw new BadRequestHttpException('New password must be at least 10 characters');
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $command->newPassword)) {
            throw new BadRequestHttpException('Password must contain lowercase, uppercase letters and a digit');
        }

        if ($command->currentPassword === $command->newPassword) {
            throw new BadRequestHttpException('New password must differ from the current one');
        }

        if ($command->newPassword !== $command->confirmPassword) {
            throw new BadRequestHttpException('Password confirmation does not match');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $command->newPassword));
        
        // Revoke all refresh tokens for security - force re-login on all devices
        $this->refreshTokenRepository->revokeAllUserTokens($user);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
