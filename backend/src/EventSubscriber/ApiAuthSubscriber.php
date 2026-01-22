<?php
namespace App\EventSubscriber;

use App\Repository\UserRepository;
use App\Security\ApiSecretUser;
use App\Service\JwtService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ApiAuthSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserRepository $users,
        private TokenStorageInterface $tokenStorage
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        $method = $request->getMethod();

        // Only protect /api and /admin routes (allow health check)
        if (strpos($path, '/api') !== 0 && strpos($path, '/admin') !== 0) {
            return;
        }

        if ($method === 'OPTIONS') {
            return;
        }

        if ($path === '/api/health' || $path === '/health') {
            return;
        }

        if ($path === '/api/docs' || $path === '/api/docs.json') {
            return;
        }

        $apiSecretStatus = $this->attachApiSecretIdentity($request);
        $jwtStatus = $this->attachJwtPayload($request);

        if ($this->isPublicRoute($path, $method)) {
            return;
        }

        if ($jwtStatus === true) {
            $user = $request->attributes->get('jwt_user');
            $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? null);
            if ($user === null) {
                $event->setResponse(new JsonResponse(['message' => 'Unauthorized'], 401));
                return;
            }

            if ($appEnv !== 'test' && !$user->isVerified()) {
                $event->setResponse(new JsonResponse(['message' => 'Account not verified'], 403));
                return;
            }

            if ($user->isPendingApproval()) {
                $event->setResponse(new JsonResponse(['message' => 'Account awaiting approval'], 403));
                return;
            }

            if ($user->isBlocked()) {
                $event->setResponse(new JsonResponse(['message' => 'Account is blocked'], 403));
                return;
            }

            $this->attachSecurityToken($user);
            return;
        }

        if ($apiSecretStatus === true) {
            $this->attachSecurityToken(new ApiSecretUser());
            return;
        }

        // JWT or API secret required
        $event->setResponse(new JsonResponse(['message' => 'Unauthorized'], 401));
    }

    private function attachSecurityToken(\Symfony\Component\Security\Core\User\UserInterface $user): void
    {
        if ($this->tokenStorage->getToken() !== null) {
            return;
        }

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }

    private function attachJwtPayload(Request $request): ?bool
    {
        $auth = $request->headers->get('authorization');
        if (!$auth || stripos($auth, 'bearer ') !== 0) {
            return null;
        }

        $bearer = substr($auth, 7);
        $payload = JwtService::validateToken($bearer);
        if (!$payload) {
            return false;
        }

        $userId = $payload['sub'] ?? null;
        $user = $userId ? $this->users->find($userId) : null;

        if (!$user) {
            return false;
        }

        $request->attributes->set('jwt_payload', $payload);
        $request->attributes->set('jwt_user', $user);

        return true;
    }

    private function isPublicRoute(string $path, string $method): bool
    {
        $publicRoutes = [
            '/api/auth/login' => ['POST'],
            '/api/auth/register' => ['POST'],
            '/api/auth/refresh' => ['POST'],
        ];

        if (isset($publicRoutes[$path]) && in_array($method, $publicRoutes[$path], true)) {
            return true;
        }

        if ($path === '/api/test-login' && $this->isDebugEnv()) {
            return true;
        }

        $publicPatterns = [
            ['pattern' => '#^/api/books$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/filters$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/recommended$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/popular$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/new$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/\\d+$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/\\d+/availability$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/\\d+/ratings$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/collections$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/collections/\\d+$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/announcements$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/announcements/\\d+$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/auth/verify/[A-Za-z0-9]+$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/library/hours$#', 'methods' => ['GET']],
        ];

        foreach ($publicPatterns as $entry) {
            if (preg_match($entry['pattern'], $path) && in_array($method, $entry['methods'], true)) {
                return true;
            }
        }

        return false;
    }

    private function attachApiSecretIdentity(Request $request): ?bool
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

        if ($secrets === []) {
            return false;
        }

        foreach ($secrets as $secret) {
            if (hash_equals($secret, $provided)) {
                $request->attributes->set('api_secret_auth', true);
                $request->attributes->set('jwt_payload', [
                    'sub' => null,
                    'roles' => ['ROLE_ADMIN', 'ROLE_SYSTEM'],
                    'auth' => 'api_secret',
                ]);
                return true;
            }
        }

        return false;
    }

    private function isDebugEnv(): bool
    {
        $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'prod');
        return in_array($env, ['dev', 'test'], true);
    }
}
