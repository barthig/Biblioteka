<?php

namespace App\GraphQL\Resolver;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\RefreshTokenService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * GraphQL resolver for authentication
 * Note: Requires additional packages:
 * - composer require symfony/password-hasher
 * - composer require lexik/jwt-authentication-bundle
 */
class AuthResolver
{
    public function __construct(
        private UserRepository $userRepository,
        /** @phpstan-ignore-next-line Optional dependency - install symfony/password-hasher */
        private ?object $passwordHasher = null,
        /** @phpstan-ignore-next-line Optional dependency - install lexik/jwt-authentication-bundle */
        private ?object $jwtManager = null,
        private RefreshTokenService $refreshTokenService,
        private RequestStack $requestStack
    ) {
    }

    /**
     * Login user and return auth payload
     */
    public function login(string $username, string $password): array
    {
        $user = $this->userRepository->findOneBy(['email' => $username]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new UnauthorizedHttpException('', 'Invalid credentials');
        }

        if (!$user->isVerified()) {
            throw new UnauthorizedHttpException('', 'Account not verified');
        }

        $token = $this->jwtManager->create($user);
        
        $request = $this->requestStack->getCurrentRequest();
        $refreshToken = $this->refreshTokenService->createRefreshToken($user, $request ?? Request::create('/'));

        return [
            'token' => $token,
            'refreshToken' => $refreshToken->getToken(),
            'expiresIn' => 3600, // 1 hour
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles(),
                'isVerified' => $user->isVerified(),
                'createdAt' => $user->getCreatedAt()?->format('c'),
            ],
        ];
    }
}
