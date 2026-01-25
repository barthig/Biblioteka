<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception thrown when validation fails.
 * HTTP Status: 400 Bad Request
 */
class ValidationException extends AppException
{
    protected int $statusCode = 400;
    protected ?string $errorCode = 'VALIDATION_ERROR';
    private array $errors = [];

    public static function fromErrors(array $errors): self
    {
        $messages = [];
        foreach ($errors as $field => $fieldErrors) {
            if (is_array($fieldErrors)) {
                $messages[] = sprintf('%s: %s', $field, implode(', ', $fieldErrors));
            } else {
                $messages[] = sprintf('%s: %s', $field, $fieldErrors);
            }
        }

        $exception = new self(implode('; ', $messages));
        $exception->errors = $errors;
        $exception->setContext(['validation_errors' => $errors]);
        return $exception;
    }

    public static function forField(string $field, string $message): self
    {
        return self::fromErrors([$field => [$message]]);
    }

    public static function forRequiredField(string $field): self
    {
        return self::forField($field, sprintf('The %s field is required.', $field));
    }

    public static function forInvalidFormat(string $field, string $expectedFormat): self
    {
        return self::forField($field, sprintf('The %s field must be in %s format.', $field, $expectedFormat));
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'errors' => $this->errors,
        ]);
    }
}
