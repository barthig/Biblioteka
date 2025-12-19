<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateBookRequest
{
    #[Assert\Length(min: 1, max: 255)]
    public ?string $title = null;

    #[Assert\Positive]
    public ?int $authorId = null;

    #[Assert\Isbn]
    public ?string $isbn = null;

    #[Assert\Type('array')]
    /** @var int[]|null */
    public ?array $categoryIds = null;

    #[Assert\Length(max: 5000)]
    public ?string $description = null;

    #[Assert\Length(max: 255)]
    public ?string $publisher = null;

    #[Assert\Range(min: 1000, max: 2100)]
    public ?int $publicationYear = null;

    #[Assert\Choice(choices: ['książka', 'czasopismo', 'materiał_audiowizualny', 'e-book'])]
    public ?string $resourceType = null;

    #[Assert\Length(max: 50)]
    public ?string $signature = null;

    #[Assert\Choice(choices: ['dzieci', 'młodzież', 'dorośli', 'wszystkie'])]
    public ?string $targetAgeGroup = null;
}
