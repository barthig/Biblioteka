<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiResponseNormalizationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->wantsJson($request)) {
            return;
        }

        $response = $event->getResponse();
        $content = $response->getContent();
        if ($content === null || $content === '') {
            return;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return;
        }

        $modified = false;
        if (isset($data['error'])) {
            if (!isset($data['message'])) {
                $data['message'] = $data['error'];
            }
            unset($data['error']);
            $modified = true;
        }

        if (isset($data['errors']) && !isset($data['message'])) {
            $data['message'] = 'Validation failed';
            $modified = true;
        }

        if ($modified) {
            $response->setContent(json_encode($data));
        }
    }

    private function wantsJson(\Symfony\Component\HttpFoundation\Request $request): bool
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
