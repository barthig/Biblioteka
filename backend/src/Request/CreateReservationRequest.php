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
}
