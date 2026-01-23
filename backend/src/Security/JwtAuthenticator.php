<?php
namespace App\Security;

use App\Repository\UserRepository;
use App\Service\JwtService;
use App\Security\ApiSecretUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator
{
    public function __construct(private UserRepository $users)
    {
    }

    public function supports(Request $request): ?bool
    {
        $path = $request->getPathInfo();
        $method = $request->getMethod();

        // Only protect API/admin paths; skip OPTIONS and public routes
        if (!str_starts_with($path, '/api') && !str_starts_with($path, '/admin')) {
            return false;
        }
        if ($method === 'OPTIONS') {
            return false;
        }
        if ($this->isPublicRoute($path, $method)) {
            return false;
        }

        $hasBearer = $this->getBearerToken($request) !== null;
        $hasApiSecret = $request->headers->get('x-api-secret');

        return $hasBearer || $hasApiSecret;
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $apiSecretUser = $this->tryApiSecret($request);
        if ($apiSecretUser !== null) {
            return new SelfValidatingPassport(new UserBadge('api_secret', static fn() => $apiSecretUser));
        }

        $token = $this->getBearerToken($request);
        if (!$token) {
            throw new CustomUserMessageAuthenticationException('Missing bearer token');
        }

        $payload = JwtService::validateToken($token);
        if (!$payload) {
            throw new CustomUserMessageAuthenticationException('Invalid or expired token');
        }

        $userId = $payload['sub'] ?? null;
        $user = $userId ? $this->users->find($userId) : null;
        if (!$user) {
            throw new CustomUserMessageAuthenticationException('User not found');
        }

        // Basic account status gates to mirror controller-level checks
        if (!$user->isVerified()) {
            throw new CustomUserMessageAuthenticationException('Account not verified');
        }
        if ($user->isPendingApproval()) {
            throw new CustomUserMessageAuthenticationException('Account awaiting approval');
        }
        if ($user->isBlocked()) {
            throw new CustomUserMessageAuthenticationException('Account is blocked');
        }

        // Roles are on the User; payload is validated already
        return new SelfValidatingPassport(new UserBadge((string) $userId, fn() => $user));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?JsonResponse
    {
        return null; // Continue request
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?JsonResponse
    {
        return new JsonResponse(['message' => $exception->getMessage()], 401);
    }

    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse(['message' => 'Unauthorized'], 401);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    private function getBearerToken(Request $request): ?string
    {
        $auth = $request->headers->get('authorization');
        if (!$auth || stripos($auth, 'bearer ') !== 0) {
            return null;
        }
        return substr($auth, 7);
    }

    private function isPublicRoute(string $path, string $method): bool
    {
        $publicRoutes = [
            '/api/auth/login' => ['POST'],
            '/api/auth/register' => ['POST'],
            '/api/auth/refresh' => ['POST'],
            '/api/health' => ['GET'],
            '/health' => ['GET'],
            '/api/docs' => ['GET'],
            '/api/docs.json' => ['GET'],
        ];

        if (isset($publicRoutes[$path]) && in_array($method, $publicRoutes[$path], true)) {
            return true;
        }

        $publicPatterns = [
            ['pattern' => '#^/api/books$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/filters$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/recommended$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/popular$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/new$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/\d+$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/\d+/availability$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/\d+/ratings$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/collections$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/collections/\d+$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/announcements$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/announcements/\d+$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/library/hours$#', 'methods' => ['GET']],
        ];

        foreach ($publicPatterns as $entry) {
            if (preg_match($entry['pattern'], $path) && in_array($method, $entry['methods'], true)) {
                return true;
            }
        }

        return false;
    }

    private function tryApiSecret(Request $request): ?ApiSecretUser
    {
        $provided = $request->headers->get('x-api-secret');
        if (!$provided) {
            return null;
        }

        $secrets = [];
        $primary = getenv('API_SECRET') ?: ($_ENV['API_SECRET'] ?? null);
        if (is_string($primary) && $primary !== '') {
            $secrets[] = $primary;
        }

        $additional = getenv('API_SECRETS') ?: ($_ENV['API_SECRETS'] ?? null);
        if (is_string($additional) && $additional !== '') {
            foreach (explode(',', $additional) as $candidate) {
                $candidate = trim($candidate);
                if ($candidate !== '') {
                    $secrets[] = $candidate;
                }
            }
        }

        foreach ($secrets as $secret) {
            if (hash_equals($secret, $provided)) {
                return new ApiSecretUser();
            }
        }

        throw new CustomUserMessageAuthenticationException('Invalid API secret');
    }
}
