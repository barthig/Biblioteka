<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateFineRequest
{
    #[Assert\NotBlank(message: 'ID wypożyczenia jest wymagane')]
    #[Assert\Positive]
    public ?int $loanId = null;

    #[Assert\NotBlank(message: 'Kwota jest wymagana')]
    #[Assert\Positive(message: 'Kwota musi być dodatnia')]
    public ?float $amount = null;

    #[Assert\NotBlank(message: 'Waluta jest wymagana')]
    #[Assert\Choice(choices: ['PLN', 'EUR', 'USD'], message: 'Nieprawidłowa waluta')]
    public string $currency = 'PLN';

    #[Assert\NotBlank(message: 'Powód jest wymagany')]
    #[Assert\Length(min: 3, max: 500)]
    public ?string $reason = null;
}
