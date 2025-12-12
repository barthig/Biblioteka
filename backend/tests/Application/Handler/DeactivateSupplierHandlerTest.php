<?php

namespace App\Tests\Application\Handler;

use App\Application\Command\Acquisition\DeactivateSupplierCommand;
use App\Application\Handler\Command\DeactivateSupplierHandler;
use App\Entity\Supplier;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DeactivateSupplierHandlerTest extends TestCase
{
    public function testHandleDeactivatesActiveSupplier(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Active Supplier')->setActive(true);

        $repository = $this->createMock(SupplierRepository::class);
        $repository->expects($this->once())->method('find')->with(1)->willReturn($supplier);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $handler = new DeactivateSupplierHandler($entityManager, $repository);
        $command = new DeactivateSupplierCommand(1);

        ($handler)($command);

        $this->assertFalse($supplier->isActive());
    }

    public function testHandleAllowsDeactivatingAlreadyInactiveSupplier(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Inactive Supplier')->setActive(false);

        $repository = $this->createMock(SupplierRepository::class);
        $repository->method('find')->willReturn($supplier);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $handler = new DeactivateSupplierHandler($entityManager, $repository);
        $command = new DeactivateSupplierCommand(1);

        ($handler)($command);

        $this->assertFalse($supplier->isActive());
    }

    public function testHandleThrowsExceptionWhenSupplierNotFound(): void
    {
        $repository = $this->createMock(SupplierRepository::class);
        $repository->expects($this->once())->method('find')->with(999)->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $handler = new DeactivateSupplierHandler($entityManager, $repository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Supplier not found');

        $command = new DeactivateSupplierCommand(999);
        ($handler)($command);
    }
}
