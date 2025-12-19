<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\JwtService;
use App\Service\RefreshTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TestAuthController extends AbstractController
{
    public function __construct(
        private RefreshTokenService $refreshTokenService
    ) {
    }

    public function testLogin(Request $request, UserRepository $repo): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true) ?: [];
            $email = strtolower(trim((string) ($data['email'] ?? '')));
            $password = $data['password'] ?? '';

            if (!$email || !$password) {
                return $this->json(['message' => 'Email i hasło są wymagane'], 400);
            }

            $user = $repo->findOneBy(['email' => $email]);
            if (!$user) {
                return $this->json(['message' => 'Użytkownik nie znaleziony', 'email' => $email], 404);
            }

            if (!password_verify($password, $user->getPassword())) {
                return $this->json([
                    'error' => 'Nieprawidłowe hasło',
                    'hashLength' => strlen($user->getPassword()),
                    'passwordLength' => strlen($password)
                ], 401);
            }

            // Test JWT
            $token = JwtService::createToken([
                'sub' => $user->getId(),
                'roles' => $user->getRoles(),
                'email' => $user->getEmail(),
                'name' => $user->getName()
            ]);

            // Test Refresh Token
            $refreshToken = $this->refreshTokenService->createRefreshToken($user, $request);

            return $this->json([
                'success' => true,
                'userId' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'token' => $token,
                'refreshToken' => $refreshToken->getToken()
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Exception: ' . $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 10)
            ], 500);
        }
    }
}
