<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Acquisition\CreateSupplierCommand;
use App\Entity\Supplier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class CreateSupplierHandler
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(CreateSupplierCommand $command): Supplier
    {
        $supplier = new Supplier();
        $supplier->setName($command->name)
            ->setContactEmail($command->contactEmail)
            ->setContactPhone($command->contactPhone)
            ->setAddressLine($command->addressLine)
            ->setCity($command->city)
            ->setCountry($command->country)
            ->setTaxIdentifier($command->taxIdentifier)
            ->setNotes($command->notes)
            ->setActive($command->active);

        $this->entityManager->persist($supplier);
        $this->entityManager->flush();

        return $supplier;
    }
}
