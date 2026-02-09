<?php
declare(strict_types=1);
namespace App\EventSubscriber;

use App\Dto\ApiError;
use App\Dto\ApiResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiResponseNormalizationSubscriber implements EventSubscriberInterface
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
        if ($content === false || $content === '') {
            return;
        }

        if ($response->getStatusCode() < 400) {
            return;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return;
        }

        // If already in new format (has 'error' or 'data'), skip normalization
        if (isset($data['error']) && is_array($data['error']) && isset($data['error']['code'])) {
            return;
        }

        // Handle legacy error format: ['message' => '...', 'errors' => [...]]
        $modified = false;
        if (isset($data['message']) && !isset($data['error']) && !isset($data['data'])) {
            // This is an error message - convert to new format
            $isValidationError = isset($data['errors']) && is_array($data['errors']);
            $statusCode = (int) $response->getStatusCode();
            
            if ($isValidationError) {
                $error = ApiError::validationFailed($data['errors']);
            } else {
                $error = new ApiError(
                    code: $this->getErrorCode($statusCode, $data['message']),
                    message: $data['message'],
                    statusCode: $statusCode
                );
            }
            
            $apiResponse = ApiResponse::error($error);
            $response->setContent(json_encode($apiResponse->toArray()));
            $modified = true;
        }

        if ($modified) {
            // Status code already set by response
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

    /**
     * Map HTTP status codes to error codes
     */
    private function getErrorCode(int $statusCode, string $message = ''): string
    {
        return match ($statusCode) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            410 => 'GONE',
            422 => 'UNPROCESSABLE_ENTITY',
            423 => 'LOCKED',
            429 => 'RATE_LIMIT_EXCEEDED',
            500 => 'INTERNAL_ERROR',
            503 => 'SERVICE_UNAVAILABLE',
            default => 'ERROR',
        };
    }
}
