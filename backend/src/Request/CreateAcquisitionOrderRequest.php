<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateAcquisitionOrderRequest
{
    #[Assert\NotBlank(message: 'Supplier ID is required')]
    #[Assert\Positive]
    public ?int $supplierId = null;

    #[Assert\NotBlank(message: 'Order title is required')]
    #[Assert\Length(min: 3, max: 500)]
    public ?string $title = null;

    #[Assert\Positive]
    public ?int $budgetId = null;

    #[Assert\Positive]
    public ?float $estimatedCost = null;

    #[Assert\Choice(choices: ['PLN', 'EUR', 'USD'], message: 'Invalid currency')]
    public string $currency = 'PLN';

    #[Assert\Length(max: 2000)]
    public ?string $notes = null;

    #[Assert\Choice(
        choices: ['planowane', 'zamówione', 'w_realizacji', 'dostarczone', 'anulowane'],
        message: 'Invalid status'
    )]
    public string $status = 'planowane';
}
