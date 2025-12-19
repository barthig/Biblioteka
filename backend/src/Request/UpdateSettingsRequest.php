<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateSettingsRequest
{
    #[Assert\Type('array')]
    /** @var array<string, mixed> */
    public array $settings = [];
}
