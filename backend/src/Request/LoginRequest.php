<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(min: 10, minMessage: 'Hasło musi mieć co najmniej 10 znaków.')]
    #[Assert\Regex(pattern: '/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', message: 'Hasło musi zawierać małe i duże litery oraz cyfrę.')]
    public ?string $password = null;
}