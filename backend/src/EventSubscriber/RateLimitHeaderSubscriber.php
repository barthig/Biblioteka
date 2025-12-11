<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds rate limit headers to API responses
 */
class RateLimitHeaderSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Only add headers for API routes
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        // Get rate limit info from request attributes
        $rateLimit = $request->attributes->get('rate_limit');
        
        if ($rateLimit) {
            $response->headers->set('X-RateLimit-Limit', (string) $rateLimit['limit']);
            $response->headers->set('X-RateLimit-Remaining', (string) $rateLimit['remaining']);
            
            if (isset($rateLimit['reset'])) {
                $response->headers->set('X-RateLimit-Reset', (string) $rateLimit['reset']);
            }
        }
    }
}
