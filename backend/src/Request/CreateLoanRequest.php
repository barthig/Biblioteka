<?php
declare(strict_types=1);
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateLoanRequest
{
    #[Assert\NotBlank(message: 'Book ID is required')]
    #[Assert\Positive]
    public ?int $bookId = null;

    #[Assert\Positive]
    public ?int $userId = null;

    #[Assert\Positive]
    public ?int $reservationId = null;

    #[Assert\Positive]
    public ?int $bookCopyId = null;

    #[Assert\Type('\DateTimeInterface')]
    public ?\DateTimeInterface $dueAt = null;
}
