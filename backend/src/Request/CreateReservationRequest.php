<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateReservationRequest
{
    #[Assert\NotBlank(message: 'ID książki jest wymagane')]
    #[Assert\Positive]
    public ?int $bookId = null;

    #[Assert\Positive]
    public ?int $userId = null;

    #[Assert\Positive]
    #[Assert\Range(
        min: 1,
        max: 14,
        notInRangeMessage: 'Rezerwacja może trwać od {{ min }} do {{ max }} dni'
    )]
    public ?int $days = 3; // Default 3 days
}
