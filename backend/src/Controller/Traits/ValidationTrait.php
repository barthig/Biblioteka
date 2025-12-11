<?php
namespace App\Controller\Traits;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\ConstraintViolationListInterface;

trait ValidationTrait
{
    /**
     * Konwertuje błędy walidacji na odpowiedź JSON
     */
    protected function validationErrorResponse(ConstraintViolationListInterface $errors): JsonResponse
    {
        $messages = [];
        foreach ($errors as $error) {
            $property = $error->getPropertyPath();
            $message = $error->getMessage();
            
            if (isset($messages[$property])) {
                if (is_array($messages[$property])) {
                    $messages[$property][] = $message;
                } else {
                    $messages[$property] = [$messages[$property], $message];
                }
            } else {
                $messages[$property] = $message;
            }
        }
        
        return $this->json(['errors' => $messages], 400);
    }

    /**
     * Mapuje dane z tablicy do obiektu DTO
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
