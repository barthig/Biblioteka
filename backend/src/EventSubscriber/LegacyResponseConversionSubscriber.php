<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Middleware\LegacyErrorResponseConverter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LegacyResponseConversionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', -5],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        if (!$response instanceof JsonResponse) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isApiRequest($request)) {
            return;
        }

        LegacyErrorResponseConverter::convertIfNeeded($response);
    }

    private function isApiRequest(Request $request): bool
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
