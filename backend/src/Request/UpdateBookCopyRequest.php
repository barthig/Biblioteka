<?php
namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateBookCopyRequest
{
    #[Assert\Length(min: 1, max: 60)]
    public ?string $inventoryCode = null;

    #[Assert\Choice(
        choices: ['AVAILABLE', 'BORROWED', 'RESERVED', 'WITHDRAWN', 'MAINTENANCE'],
        message: 'Invalid copy status'
    )]
    public ?string $status = null;

    #[Assert\Length(max: 120, maxMessage: 'Location cannot exceed 120 characters')]
    public ?string $location = null;

    #[Assert\Choice(
        choices: ['STORAGE', 'OPEN_STACK', 'REFERENCE'],
        message: 'Invalid access type'
    )]
    public ?string $accessType = null;

    #[Assert\Length(max: 120, maxMessage: 'Condition cannot exceed 120 characters')]
    public ?string $condition = null;
}
