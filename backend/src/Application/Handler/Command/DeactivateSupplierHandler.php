<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Acquisition\DeactivateSupplierCommand;
use App\Exception\NotFoundException;
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
            throw NotFoundException::forEntity('Supplier', $command->id);
        }

        $supplier->setActive(false);
        $this->entityManager->persist($supplier);
        $this->entityManager->flush();
    }
}
