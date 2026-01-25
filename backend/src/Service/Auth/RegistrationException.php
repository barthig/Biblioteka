<?php
namespace App\Service\Auth;

class RegistrationException extends \RuntimeException
{
    public static function validation(string $message, int $code = 400): self
    {
        return new self($message, $code);
    }
}

