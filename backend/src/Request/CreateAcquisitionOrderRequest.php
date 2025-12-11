<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateAcquisitionOrderRequest
{
    #[Assert\NotBlank(message: 'ID dostawcy jest wymagane')]
    #[Assert\Positive]
    public ?int $supplierId = null;

    #[Assert\NotBlank(message: 'Tytuł zamówienia jest wymagany')]
    #[Assert\Length(min: 3, max: 500)]
    public ?string $title = null;

    #[Assert\Positive]
    public ?int $budgetId = null;

    #[Assert\Positive]
    public ?float $estimatedCost = null;

    #[Assert\Choice(choices: ['PLN', 'EUR', 'USD'], message: 'Nieprawidłowa waluta')]
    public string $currency = 'PLN';

    #[Assert\Length(max: 2000)]
    public ?string $notes = null;

    #[Assert\Choice(
        choices: ['planowane', 'zamówione', 'w_realizacji', 'dostarczone', 'anulowane'],
        message: 'Nieprawidłowy status'
    )]
    public string $status = 'planowane';
}
