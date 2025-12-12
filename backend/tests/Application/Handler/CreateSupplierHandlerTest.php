<?php

namespace App\Tests\Application\Handler;

use App\Application\Command\Acquisition\CreateSupplierCommand;
use App\Application\Handler\Command\CreateSupplierHandler;
use App\Entity\Supplier;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateSupplierHandlerTest extends TestCase
{
    public function testHandleCreatesSupplier(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $handler = new CreateSupplierHandler($entityManager);
        
        $command = new CreateSupplierCommand(
            'Test Supplier',
            true,
            'contact@supplier.com',
            '+48123456789',
            'Main Street 123',
            'Warsaw',
            'Poland',
            'PL1234567890',
            'Test notes'
        );

        $result = ($handler)($command);

        $this->assertInstanceOf(Supplier::class, $result);
        $this->assertEquals('Test Supplier', $result->getName());
        $this->assertTrue($result->isActive());
    }
}
