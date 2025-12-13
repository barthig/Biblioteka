<?php

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class RefreshTokenService
{
    private const TOKEN_LIFETIME_DAYS = 30;
    private const MAX_TOKENS_PER_USER = 5;

    public function __construct(
        private EntityManagerInterface $em,
        private RefreshTokenRepository $refreshTokenRepository
    ) {
    }

    /**
     * Tworzy nowy refresh token dla użytkownika
     */
    public function createRefreshToken(User $user, Request $request): RefreshToken
    {
        // Limit tokenów na użytkownika
        $activeTokens = $this->refreshTokenRepository->countUserActiveTokens($user);
        if ($activeTokens >= self::MAX_TOKENS_PER_USER) {
            // Usuń najstarszy token
            $this->revokeOldestToken($user);
        }

        $token = new RefreshToken();
        $token->setUser($user);
        $token->setToken($this->generateSecureToken());
        $token->setExpiresAt(new \DateTimeImmutable('+' . self::TOKEN_LIFETIME_DAYS . ' days'));
        $token->setIpAddress($request->getClientIp());
        $token->setUserAgent($request->headers->get('User-Agent'));

        $this->em->persist($token);
        $this->em->flush();

        return $token;
    }

    /**
     * Waliduje refresh token i zwraca użytkownika
     */
    public function validateRefreshToken(string $tokenString): ?User
    {
        // Hash the provided token to look it up securely
        $tokenHash = hash('sha256', $tokenString);
        $token = $this->refreshTokenRepository->findOneBy(['tokenHash' => $tokenHash]);

        if (!$token || !$token->isValid()) {
            return null;
        }

        // Double-check with constant-time comparison
        if (!$token->verifyToken($tokenString)) {
            return null;
        }

        return $token->getUser();
    }

    /**
     * Unieważnia refresh token
     */
    public function revokeRefreshToken(string $tokenString): bool
    {
        $tokenHash = hash('sha256', $tokenString);
        $token = $this->refreshTokenRepository->findOneBy(['tokenHash' => $tokenHash]);

        if (!$token) {
            return false;
        }

        $token->revoke();
        $this->em->flush();

        return true;
    }

    /**
     * Unieważnia wszystkie tokeny użytkownika (logout ze wszystkich urządzeń)
     */
    public function revokeAllUserTokens(User $user): int
    {
        return $this->refreshTokenRepository->revokeAllUserTokens($user);
    }

    /**
     * Usuwa wygasłe tokeny (do uruchomienia przez cron)
     */
    public function cleanupExpiredTokens(): int
    {
        return $this->refreshTokenRepository->deleteExpiredTokens();
    }

    /**
     * Generuje bezpieczny losowy token
     */
    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Usuwa najstarszy token użytkownika
     */
    private function revokeOldestToken(User $user): void
    {
        $oldestToken = $this->em->createQueryBuilder()
            ->select('rt')
            ->from(RefreshToken::class, 'rt')
            ->where('rt.user = :user')
            ->andWhere('rt.isRevoked = false')
            ->setParameter('user', $user)
            ->orderBy('rt.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($oldestToken) {
            $oldestToken->revoke();
            $this->em->flush();
        }
    }
}
