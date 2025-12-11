<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateSettingsRequest
{
    #[Assert\Type('array')]
    public array $settings = [];
}
