<?php
declare(strict_types=1);
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Password is required')]
    // #[Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters long.')]
    // #[Assert\Regex(pattern: '/(?=.*[a-zA-Z])(?=.*\d)/', message: 'Password must contain letters and at least one digit.')]
    public ?string $password = null;
}
