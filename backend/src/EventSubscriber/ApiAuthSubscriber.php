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

        $publicRoutes = [
            // allow authentication bootstrap without token
            '/api/auth/login' => ['POST'],
            '/api/auth/register' => ['POST'],
        ];

        if (isset($publicRoutes[$path]) && in_array($method, $publicRoutes[$path], true)) {
            return;
        }

        $publicPatterns = [
            ['pattern' => '#^/api/books$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/filters$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/books/\\d+$#', 'methods' => ['GET']],
            ['pattern' => '#^/api/auth/verify/[A-Za-z0-9]+$#', 'methods' => ['GET']],
        ];

        foreach ($publicPatterns as $entry) {
            if (preg_match($entry['pattern'], $path) && in_array($method, $entry['methods'], true)) {
                return;
            }
        }

        $headerSecret = $request->headers->get('x-api-secret');
        $auth = $request->headers->get('authorization');
        $bearer = null;
        if ($auth && stripos($auth, 'bearer ') === 0) {
            $bearer = substr($auth, 7);
        }

        $secret = $headerSecret ?: $bearer;

        $envSecret = getenv('API_SECRET') ?: ($_ENV['API_SECRET'] ?? null);

        // direct secret matches env variable
        if ($secret && $envSecret !== null && $secret === $envSecret) {
            return;
        }

        // try JWT validation when bearer token provided
        if ($bearer) {
            $payload = JwtService::validateToken($bearer);
            if ($payload) {
                $userId = $payload['sub'] ?? null;
                $user = $userId ? $this->users->find($userId) : null;
                if (!$user) {
                    $event->setResponse(new JsonResponse(['error' => 'Unauthorized'], 401));
                    return;
                }

                $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? null);
                if ($appEnv !== 'test' && !$user->isVerified()) {
                    $event->setResponse(new JsonResponse(['error' => 'Account not verified'], 403));
                    return;
                }

                if ($user->isPendingApproval()) {
                    $event->setResponse(new JsonResponse(['error' => 'Account awaiting approval'], 403));
                    return;
                }

                if ($user->isBlocked()) {
                    $event->setResponse(new JsonResponse(['error' => 'Account is blocked'], 403));
                    return;
                }

                // attach payload to request for downstream role checks
                $request->attributes->set('jwt_payload', $payload);
                $request->attributes->set('jwt_user', $user);
                return;
            }
        }

        // if API secret matched earlier we would have returned; otherwise unauthorized
        $event->setResponse(new JsonResponse(['error' => 'Unauthorized'], 401));
    }
}
