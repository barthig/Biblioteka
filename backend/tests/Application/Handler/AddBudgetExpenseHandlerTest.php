<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Acquisition\AddBudgetExpenseCommand;
use App\Application\Handler\Command\AddBudgetExpenseHandler;
use App\Entity\AcquisitionBudget;
use App\Repository\AcquisitionBudgetRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AddBudgetExpenseHandlerTest extends TestCase
{
    private AcquisitionBudgetRepository $budgetRepository;
    private EntityManagerInterface $entityManager;
    private AddBudgetExpenseHandler $handler;

    protected function setUp(): void
    {
        $this->budgetRepository = $this->createMock(AcquisitionBudgetRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new AddBudgetExpenseHandler($this->entityManager, $this->budgetRepository);
    }

    public function testAddBudgetExpenseSuccess(): void
    {
        $budget = $this->createMock(AcquisitionBudget::class);
        $budget->method('getSpentAmount')->willReturn('500.00');
        $budget->method('getCurrency')->willReturn('PLN');
        $budget->expects($this->once())->method('registerExpense')->with('100.00');
        
        $this->budgetRepository->method('find')->with(1)->willReturn($budget);
        $this->entityManager->expects($this->exactly(2))->method('persist'); // expense + budget
        $this->entityManager->expects($this->once())->method('flush');

        $command = new AddBudgetExpenseCommand(
            budgetId: 1,
            amount: '100.0',
            description: 'Test expense',
            type: 'MISC',
            postedAt: null
        );
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
