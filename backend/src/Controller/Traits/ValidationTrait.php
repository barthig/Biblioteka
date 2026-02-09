<?php
namespace App\Controller\Traits;

use App\Dto\ApiError;
use App\Dto\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

trait ValidationTrait
{
    /**
     * Convert validation errors to unified JSON response.
     * Field errors are always arrays for consistent API contract.
     */
    protected function validationErrorResponse(ConstraintViolationListInterface $errors): JsonResponse
    {
        $fieldErrors = [];
        foreach ($errors as $error) {
            $property = $error->getPropertyPath();
            $message = $error->getMessage();
            $fieldErrors[$property][] = $message;
        }
        
        $error = ApiError::validationFailed($fieldErrors);
        $response = ApiResponse::error($error);
        return $this->json($response->toArray(), 400);
    }

    /**
     * Maps array to DTO object with automatic type coercion.
     */
    protected function mapArrayToDto(array $data, object $dto): object
    {
        $reflection = new \ReflectionClass($dto);
        
        foreach ($data as $key => $value) {
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);
                $property->setAccessible(true);
                
                // Type coercion for scalar and DateTime types
                $type = $property->getType();
                if ($type instanceof \ReflectionNamedType && $value !== null) {
                    $typeName = $type->getName();
                    
                    if ($typeName === 'int' && !is_int($value)) {
                        $value = (int) $value;
                    } elseif ($typeName === 'float' && !is_float($value)) {
                        $value = (float) $value;
                    } elseif ($typeName === 'bool' && !is_bool($value)) {
                        $value = (bool) $value;
                    } elseif ($typeName === 'string' && !is_string($value)) {
                        $value = (string) $value;
                    } elseif (is_string($value) && is_a($typeName, \DateTimeInterface::class, true)) {
                        $value = new \DateTimeImmutable($value);
                    }
                }
                
                $property->setValue($dto, $value);
            }
        }
        
        return $dto;
    }
}
