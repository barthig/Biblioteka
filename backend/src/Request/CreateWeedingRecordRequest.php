<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateWeedingRecordRequest
{
    #[Assert\NotBlank(message: 'ID książki jest wymagane')]
    #[Assert\Positive]
    public ?int $bookId = null;

    #[Assert\Positive]
    public ?int $copyId = null;

    #[Assert\NotBlank(message: 'Powód wycofania jest wymagany')]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $reason = null;

    #[Assert\Length(max: 1000)]
    public ?string $notes = null;

    #[Assert\Length(max: 50)]
    public ?string $action = null;

    #[Assert\Length(max: 50)]
    public ?string $conditionState = null;

    public ?string $removedAt = null;
}
