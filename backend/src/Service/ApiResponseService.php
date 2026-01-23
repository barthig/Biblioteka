<?php
namespace App\Service;

use App\Dto\ApiError;
use App\Dto\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Centralized API response builder ensuring consistent contract across all endpoints.
 */
class ApiResponseService
{
    /**
     * Build success response with standardized format: { data: {...} }
     */
    public function success(mixed $data = null, int $statusCode = 200): JsonResponse
    {
        $response = ApiResponse::success($data);
        return new JsonResponse($response->toArray(), $statusCode);
    }

    /**
     * Build error response with standardized format: { error: { code, message, details } }
     */
    public function error(string $code, string $message, int $statusCode = 400, mixed $details = null): JsonResponse
    {
        $apiError = new ApiError($code, $message, $statusCode, $details);
        $response = ApiResponse::error($apiError);
        return new JsonResponse($response->toArray(), $statusCode);
    }

    /**
     * Shorthand for common error responses
     */
    public function badRequest(string $message, mixed $details = null): JsonResponse
    {
        return $this->error('BAD_REQUEST', $message, 400, $details);
    }

    public function unauthorized(mixed $details = null): JsonResponse
    {
        return $this->error('UNAUTHORIZED', 'Unauthorized', 401, $details);
    }

    public function forbidden(mixed $details = null): JsonResponse
    {
        return $this->error('FORBIDDEN', 'Forbidden', 403, $details);
    }

    public function notFound(string $resource = 'Resource', mixed $details = null): JsonResponse
    {
        return $this->error('NOT_FOUND', "$resource not found", 404, $details);
    }

    public function conflict(string $message, mixed $details = null): JsonResponse
    {
        return $this->error('CONFLICT', $message, 409, $details);
    }

    public function unprocessable(string $message, mixed $details = null): JsonResponse
    {
        return $this->error('UNPROCESSABLE_ENTITY', $message, 422, $details);
    }

    public function tooManyRequests(string $message = 'Rate limit exceeded', mixed $details = null): JsonResponse
    {
        return $this->error('RATE_LIMIT_EXCEEDED', $message, 429, $details);
    }

    public function serviceUnavailable(string $message = 'Service unavailable', mixed $details = null): JsonResponse
    {
        return $this->error('SERVICE_UNAVAILABLE', $message, 503, $details);
    }

    public function internalError(string $message = 'Internal server error', mixed $details = null): JsonResponse
    {
        return $this->error('INTERNAL_ERROR', $message, 500, $details);
    }
}
