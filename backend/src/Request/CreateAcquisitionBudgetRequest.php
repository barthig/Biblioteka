<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateAcquisitionBudgetRequest
{
    #[Assert\NotBlank(message: 'Budget name is required')]
    #[Assert\Length(min: 3, max: 255)]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'Fiscal year is required')]
    #[Assert\Range(min: 2000, max: 2100)]
    public ?int $fiscalYear = null;

    #[Assert\NotBlank(message: 'Budget amount is required')]
    #[Assert\Positive]
    public ?float $allocatedAmount = null;

    #[Assert\Choice(choices: ['PLN', 'EUR', 'USD'])]
    public string $currency = 'PLN';

    #[Assert\Positive]
    public ?float $spentAmount = null;

    #[Assert\Length(max: 1000)]
    public ?string $description = null;
}
