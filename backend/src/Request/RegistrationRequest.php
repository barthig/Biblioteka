<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class RegistrationRequest
{
    #[Assert\NotBlank(message: 'Email jest wymagany')]
    #[Assert\Email(message: 'Nieprawidłowy format email')]
    #[Assert\Length(max: 180, maxMessage: 'Email nie może przekraczać 180 znaków')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Imię i nazwisko jest wymagane')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Imię musi mieć co najmniej 2 znaki', maxMessage: 'Imię nie może przekraczać 255 znaków')]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'Hasło jest wymagane')]
    #[Assert\Length(min: 10, minMessage: 'Hasło musi mieć co najmniej 10 znaków')]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
        message: 'Hasło musi zawierać małe i duże litery oraz cyfrę'
    )]
    public ?string $password = null;

    #[Assert\Length(max: 30, maxMessage: 'Numer telefonu nie może przekraczać 30 znaków')]
    public ?string $phoneNumber = null;

    #[Assert\Length(max: 255, maxMessage: 'Adres nie może przekraczać 255 znaków')]
    public ?string $addressLine = null;

    #[Assert\Length(max: 100, maxMessage: 'Miasto nie może przekraczać 100 znaków')]
    public ?string $city = null;

    #[Assert\Regex(pattern: '/^\d{2}-\d{3}$/', message: 'Kod pocztowy musi być w formacie XX-XXX')]
    public ?string $postalCode = null;

    #[Assert\Choice(
        choices: ['standard', 'student', 'pracownik_naukowy', 'dziecko'],
        message: 'Nieprawidłowa grupa członkowska'
    )]
    public ?string $membershipGroup = null;

    public ?bool $privacyConsent = null;

    public ?bool $newsletterSubscribed = null;
}
