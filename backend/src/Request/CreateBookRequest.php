<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateBookRequest
{
    #[Assert\NotBlank(message: 'Tytuł książki jest wymagany')]
    #[Assert\Length(min: 1, max: 255, maxMessage: 'Tytuł nie może przekraczać 255 znaków')]
    public ?string $title = null;

    #[Assert\NotBlank(message: 'Autor jest wymagany')]
    #[Assert\Positive(message: 'ID autora musi być liczbą dodatnią')]
    public ?int $authorId = null;

    #[Assert\Isbn(message: 'Nieprawidłowy format ISBN')]
    public ?string $isbn = null;

    #[Assert\Type('array')]
    public array $categoryIds = [];

    #[Assert\Length(max: 5000)]
    public ?string $description = null;

    #[Assert\Length(max: 255)]
    public ?string $publisher = null;

    #[Assert\Range(min: 1000, max: 2100, notInRangeMessage: 'Rok publikacji musi być między {{ min }} a {{ max }}')]
    public ?int $publicationYear = null;

    #[Assert\Choice(choices: ['książka', 'czasopismo', 'materiał_audiowizualny', 'e-book'], message: 'Nieprawidłowy typ zasobu')]
    public ?string $resourceType = null;

    #[Assert\Length(max: 50)]
    public ?string $signature = null;

    #[Assert\Choice(choices: ['dzieci', 'młodzież', 'dorośli', 'wszystkie'], message: 'Nieprawidłowa grupa wiekowa')]
    public ?string $targetAgeGroup = null;

    #[Assert\PositiveOrZero(message: 'Liczba egzemplarzy musi być nieujemna')]
    public ?int $totalCopies = null;
}
