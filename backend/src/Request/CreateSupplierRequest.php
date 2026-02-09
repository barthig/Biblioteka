<?php
declare(strict_types=1);
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateSupplierRequest
{
    #[Assert\NotBlank(message: 'Supplier name is required')]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $name = null;

    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\Length(max: 30)]
    public ?string $phone = null;

    #[Assert\Length(max: 500)]
    public ?string $address = null;

    #[Assert\Length(max: 1000)]
    public ?string $notes = null;
}
