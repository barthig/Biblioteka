<?php
declare(strict_types=1);
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateLoanRequest
{
    #[Assert\DateTime(message: 'Invalid date format')]
    public ?string $dueAt = null;

    #[Assert\Choice(choices: ['active', 'returned'], message: 'Invalid status')]
    public ?string $status = null;

    #[Assert\Positive]
    public ?int $bookId = null;

    #[Assert\Positive]
    public ?int $bookCopyId = null;
}
