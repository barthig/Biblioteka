<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->wantsJson($request)) {
            return;
        }

        $throwable = $event->getThrowable();
        if ($throwable instanceof HandlerFailedException) {
            $throwable = $throwable->getPrevious() ?? $throwable;
        }

        $statusCode = 500;
        if ($throwable instanceof HttpExceptionInterface) {
            $statusCode = $throwable->getStatusCode();
        } elseif ($throwable instanceof \InvalidArgumentException) {
            $statusCode = 400;
        }

        $message = $statusCode >= 500 ? 'Internal server error' : $throwable->getMessage();
        if ($statusCode >= 500 && $this->isDebug()) {
            $message = $throwable->getMessage();
        }

        $event->setResponse(new JsonResponse([
            'message' => $message,
        ], $statusCode));
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

    private function isDebug(): bool
    {
        $value = getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? '0');
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
