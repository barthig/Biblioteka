<?php
namespace App\EventSubscriber;

use App\Repository\UserRepository;
use App\Service\JwtService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiAuthSubscriber implements EventSubscriberInterface
{
    public function __construct(private UserRepository $users)
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

        // Only protect /api routes (allow health check)
        if (strpos($path, '/api') !== 0) {
            return;
        }

        if ($method === 'OPTIONS') {
            return;
        }

        if ($path === '/api/health' || $path === '/health') {
            return;
        }

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

            return;
        }

        // JWT validation required - no fallback authentication allowed
        $event->setResponse(new JsonResponse(['message' => 'Unauthorized'], 401));
    }

    private function attachJwtPayload($request): ?bool
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
            '/api/test-login' => ['POST'], // temporary debug endpoint
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
}
