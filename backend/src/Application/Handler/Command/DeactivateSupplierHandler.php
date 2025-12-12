<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Acquisition\DeactivateSupplierCommand;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeactivateSupplierHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SupplierRepository $repository
    ) {
    }

    public function __invoke(DeactivateSupplierCommand $command): void
    {
        $supplier = $this->repository->find($command->id);
        if (!$supplier) {
            throw new \RuntimeException('Supplier not found');
        }

        $supplier->setActive(false);
        $this->entityManager->persist($supplier);
        $this->entityManager->flush();
    }
}
