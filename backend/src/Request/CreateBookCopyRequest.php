<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateBookCopyRequest
{
    #[Assert\NotBlank(message: 'Kod inwentarzowy jest wymagany')]
    #[Assert\Length(min: 1, max: 50)]
    public ?string $inventoryCode = null;

    #[Assert\Choice(
        choices: ['AVAILABLE', 'BORROWED', 'RESERVED', 'WITHDRAWN', 'MAINTENANCE'],
        message: 'Nieprawidłowy status egzemplarza'
    )]
    public string $status = 'AVAILABLE';

    #[Assert\Choice(
        choices: ['magazyn', 'wypożyczalnia', 'czytelnia', 'archiwum'],
        message: 'Nieprawidłowa lokalizacja'
    )]
    public ?string $location = null;

    #[Assert\Choice(
        choices: ['wolny_dostęp', 'magazyn', 'czytelnia', 'zakaz_wypożyczenia'],
        message: 'Nieprawidłowy typ dostępu'
    )]
    public ?string $accessType = null;

    #[Assert\Choice(
        choices: ['nowy', 'dobry', 'zużyty', 'uszkodzony', 'wymaga_naprawy'],
        message: 'Nieprawidłowy stan'
    )]
    public ?string $conditionState = null;
}
