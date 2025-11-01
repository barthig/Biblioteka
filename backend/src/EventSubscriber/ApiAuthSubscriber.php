<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\JwtService;

class ApiAuthSubscriber implements EventSubscriberInterface
{
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

        // Only protect /api routes (allow health check)
        if (strpos($path, '/api') !== 0) {
            return;
        }

        if ($path === '/api/health' || $path === '/health') {
            return;
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
                // attach payload to request for downstream role checks
                $request->attributes->set('jwt_payload', $payload);
                return;
            }
        }

        // if API secret matched earlier we would have returned; otherwise unauthorized
        $event->setResponse(new JsonResponse(['error' => 'Unauthorized'], 401));
    }
}
