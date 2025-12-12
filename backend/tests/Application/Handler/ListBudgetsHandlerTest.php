<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListBudgetsHandler;
use App\Application\Query\Acquisition\ListBudgetsQuery;
use App\Repository\AcquisitionBudgetRepository;
use PHPUnit\Framework\TestCase;

class ListBudgetsHandlerTest extends TestCase
{
    private AcquisitionBudgetRepository $budgetRepository;
    private ListBudgetsHandler $handler;

    protected function setUp(): void
    {
        $this->budgetRepository = $this->createMock(AcquisitionBudgetRepository::class);
        $this->handler = new ListBudgetsHandler($this->budgetRepository);
    }

    public function testListBudgetsSuccess(): void
    {
        $this->budgetRepository->method('findBy')->willReturn([]);

        $query = new ListBudgetsQuery();
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
