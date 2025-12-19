<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpFoundation\Response;

class RateLimiterSubscriber implements EventSubscriberInterface
{
    private RateLimiterFactory $anonymousApiLimiter;
    private RateLimiterFactory $authenticatedApiLimiter;

    public function __construct(
        RateLimiterFactory $anonymousApiLimiter,
        RateLimiterFactory $authenticatedApiLimiter
    ) {
        $this->anonymousApiLimiter = $anonymousApiLimiter;
        $this->authenticatedApiLimiter = $authenticatedApiLimiter;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Skip rate limiting for non-API routes
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        // Skip health check endpoint
        if ($request->getPathInfo() === '/api/health') {
            return;
        }

        // Determine if user is authenticated
        $user = $request->attributes->get('_security_user');
        $identifier = $request->getClientIp();
        $limiter = $this->anonymousApiLimiter->create($identifier);
        if ($user instanceof \App\Entity\User) {
            $identifier = 'user_' . $user->getId();
            $limiter = $this->authenticatedApiLimiter->create($identifier);
        }

        // Consume a token
        $limit = $limiter->consume(1);

        // Add rate limit headers
        $retryAfter = $limit->getRetryAfter();
        $retryAfterTimestamp = $retryAfter->getTimestamp();

        $event->getRequest()->attributes->set('rate_limit', [
            'limit' => $limit->getLimit(),
            'remaining' => $limit->getRemainingTokens(),
            'reset' => $retryAfterTimestamp,
        ]);

        // Check if limit is exceeded
        if (!$limit->isAccepted()) {
            $response = new JsonResponse([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $retryAfterTimestamp,
            ], Response::HTTP_TOO_MANY_REQUESTS);

            $response->headers->set('X-RateLimit-Limit', (string) $limit->getLimit());
            $response->headers->set('X-RateLimit-Remaining', '0');
            $response->headers->set('X-RateLimit-Reset', (string) $retryAfterTimestamp);
            $response->headers->set('Retry-After', (string) $retryAfter->format(\DateTimeInterface::RFC7231));

            $event->setResponse($response);
        }
    }
}
