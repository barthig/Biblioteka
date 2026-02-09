<?php
declare(strict_types=1);
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUserRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(min: 10, minMessage: 'Password must be at least 10 characters')]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
        message: 'Password must contain lowercase, uppercase letters and a digit'
    )]
    public ?string $password = null;

    #[Assert\Type('array')]
    /** @var string[] */
    public array $roles = [];

    #[Assert\Length(max: 30)]
    public ?string $phoneNumber = null;

    #[Assert\Length(max: 255)]
    public ?string $addressLine = null;

    #[Assert\Length(max: 100)]
    public ?string $city = null;

    #[Assert\Regex(pattern: '/^\d{2}-\d{3}$/', message: 'Postal code must be in XX-XXX format')]
    public ?string $postalCode = null;

    #[Assert\Choice(
        choices: ['standard', 'student', 'pracownik_naukowy', 'dziecko'],
        message: 'Invalid membership group'
    )]
    public ?string $membershipGroup = null;
}
