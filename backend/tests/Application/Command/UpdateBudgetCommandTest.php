<?php

namespace App\Tests\Application\Command;

use App\Application\Command\Acquisition\UpdateBudgetCommand;
use PHPUnit\Framework\TestCase;

class UpdateBudgetCommandTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $command = new UpdateBudgetCommand(
            1,
            'Updated Budget',
            '2025',
            '200000.00',
            'EUR'
        );

        $this->assertEquals(1, $command->id);
        $this->assertEquals('Updated Budget', $command->name);
        $this->assertEquals('2025', $command->fiscalYear);
        $this->assertEquals('200000.00', $command->allocatedAmount);
        $this->assertEquals('EUR', $command->currency);
    }

    public function testConstructorAllowsNullValues(): void
    {
        $command = new UpdateBudgetCommand(
            1,
            null,
            null,
            null,
            null
        );

        $this->assertEquals(1, $command->id);
        $this->assertNull($command->name);
        $this->assertNull($command->fiscalYear);
        $this->assertNull($command->allocatedAmount);
        $this->assertNull($command->currency);
    }
}
