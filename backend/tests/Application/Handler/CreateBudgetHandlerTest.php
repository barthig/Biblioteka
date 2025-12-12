<?php

namespace App\Tests\Application\Handler;

use App\Application\Command\Acquisition\CreateBudgetCommand;
use App\Application\Handler\Command\CreateBudgetHandler;
use App\Entity\AcquisitionBudget;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateBudgetHandlerTest extends TestCase
{
    public function testHandleCreatesAndPersistsBudget(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $handler = new CreateBudgetHandler($entityManager);
        
        $command = new CreateBudgetCommand(
            'Budget 2024',
            '2024',
            '100000.00',
            'PLN',
            null
        );

        $result = ($handler)($command);

        $this->assertInstanceOf(AcquisitionBudget::class, $result);
        $this->assertEquals('Budget 2024', $result->getName());
        $this->assertEquals('2024', $result->getFiscalYear());
        $this->assertEquals('PLN', $result->getCurrency());
    }
}
