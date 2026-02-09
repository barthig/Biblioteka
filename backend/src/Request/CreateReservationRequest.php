<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateReservationRequest
{
    #[Assert\NotBlank(message: 'Book ID is required')]
    #[Assert\Positive]
    public ?int $bookId = null;

    #[Assert\Positive]
    #[Assert\Range(
        min: 1,
        max: 14,
        notInRangeMessage: 'Reservation duration must be between {{ min }} and {{ max }} days'
    )]
    public ?int $days = 3; // Default 3 days (unified with Reservation entity)
}
