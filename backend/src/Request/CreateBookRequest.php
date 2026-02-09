<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateBookRequest
{
    #[Assert\NotBlank(message: 'Book title is required')]
    #[Assert\Length(min: 1, max: 255, maxMessage: 'Title cannot exceed 255 characters')]
    public ?string $title = null;

    #[Assert\NotBlank(message: 'Author is required')]
    #[Assert\Positive(message: 'Author ID must be a positive number')]
    public ?int $authorId = null;

    #[Assert\Isbn(message: 'Invalid ISBN format')]
    public ?string $isbn = null;

    #[Assert\Type('array')]
    /** @var int[] */
    public array $categoryIds = [];

    #[Assert\Length(max: 5000)]
    public ?string $description = null;

    #[Assert\Length(max: 255)]
    public ?string $publisher = null;

    #[Assert\Range(min: 1000, max: 2100, notInRangeMessage: 'Publication year must be between {{ min }} and {{ max }}')]
    public ?int $publicationYear = null;

    #[Assert\Choice(choices: ['książka', 'czasopismo', 'materiał_audiowizualny', 'e-book'], message: 'Invalid resource type')]
    public ?string $resourceType = null;

    #[Assert\Length(max: 50)]
    public ?string $signature = null;

    #[Assert\Choice(choices: ['dzieci', 'młodzież', 'dorośli', 'wszystkie'], message: 'Invalid target age group')]
    public ?string $targetAgeGroup = null;

    #[Assert\PositiveOrZero(message: 'Total copies must be non-negative')]
    public ?int $totalCopies = null;
}
