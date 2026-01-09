<?php

namespace App\Middleware;

use App\Dto\ApiError;
use App\Dto\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * This middleware auto-converts legacy error responses ['message' => ...] 
 * to the new standardized ApiResponse format during transition period.
 * Controllers can continue using old format and this will normalize them.
 */
class LegacyErrorResponseConverter
{
    /**
     * Converts a JsonResponse with legacy format to new standardized format
     */
    public static function convertIfNeeded(JsonResponse $response): void
    {
        // Skip if already converted (has proper structure with 'error' code field)
        $content = json_decode($response->getContent(), true);
        if (!is_array($content)) {
            return;
        }

        // If has structured error with code, it's already in new format
        if (isset($content['error'], $content['error']['code'])) {
            return;
        }

        // If has data key, it's success response in new format
        if (isset($content['data'])) {
            return;
        }

        // Legacy format detection: has 'message' but not 'error' code
        if (isset($content['message']) && !isset($content['error']['code'])) {
            $statusCode = $response->getStatusCode();
            
            // Handle validation error with field-specific errors
            if (isset($content['errors']) && is_array($content['errors'])) {
                $error = ApiError::validationFailed($content['errors']);
            } else {
                $error = new ApiError(
                    code: self::getErrorCode($statusCode, $content['message']),
                    message: $content['message'],
                    statusCode: $statusCode
                );
            }

            $apiResponse = ApiResponse::error($error);
            $response->setContent(json_encode($apiResponse->toArray()));
        }
    }

    private static function getErrorCode(int $statusCode, string $message = ''): string
    {
        return match ($statusCode) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'UNPROCESSABLE_ENTITY',
            423 => 'LOCKED',
            429 => 'RATE_LIMIT_EXCEEDED',
            500 => 'INTERNAL_ERROR',
            503 => 'SERVICE_UNAVAILABLE',
            default => 'ERROR',
        };
    }
}
