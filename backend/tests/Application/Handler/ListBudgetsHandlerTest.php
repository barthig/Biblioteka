<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListBudgetsHandler;
use App\Application\Query\Budget\ListBudgetsQuery;
use App\Repository\BudgetRepository;
use PHPUnit\Framework\TestCase;

class ListBudgetsHandlerTest extends TestCase
{
    private BudgetRepository $budgetRepository;
    private ListBudgetsHandler $handler;

    protected function setUp(): void
    {
        $this->budgetRepository = $this->createMock(BudgetRepository::class);
        $this->handler = new ListBudgetsHandler($this->budgetRepository);
    }

    public function testListBudgetsSuccess(): void
    {
        $this->budgetRepository->method('findBy')->willReturn([]);

        $query = new ListBudgetsQuery(page: 1, limit: 50);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
