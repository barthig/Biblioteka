<?php
declare(strict_types=1);
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateReviewRequest
{
    #[Assert\NotBlank(message: 'Rating is required')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'Rating must be between 1 and 5')]
    public ?int $rating = null;

    #[Assert\Length(max: 2000, maxMessage: 'Comment cannot exceed 2000 characters')]
    public ?string $comment = null;
}
