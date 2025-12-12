<?php

namespace App\Tests\Application\Handler;

use App\Application\Command\Acquisition\CreateOrderCommand;
use App\Application\Handler\Command\CreateOrderHandler;
use App\Entity\AcquisitionBudget;
use App\Entity\AcquisitionOrder;
use App\Entity\Supplier;
use App\Repository\AcquisitionBudgetRepository;
use App\Repository\SupplierRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateOrderHandlerTest extends TestCase
{
    private SupplierRepository $supplierRepository;
    private AcquisitionBudgetRepository $budgetRepository;
    private EntityManagerInterface $entityManager;
    private CreateOrderHandler $handler;

    protected function setUp(): void
    {
        $this->supplierRepository = $this->createMock(SupplierRepository::class);
        $this->budgetRepository = $this->createMock(AcquisitionBudgetRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->handler = new CreateOrderHandler(
            $this->entityManager,
            $this->supplierRepository,
            $this->budgetRepository
        );
    }

    public function testHandleCreatesOrderWithAllFields(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier')->setActive(true);

        $budget = new AcquisitionBudget();
        $budget->setName('Test Budget')
            ->setFiscalYear('2024')
            ->setAllocatedAmount('100000.00')
            ->setCurrency('PLN');

        $this->supplierRepository->method('find')->with(1)->willReturn($supplier);
        $this->budgetRepository->method('find')->with(1)->willReturn($budget);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CreateOrderCommand(
            1,
            1,
            'Test Book Order',
            '5000.00',
            'PLN',
            'Order description',
            'REF-2024-001',
            [['isbn' => '1234567890', 'quantity' => 5]],
            '2024-12-31',
            'draft'
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(AcquisitionOrder::class, $result);
        $this->assertEquals('Test Book Order', $result->getTitle());
        $this->assertEquals('5000.00', $result->getTotalAmount());
        $this->assertEquals('PLN', $result->getCurrency());
        $this->assertEquals('Order description', $result->getDescription());
        $this->assertEquals('REF-2024-001', $result->getReferenceNumber());
    }

    public function testHandleThrowsExceptionWhenSupplierNotFound(): void
    {
        $this->supplierRepository->method('find')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Supplier not found');

        $command = new CreateOrderCommand(
            999,
            null,
            'Title',
            '1000.00',
            'PLN',
            null,
            null,
            null,
            null,
            null
        );

        ($this->handler)($command);
    }

    public function testHandleThrowsExceptionWhenSupplierIsInactive(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Inactive Supplier')->setActive(false);

        $this->supplierRepository->method('find')->willReturn($supplier);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Supplier is inactive');

        $command = new CreateOrderCommand(
            1,
            null,
            'Title',
            '1000.00',
            'PLN',
            null,
            null,
            null,
            null,
            null
        );

        ($this->handler)($command);
    }

    public function testHandleThrowsExceptionWhenBudgetNotFound(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Active Supplier')->setActive(true);

        $this->supplierRepository->method('find')->willReturn($supplier);
        $this->budgetRepository->method('find')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Budget not found');

        $command = new CreateOrderCommand(
            1,
            999,
            'Title',
            '1000.00',
            'PLN',
            null,
            null,
            null,
            null,
            null
        );

        ($this->handler)($command);
    }

    public function testHandleThrowsExceptionWhenCurrencyMismatch(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Supplier')->setActive(true);

        $budget = new AcquisitionBudget();
        $budget->setName('Budget')
            ->setFiscalYear('2024')
            ->setAllocatedAmount('100000.00')
            ->setCurrency('EUR');

        $this->supplierRepository->method('find')->willReturn($supplier);
        $this->budgetRepository->method('find')->willReturn($budget);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Budget currency mismatch');

        $command = new CreateOrderCommand(
            1,
            1,
            'Title',
            '1000.00',
            'PLN', // Different from budget EUR
            null,
            null,
            null,
            null,
            null
        );

        ($this->handler)($command);
    }

    public function testHandleCreatesOrderWithoutBudget(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Supplier')->setActive(true);

        $this->supplierRepository->method('find')->willReturn($supplier);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CreateOrderCommand(
            1,
            null, // No budget
            'Order without budget',
            '2000.00',
            'PLN',
            null,
            null,
            null,
            null,
            null
        );

        $result = ($this->handler)($command);

        $this->assertInstanceOf(AcquisitionOrder::class, $result);
        $this->assertNull($result->getBudget());
    }
}
