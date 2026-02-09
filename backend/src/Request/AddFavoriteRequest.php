<?php
declare(strict_types=1);
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class AddFavoriteRequest
{
    #[Assert\NotBlank(message: 'Book ID is required')]
    #[Assert\Positive]
    public ?int $bookId = null;
}
