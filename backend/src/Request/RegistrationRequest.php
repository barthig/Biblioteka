<?php
declare(strict_types=1);
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class RegistrationRequest
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Invalid email format')]
    #[Assert\Length(max: 180, maxMessage: 'Email cannot exceed 180 characters')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Name must be at least 2 characters', maxMessage: 'Name cannot exceed 255 characters')]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(min: 10, minMessage: 'Password must be at least 10 characters')]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
        message: 'Password must contain lowercase, uppercase letters and a digit'
    )]
    public ?string $password = null;

    #[Assert\Length(max: 30, maxMessage: 'Phone number cannot exceed 30 characters')]
    public ?string $phoneNumber = null;

    #[Assert\Length(max: 255, maxMessage: 'Address cannot exceed 255 characters')]
    public ?string $addressLine = null;

    #[Assert\Length(max: 100, maxMessage: 'City cannot exceed 100 characters')]
    public ?string $city = null;

    #[Assert\Regex(pattern: '/^\d{2}-\d{3}$/', message: 'Postal code must be in XX-XXX format')]
    public ?string $postalCode = null;

    #[Assert\Choice(
        choices: ['standard', 'student', 'pracownik_naukowy', 'dziecko'],
        message: 'Invalid membership group'
    )]
    public ?string $membershipGroup = null;

    public ?bool $privacyConsent = null;

    public ?bool $newsletterSubscribed = null;

    #[Assert\Length(max: 500, maxMessage: 'Taste preferences cannot exceed 500 characters')]
    public ?string $tastePrompt = null;
}
