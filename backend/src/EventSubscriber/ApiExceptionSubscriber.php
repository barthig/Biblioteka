<?php
namespace App\EventSubscriber;

use App\Dto\ApiError;
use App\Dto\ApiResponse;
use App\Exception\AppException;
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

        // AppException hierarchy — uses its own statusCode, errorCode, context
        if ($throwable instanceof AppException) {
            $error = new ApiError(
                code: $throwable->getErrorCode() ?? $this->getErrorCode($throwable->getStatusCode()),
                message: $throwable->getMessage(),
                statusCode: $throwable->getStatusCode(),
                details: $throwable->getContext() ?: null,
            );

            $response = ApiResponse::error($error);
            $event->setResponse(new JsonResponse($response->toArray(), $throwable->getStatusCode()));
            return;
        }

        $statusCode = 500;
        if ($throwable instanceof HttpExceptionInterface) {
            $statusCode = $throwable->getStatusCode();
        } elseif ($throwable instanceof \InvalidArgumentException) {
            $statusCode = 400;
        } elseif ($throwable instanceof \RuntimeException) {
            // Infer HTTP status from common handler message patterns
            $statusCode = $this->inferStatusFromMessage($throwable->getMessage());
        }

        $message = $statusCode >= 500 ? 'Internal server error' : $throwable->getMessage();
        if ($statusCode >= 500 && $this->isDebug()) {
            $message = $throwable->getMessage();
        }

        $error = new ApiError(
            code: $this->getErrorCode($statusCode),
            message: $message,
            statusCode: $statusCode
        );

        $response = ApiResponse::error($error);
        $event->setResponse(new JsonResponse($response->toArray(), $statusCode));
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

    private function getErrorCode(int $statusCode): string
    {
        return ApiError::errorCodeForStatus($statusCode);
    }

    /**
     * Infer HTTP status code from RuntimeException messages thrown by handlers.
     *
     * This is a safety net for handlers not yet migrated to typed AppException classes.
     * Once all handlers use NotFoundException/BusinessLogicException/etc., this method
     * can be removed.
     */
    private function inferStatusFromMessage(string $message): int
    {
        $lower = mb_strtolower($message, 'UTF-8');

        // 404 — "not found" pattern
        if (str_contains($lower, 'not found') || str_contains($lower, 'nie znalezion')) {
            return 404;
        }

        // 403 — authorization
        if ($lower === 'forbidden' || str_contains($lower, 'permission') || str_contains($lower, 'you can only')) {
            return 403;
        }

        // 409 — conflict / already exists / already done
        if (str_contains($lower, 'already exists')
            || str_contains($lower, 'already returned')
            || str_contains($lower, 'already received')
            || str_contains($lower, 'already cancelled')
            || str_contains($lower, 'already expired')
            || str_contains($lower, 'already fulfilled')
            || str_contains($lower, 'already withdrawn')
            || str_contains($lower, 'already borrowed')
            || str_contains($lower, 'already extended')
        ) {
            return 409;
        }

        // 422 — business logic violations
        if (str_contains($lower, 'cannot')
            || str_contains($lower, 'limit')
            || str_contains($lower, 'no copies')
            || str_contains($lower, 'reserved by another')
            || str_contains($lower, 'inactive')
            || str_contains($lower, 'mismatch')
            || str_contains($lower, 'must be')
            || str_contains($lower, 'invalid')
            || str_contains($lower, 'zablokowane')
            || str_contains($lower, 'brak dost')
        ) {
            return 422;
        }

        return 500;
    }
}
