<?php

namespace App\Tests\Application\Command;

use App\Application\Command\Acquisition\CreateSupplierCommand;
use PHPUnit\Framework\TestCase;

class CreateSupplierCommandTest extends TestCase
{
    public function testConstructorWithAllFields(): void
    {
        $command = new CreateSupplierCommand(
            'Supplier Inc.',
            true,
            'info@supplier.com',
            '+48123456789',
            'Main Street 123',
            'Warsaw',
            'Poland',
            'PL1234567890',
            'Important supplier'
        );

        $this->assertEquals('Supplier Inc.', $command->name);
        $this->assertTrue($command->active);
        $this->assertEquals('info@supplier.com', $command->contactEmail);
        $this->assertEquals('+48123456789', $command->contactPhone);
        $this->assertEquals('Main Street 123', $command->addressLine);
        $this->assertEquals('Warsaw', $command->city);
        $this->assertEquals('Poland', $command->country);
        $this->assertEquals('PL1234567890', $command->taxIdentifier);
        $this->assertEquals('Important supplier', $command->notes);
    }

    public function testConstructorWithMinimalFields(): void
    {
        $command = new CreateSupplierCommand(
            'Minimal Supplier',
            false,
            null,
            null,
            null,
            null,
            null,
            null,
            null
        );

        $this->assertEquals('Minimal Supplier', $command->name);
        $this->assertFalse($command->active);
        $this->assertNull($command->contactEmail);
        $this->assertNull($command->contactPhone);
    }
}
