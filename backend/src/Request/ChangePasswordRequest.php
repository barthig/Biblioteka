<?php
declare(strict_types=1);
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordRequest
{
    #[Assert\NotBlank(message: 'Current password is required')]
    public ?string $currentPassword = null;

    #[Assert\NotBlank(message: 'New password is required')]
    #[Assert\Length(min: 10, minMessage: 'Password must be at least 10 characters')]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
        message: 'Password must contain lowercase, uppercase letters and a digit'
    )]
    public ?string $newPassword = null;
}
