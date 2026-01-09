<?php

namespace App\EventSubscriber;

use App\Middleware\LegacyErrorResponseConverter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Automatically converts legacy error responses to standardized format.
 * This allows gradual migration of controllers without breaking changes.
 */
class LegacyResponseConversionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', -5], // After other subscribers
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        
        // Only process JSON responses
        if (!$response instanceof JsonResponse) {
            return;
        }

        // Only process API responses
        $request = $event->getRequest();
        if (!$this->isApiRequest($request)) {
            return;
        }

        LegacyErrorResponseConverter::convertIfNeeded($response);
    }

    private function isApiRequest($request): bool
    {
        if (str_starts_with($request->getPathInfo(), '/api')) {
            return true;
        }

        if ($request->getRequestFormat() === 'json') {
            return true;
        }

        $accept = $request->headers->get('Accept', '');
        return str_contains($accept, 'application/json');
    }
}
