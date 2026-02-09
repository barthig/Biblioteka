<?php
declare(strict_types=1);
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateAccountRequest
{
    #[Assert\Length(min: 2, max: 255)]
    public ?string $name = null;

    #[Assert\Length(max: 30)]
    public ?string $phoneNumber = null;

    #[Assert\Length(max: 255)]
    public ?string $addressLine = null;

    #[Assert\Length(max: 100)]
    public ?string $city = null;

    #[Assert\Regex(pattern: '/^\d{2}-\d{3}$/', message: 'Postal code must be in XX-XXX format')]
    public ?string $postalCode = null;
}
