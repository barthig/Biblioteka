<?php
declare(strict_types=1);
namespace App\Controller\Traits;

use App\Dto\ApiError;
use App\Dto\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

trait ExceptionHandlingTrait
{
    protected function unwrapThrowable(\Throwable $e): \Throwable
    {
        if ($e instanceof HandlerFailedException) {
            return $e->getPrevious() ?? $e;
        }

        return $e;
    }

    /**
     * Convert exception to JSON response with unified error format
     */
    protected function jsonFromHttpException(\Throwable $e): ?JsonResponse
    {
        if ($e instanceof HttpExceptionInterface) {
            $error = new ApiError(
                code: $this->getErrorCodeFromStatusCode($e->getStatusCode()),
                message: $e->getMessage(),
                statusCode: $e->getStatusCode()
            );
            return $this->jsonError($error);
        }

        if ($e instanceof \InvalidArgumentException) {
            $error = ApiError::badRequest($e->getMessage());
            return $this->jsonError($error);
        }

        return null;
    }

    /**
     * Return error response with unified format
     */
    protected function jsonError(ApiError $error): JsonResponse
    {
        $response = ApiResponse::error($error);
        return $this->json($response->toArray(), $error->statusCode);
    }

    /**
     * Build standardized error response from status code + message.
     */
    protected function jsonErrorMessage(int $statusCode, string $message, mixed $details = null): JsonResponse
    {
        return $this->jsonError($this->apiErrorFromStatus($statusCode, $message, $details));
    }

    protected function apiErrorFromStatus(int $statusCode, string $message, mixed $details = null): ApiError
    {
        return match (true) {
            $statusCode === 401 => ApiError::unauthorized($details),
            $statusCode === 403 => ApiError::forbidden($details),
            $statusCode === 404 => new ApiError('NOT_FOUND', $message, 404, $details),
            $statusCode === 409 => ApiError::conflict($message, $details),
            $statusCode === 410 => ApiError::gone($message, $details),
            $statusCode === 422 => ApiError::unprocessable($message, $details),
            $statusCode === 423 => ApiError::locked($message, $details),
            $statusCode === 429 => ApiError::tooManyRequests($message, $details),
            $statusCode === 503 => ApiError::serviceUnavailable($message, $details),
            $statusCode >= 500 => ApiError::internalError($message, $details),
            default => ApiError::badRequest($message, $details),
        };
    }

    /**
     * Return success response with unified format
     */
    protected function jsonSuccess(mixed $data = null, int $statusCode = 200, array $context = []): JsonResponse
    {
        $response = ApiResponse::success($data);
        return $this->json($response->toArray(), $statusCode, context: $context);
    }

    /**
     * Map HTTP status code to error code
     */
    private function getErrorCodeFromStatusCode(int $statusCode): string
    {
        return ApiError::errorCodeForStatus($statusCode);
    }
}
