<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Budget\AddBudgetExpenseCommand;
use App\Application\Handler\Command\AddBudgetExpenseHandler;
use App\Entity\Budget;
use App\Repository\BudgetRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class AddBudgetExpenseHandlerTest extends TestCase
{
    private BudgetRepository $budgetRepository;
    private EntityManagerInterface $entityManager;
    private AddBudgetExpenseHandler $handler;

    protected function setUp(): void
    {
        $this->budgetRepository = $this->createMock(BudgetRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new AddBudgetExpenseHandler($this->budgetRepository, $this->entityManager);
    }

    public function testAddBudgetExpenseSuccess(): void
    {
        $budget = $this->createMock(Budget::class);
        $budget->expects($this->once())->method('addExpense')->with(100.0);
        
        $this->budgetRepository->method('find')->with(1)->willReturn($budget);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new AddBudgetExpenseCommand(budgetId: 1, amount: 100.0, description: 'Test expense');
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
