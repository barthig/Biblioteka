<?php
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
