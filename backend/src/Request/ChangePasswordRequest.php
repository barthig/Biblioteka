<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordRequest
{
    #[Assert\NotBlank(message: 'Obecne hasło jest wymagane')]
    public ?string $currentPassword = null;

    #[Assert\NotBlank(message: 'Nowe hasło jest wymagane')]
    #[Assert\Length(min: 10, minMessage: 'Hasło musi mieć co najmniej 10 znaków')]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
        message: 'Hasło musi zawierać małe i duże litery oraz cyfrę'
    )]
    public ?string $newPassword = null;
}
