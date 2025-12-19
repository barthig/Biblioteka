<?php
namespace App\Controller;

use App\Repository\UserRepository;
use App\Request\LoginRequest;
use App\Service\JwtService;
use App\Service\RefreshTokenService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    public function __construct(
        private RateLimiterFactory $loginAttemptsLimiter,
        private RefreshTokenService $refreshTokenService,
    ) {
    }

    public function login(Request $request, UserRepository $repo, ValidatorInterface $validator, LoggerInterface $logger): JsonResponse
    {
        $ip = $request->getClientIp() ?? 'unknown';
        $limiter = $this->loginAttemptsLimiter->create($ip);

        try {
            $data = json_decode($request->getContent(), true) ?: [];
            $loginRequest = new LoginRequest();
            $loginRequest->email = isset($data['email']) ? strtolower(trim((string) $data['email'])) : '';
            $loginRequest->password = $data['password'] ?? null;

            $errors = $validator->validate($loginRequest);
            if (count($errors) > 0) {
                $messages = [];
                foreach ($errors as $error) {
                    $messages[] = $error->getMessage();
                }
                return $this->json(['message' => implode(', ', $messages)], 400);
            }

            $email = $loginRequest->email;
            $password = $loginRequest->password;

            $user = $repo->findOneBy(['email' => $email]);
            if (!$user || !password_verify($password, $user->getPassword())) {
                $limit = $limiter->consume(1);
                if (!$limit->isAccepted()) {
                    return $this->json(['message' => 'Zbyt wiele prób logowania. Spróbuj ponownie za 15 minut.'], 429);
                }
                return $this->json(['message' => 'Invalid credentials'], 401);
            }

            // Successful login resets limiter attempts
            if (method_exists($limiter, 'reset')) {
                $limiter->reset();
            }

            $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? null);
            if ($appEnv !== 'test' && !$user->isVerified()) {
                return $this->json(['message' => 'Account not verified'], 403);
            }

            if ($user->isPendingApproval()) {
                return $this->json(['message' => 'Account awaiting librarian approval'], 403);
            }

            if ($user->isBlocked()) {
                return $this->json(['message' => 'Account is blocked'], 403);
            }

            $token = JwtService::createToken([
                'sub' => $user->getId(),
                'roles' => $user->getRoles(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
            ]);
            
            try {
                $refreshToken = $this->refreshTokenService->createRefreshToken($user, $request);
                $refreshTokenString = $refreshToken->getToken();
            } catch (\Throwable $refreshError) {
                $logger->error('Failed to create refresh token', [
                    'error' => $refreshError->getMessage(),
                    'trace' => substr($refreshError->getTraceAsString(), 0, 1000),
                    'file' => $refreshError->getFile(),
                    'line' => $refreshError->getLine(),
                ]);
                return $this->json(['message' => 'Failed to create session. Please try again.'], 500);
            }
            
            return $this->json([
                'token' => $token,
                'refreshToken' => $refreshTokenString,
                'expiresIn' => 86400,
                'refreshExpiresIn' => 2592000,
            ], 200);
        } catch (\Throwable $e) {
            $logger->error('Login error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->json(['message' => 'Wystąpił błąd logowania'], 500);
        }
    }

    public function profile(Request $request, UserRepository $repo): JsonResponse
    {
        $payload = $request->attributes->get('jwt_payload');
        if (!$payload) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $userId = $payload['sub'] ?? null;
        if (!$userId) {
            return $this->json(['message' => 'Invalid token'], 401);
        }

        $user = $repo->find($userId);
        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $refreshTokenString = $data['refreshToken'] ?? null;

        if (!$refreshTokenString) {
            return $this->json(['message' => 'Refresh token is required'], 400);
        }

        $user = $this->refreshTokenService->validateRefreshToken($refreshTokenString);

        if (!$user) {
            return $this->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        $this->refreshTokenService->revokeRefreshToken($refreshTokenString);
        
        try {
            $newRefreshToken = $this->refreshTokenService->createRefreshToken($user, $request);
            $newRefreshTokenString = $newRefreshToken->getToken();
        } catch (\Throwable $e) {
            return $this->json(['message' => 'Failed to rotate refresh token'], 500);
        }

        $token = JwtService::createToken([
            'sub' => $user->getId(),
            'roles' => $user->getRoles(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
        ]);

        return $this->json([
            'token' => $token,
            'refreshToken' => $newRefreshTokenString,
            'expiresIn' => 86400,
            'refreshExpiresIn' => 2592000,
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $refreshTokenString = $data['refreshToken'] ?? null;

        if ($refreshTokenString) {
            $this->refreshTokenService->revokeRefreshToken($refreshTokenString);
        }

        return $this->json(['message' => 'Logged out successfully'], 200);
    }

    public function logoutAll(Request $request, UserRepository $userRepository): JsonResponse
    {
        $payload = $request->attributes->get('jwt_payload');
        if (!$payload) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        $user = $userRepository->find($payload['sub']);
        if (!$user) {
            return $this->json(['message' => 'User not found'], 404);
        }

        $count = $this->refreshTokenService->revokeAllUserTokens($user);

        return $this->json([
            'message' => 'All sessions terminated',
            'revokedCount' => $count,
        ], 200);
    }
}
