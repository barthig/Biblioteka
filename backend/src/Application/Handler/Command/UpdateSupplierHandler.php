<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Acquisition\UpdateSupplierCommand;
use App\Entity\Supplier;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateSupplierHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SupplierRepository $repository
    ) {
    }

    public function __invoke(UpdateSupplierCommand $command): Supplier
    {
        $supplier = $this->repository->find($command->id);
        if (!$supplier) {
            throw new \RuntimeException('Supplier not found');
        }

        if ($command->name !== null) {
            $supplier->setName($command->name);
        }
        if ($command->contactEmail !== 'NOT_SET') {
            $supplier->setContactEmail($command->contactEmail);
        }
        if ($command->contactPhone !== 'NOT_SET') {
            $supplier->setContactPhone($command->contactPhone);
        }
        if ($command->addressLine !== 'NOT_SET') {
            $supplier->setAddressLine($command->addressLine);
        }
        if ($command->city !== 'NOT_SET') {
            $supplier->setCity($command->city);
        }
        if ($command->country !== 'NOT_SET') {
            $supplier->setCountry($command->country);
        }
        if ($command->taxIdentifier !== 'NOT_SET') {
            $supplier->setTaxIdentifier($command->taxIdentifier);
        }
        if ($command->notes !== 'NOT_SET') {
            $supplier->setNotes($command->notes);
        }
        if ($command->active !== null) {
            $supplier->setActive($command->active);
        }

        $this->entityManager->persist($supplier);
        $this->entityManager->flush();

        return $supplier;
    }
}
