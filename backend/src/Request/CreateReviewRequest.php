<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateReviewRequest
{
    #[Assert\NotBlank(message: 'Ocena jest wymagana')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'Ocena musi być między 1 a 5')]
    public ?int $rating = null;

    #[Assert\Length(max: 2000, maxMessage: 'Komentarz nie może przekraczać 2000 znaków')]
    public ?string $comment = null;
}
