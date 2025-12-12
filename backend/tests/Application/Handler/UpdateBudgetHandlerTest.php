<?php

namespace App\Tests\Application\Handler;

use App\Application\Command\Acquisition\UpdateBudgetCommand;
use App\Application\Handler\Command\UpdateBudgetHandler;
use App\Entity\AcquisitionBudget;
use App\Repository\AcquisitionBudgetRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateBudgetHandlerTest extends TestCase
{
    private AcquisitionBudgetRepository $budgetRepository;
    private EntityManagerInterface $entityManager;
    private UpdateBudgetHandler $handler;

    protected function setUp(): void
    {
        $this->budgetRepository = $this->createMock(AcquisitionBudgetRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new UpdateBudgetHandler(
            $this->entityManager,
            $this->budgetRepository
        );
    }

    public function testHandleUpdatesBudgetSuccessfully(): void
    {
        $budget = new AcquisitionBudget();
        $budget->setName('Old Name')
            ->setFiscalYear('2024')
            ->setAllocatedAmount('100000.00')
            ->setCurrency('PLN');

        $this->budgetRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($budget);

        $this->entityManager->expects($this->once())->method('flush');

        $command = new UpdateBudgetCommand(
            1,
            'Updated Budget Name',
            '2025',
            '150000.00',
            'EUR'
        );

        $result = ($this->handler)($command);

        $this->assertEquals('Updated Budget Name', $result->getName());
        $this->assertEquals('2025', $result->getFiscalYear());
        $this->assertEquals('150000.00', $result->getAllocatedAmount());
        $this->assertEquals('EUR', $result->getCurrency());
    }

    public function testHandleThrowsExceptionWhenBudgetNotFound(): void
    {
        $this->budgetRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Budget not found');

        $command = new UpdateBudgetCommand(999, 'Name', '2024', '10000', 'PLN');
        ($this->handler)($command);
    }

    public function testHandleAllowsPartialUpdate(): void
    {
        $budget = new AcquisitionBudget();
        $budget->setName('Original Name')
            ->setFiscalYear('2024')
            ->setAllocatedAmount('100000.00')
            ->setCurrency('PLN');

        $this->budgetRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($budget);

        $this->entityManager->expects($this->once())->method('flush');

        // Only update name, keep other fields
        $command = new UpdateBudgetCommand(
            1,
            'New Name Only',
            null,
            null,
            null
        );

        $result = ($this->handler)($command);

        $this->assertEquals('New Name Only', $result->getName());
        $this->assertEquals('2024', $result->getFiscalYear()); // unchanged
        $this->assertEquals('100000.00', $result->getAllocatedAmount()); // unchanged
    }
}
