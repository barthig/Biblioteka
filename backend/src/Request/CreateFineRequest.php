<?php
declare(strict_types=1);
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateFineRequest
{
    #[Assert\NotBlank(message: 'Loan ID is required')]
    #[Assert\Positive]
    public ?int $loanId = null;

    #[Assert\NotBlank(message: 'Amount is required')]
    #[Assert\Positive(message: 'Amount must be positive')]
    public ?float $amount = null;

    #[Assert\NotBlank(message: 'Currency is required')]
    #[Assert\Choice(choices: ['PLN', 'EUR', 'USD'], message: 'Invalid currency')]
    public string $currency = 'PLN';

    #[Assert\NotBlank(message: 'Reason is required')]
    #[Assert\Length(min: 3, max: 500)]
    public ?string $reason = null;
}
