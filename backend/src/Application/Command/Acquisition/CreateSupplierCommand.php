<?php
namespace App\Application\Command\Acquisition;

class CreateSupplierCommand
{
    public function __construct(
        public readonly string $name,
        public readonly bool $active,
        public readonly ?string $contactEmail,
        public readonly ?string $contactPhone,
        public readonly ?string $addressLine,
        public readonly ?string $city,
        public readonly ?string $country,
        public readonly ?string $taxIdentifier,
        public readonly ?string $notes
    ) {
    }
}
