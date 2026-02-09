<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserRequest
{
    #[Assert\Length(min: 2, max: 255)]
    public ?string $name = null;

    #[Assert\Type('array')]
    /** @var string[]|null */
    public ?array $roles = null;

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

    #[Assert\Type('bool')]
    public ?bool $blocked = null;

    public ?string $blockedReason = null;
}
