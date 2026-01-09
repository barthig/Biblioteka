<?php
namespace App\Controller\Traits;

use App\Dto\ApiError;
use App\Dto\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

trait ValidationTrait
{
    /**
     * Convert validation errors to unified JSON response
     */
    protected function validationErrorResponse(ConstraintViolationListInterface $errors): JsonResponse
    {
        $fieldErrors = [];
        foreach ($errors as $error) {
            $property = $error->getPropertyPath();
            $message = $error->getMessage();
            
            if (isset($fieldErrors[$property])) {
                if (is_array($fieldErrors[$property])) {
                    $fieldErrors[$property][] = $message;
                } else {
                    $fieldErrors[$property] = [$fieldErrors[$property], $message];
                }
            } else {
                $fieldErrors[$property] = $message;
            }
        }
        
        $error = ApiError::validationFailed($fieldErrors);
        $response = ApiResponse::error($error);
        return $this->json($response->toArray(), 400);
    }

    /**
     * Maps array to DTO object
     */
    protected function mapArrayToDto(array $data, object $dto): object
    {
        $reflection = new \ReflectionClass($dto);
        
        foreach ($data as $key => $value) {
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);
                $property->setAccessible(true);
                
                // Konwersja typu jeśli trzeba
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
                    }
                }
                
                $property->setValue($dto, $value);
            }
        }
        
        return $dto;
    }
}
