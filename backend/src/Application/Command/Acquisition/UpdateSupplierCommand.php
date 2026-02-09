<?php
declare(strict_types=1);
namespace App\Application\Command\Acquisition;

class UpdateSupplierCommand
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly mixed $contactEmail,
        public readonly mixed $contactPhone,
        public readonly mixed $addressLine,
        public readonly mixed $city,
        public readonly mixed $country,
        public readonly mixed $taxIdentifier,
        public readonly mixed $notes,
        public readonly ?bool $active
    ) {
    }
}
