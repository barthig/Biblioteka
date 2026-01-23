<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateBookCopyRequest
{
    #[Assert\NotBlank(message: 'Kod inwentarzowy jest wymagany')]
    #[Assert\Length(min: 1, max: 60)]
    public ?string $inventoryCode = null;

    #[Assert\Choice(
        choices: ['AVAILABLE', 'BORROWED', 'RESERVED', 'WITHDRAWN', 'MAINTENANCE'],
        message: 'Nieprawidlowy status egzemplarza'
    )]
    public string $status = 'AVAILABLE';

    #[Assert\Length(max: 120, maxMessage: 'Lokalizacja moze miec maksymalnie 120 znakow')]
    public ?string $location = null;

    #[Assert\Choice(
        choices: ['STORAGE', 'OPEN_STACK', 'REFERENCE'],
        message: 'Nieprawidlowy typ dostepu'
    )]
    public ?string $accessType = null;

    #[Assert\Length(max: 120, maxMessage: 'Stan moze miec maksymalnie 120 znakow')]
    public ?string $condition = null;
}
