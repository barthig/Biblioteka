<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class AddFavoriteRequest
{
    #[Assert\NotBlank(message: 'ID książki jest wymagane')]
    #[Assert\Positive]
    public ?int $bookId = null;
}
