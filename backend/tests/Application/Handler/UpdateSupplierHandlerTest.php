<?php

namespace App\Tests\Application\Handler;

use App\Application\Command\Acquisition\UpdateSupplierCommand;
use App\Application\Handler\Command\UpdateSupplierHandler;
use App\Entity\Supplier;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateSupplierHandlerTest extends TestCase
{
    public function testHandleUpdatesAllSupplierFields(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Old Supplier')
            ->setContactEmail('old@email.com')
            ->setContactPhone('111111111')
            ->setAddressLine('Old Street')
            ->setCity('Old City')
            ->setCountry('Old Country')
            ->setTaxIdentifier('OLD123')
            ->setNotes('Old notes')
            ->setActive(true);

        $repository = $this->createMock(SupplierRepository::class);
        $repository->expects($this->once())->method('find')->with(1)->willReturn($supplier);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $handler = new UpdateSupplierHandler($entityManager, $repository);

        $command = new UpdateSupplierCommand(
            1,
            'New Supplier Name',
            'new@email.com',
            '999999999',
            'New Street 123',
            'New City',
            'New Country',
            'NEW456',
            'Updated notes',
            false
        );

        $result = ($handler)($command);

        $this->assertEquals('New Supplier Name', $result->getName());
        $this->assertEquals('new@email.com', $result->getContactEmail());
        $this->assertEquals('999999999', $result->getContactPhone());
        $this->assertEquals('New Street 123', $result->getAddressLine());
        $this->assertEquals('New City', $result->getCity());
        $this->assertEquals('New Country', $result->getCountry());
        $this->assertEquals('NEW456', $result->getTaxIdentifier());
        $this->assertEquals('Updated notes', $result->getNotes());
        $this->assertFalse($result->isActive());
    }

    public function testHandleAllowsNullableFields(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier')
            ->setActive(true);

        $repository = $this->createMock(SupplierRepository::class);
        $repository->method('find')->willReturn($supplier);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $handler = new UpdateSupplierHandler($entityManager, $repository);

        $command = new UpdateSupplierCommand(
            1,
            'Updated Name',
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $result = ($handler)($command);

        $this->assertEquals('Updated Name', $result->getName());
        $this->assertNull($result->getContactEmail());
        $this->assertNull($result->getContactPhone());
    }

    public function testHandleThrowsExceptionWhenSupplierNotFound(): void
    {
        $repository = $this->createMock(SupplierRepository::class);
        $repository->expects($this->once())->method('find')->with(999)->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $handler = new UpdateSupplierHandler($entityManager, $repository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Supplier not found');

        $command = new UpdateSupplierCommand(999, 'Name', null, null, null, null, null, null, null, null);
        ($handler)($command);
    }
}
