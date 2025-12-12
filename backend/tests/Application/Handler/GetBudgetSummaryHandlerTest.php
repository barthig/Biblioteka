<?php

namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\GetBudgetSummaryHandler;
use App\Application\Query\Acquisition\GetBudgetSummaryQuery;
use App\Entity\AcquisitionBudget;
use App\Repository\AcquisitionBudgetRepository;
use PHPUnit\Framework\TestCase;

class GetBudgetSummaryHandlerTest extends TestCase
{
    public function testHandleReturnsFormattedBudgetSummary(): void
    {
        $budget = new AcquisitionBudget();
        $budget->setName('Test Budget')
            ->setFiscalYear('2024')
            ->setAllocatedAmount('100000.00')
            ->setSpentAmount('35500.50')
            ->setCurrency('PLN');

        $repository = $this->createMock(AcquisitionBudgetRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($budget);

        $handler = new GetBudgetSummaryHandler($repository);
        $query = new GetBudgetSummaryQuery(1);

        $result = ($handler)($query);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('fiscalYear', $result);
        $this->assertArrayHasKey('allocatedAmount', $result);
        $this->assertArrayHasKey('spentAmount', $result);
        $this->assertArrayHasKey('remainingAmount', $result);
        $this->assertArrayHasKey('currency', $result);

        $this->assertEquals('Test Budget', $result['name']);
        $this->assertEquals('2024', $result['fiscalYear']);
        $this->assertEquals(100000.00, $result['allocatedAmount']);
        $this->assertEquals(35500.50, $result['spentAmount']);
        $this->assertEquals(64499.50, $result['remainingAmount']);
        $this->assertEquals('PLN', $result['currency']);
    }

    public function testHandleThrowsExceptionWhenBudgetNotFound(): void
    {
        $repository = $this->createMock(AcquisitionBudgetRepository::class);
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $handler = new GetBudgetSummaryHandler($repository);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Budget not found');

        $query = new GetBudgetSummaryQuery(999);
        ($handler)($query);
    }

    public function testHandleCalculatesRemainingAmountCorrectly(): void
    {
        $budget = new AcquisitionBudget();
        $budget->setName('Math Test Budget')
            ->setFiscalYear('2025')
            ->setAllocatedAmount('50000.00')
            ->setSpentAmount('12345.67')
            ->setCurrency('EUR');

        $repository = $this->createMock(AcquisitionBudgetRepository::class);
        $repository->method('find')->willReturn($budget);

        $handler = new GetBudgetSummaryHandler($repository);
        $query = new GetBudgetSummaryQuery(1);

        $result = ($handler)($query);

        $this->assertEquals(37654.33, $result['remainingAmount']);
    }
}
