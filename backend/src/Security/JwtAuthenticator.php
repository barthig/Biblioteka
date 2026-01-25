<?php
namespace App\Security;

use App\Repository\UserRepository;
use App\Service\Auth\JwtService;
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
            error_log("JwtAuthenticator::supports: FALSE - not API/admin path: $path");
            return false;
        }
        if ($method === 'OPTIONS') {
            error_log("JwtAuthenticator::supports: FALSE - OPTIONS request: $path");
            return false;
        }
        if ($this->isPublicRoute($path, $method)) {
            error_log("JwtAuthenticator::supports: FALSE - public route: $path");
            return false;
        }

        // Check for credentials
        $hasBearer = $this->getBearerToken($request) !== null;
        $hasApiSecret = $request->headers->get('x-api-secret');

        error_log(sprintf(
            "JwtAuthenticator::supports: path=%s, hasBearer=%s, hasApiSecret=%s, result=TRUE",
            $path,
            $hasBearer ? 'YES' : 'NO',
            $hasApiSecret ? 'YES' : 'NO'
        ));

        if ($hasBearer || $hasApiSecret) {
            return true; // Yes, we should authenticate
        }

        // No credentials for protected route - return true to trigger authentication
        // This will cause authenticate() to be called, which will throw an exception
        // and result in a 401 response via onAuthenticationFailure()
        return true;
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $path = $request->getPathInfo();
        
        $apiSecretUser = $this->tryApiSecret($request);
        if ($apiSecretUser !== null) {
            // Set jwt_payload for API secret authentication
            $request->attributes->set('jwt_payload', [
                'sub' => null,
                'roles' => ['ROLE_ADMIN', 'ROLE_SYSTEM'],
                'auth' => 'api_secret',
            ]);
            $request->attributes->set('api_secret_auth', true);
            return new SelfValidatingPassport(new UserBadge('api_secret', static fn() => $apiSecretUser));
        }

        $token = $this->getBearerToken($request);
        if (!$token) {
            error_log("JwtAuthenticator: No bearer token for path=$path");
            throw new CustomUserMessageAuthenticationException('Missing bearer token');
        }

        $payload = JwtService::validateToken($token);
        if (!$payload) {
            error_log("JwtAuthenticator: Invalid token for path=$path");
            throw new CustomUserMessageAuthenticationException('Invalid or expired token');
        }

        $userId = $payload['sub'] ?? null;
        $user = $userId ? $this->users->find($userId) : null;
        if (!$user) {
            error_log("JwtAuthenticator: User not found for userId=$userId, path=$path");
            throw new CustomUserMessageAuthenticationException('User not found');
        }

        // Skip verification check in test environment
        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'prod');
        if ($appEnv !== 'test' && !$user->isVerified()) {
            throw new CustomUserMessageAuthenticationException('Account not verified');
        }
        if ($user->isPendingApproval()) {
            throw new CustomUserMessageAuthenticationException('Account awaiting approval');
        }
        if ($user->isBlocked()) {
            throw new CustomUserMessageAuthenticationException('Account is blocked');
        }

        // Set jwt_payload and jwt_user attributes for controllers
        $request->attributes->set('jwt_payload', $payload);
        $request->attributes->set('jwt_user', $user);
        
        error_log("JwtAuthenticator: SUCCESS userId=$userId, path=$path");

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
        error_log(sprintf(
            "JwtAuthenticator::getBearerToken: path=%s, auth_header=%s",
            $request->getPathInfo(),
            $auth ? 'PRESENT(' . strlen($auth) . ' chars)' : 'NULL'
        ));
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

        // Check for /api/auth/verify/{token}
        if (preg_match('#^/api/auth/verify/.+$#', $path) && $method === 'POST') {
            return true;
        }

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
