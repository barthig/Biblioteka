<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateLoanRequest
{
    #[Assert\DateTime(message: 'Nieprawidłowy format daty')]
    public ?string $dueAt = null;

    #[Assert\Choice(choices: ['active', 'returned'], message: 'Nieprawidłowy status')]
    public ?string $status = null;

    #[Assert\Positive]
    public ?int $bookId = null;

    #[Assert\Positive]
    public ?int $bookCopyId = null;
}
