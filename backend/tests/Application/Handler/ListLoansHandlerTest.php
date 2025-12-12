<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListLoansHandler;
use App\Application\Query\Loan\ListLoansQuery;
use App\Repository\LoanRepository;
use PHPUnit\Framework\TestCase;

class ListLoansHandlerTest extends TestCase
{
    private LoanRepository $loanRepository;
    private ListLoansHandler $handler;

    protected function setUp(): void
    {
        $this->loanRepository = $this->createMock(LoanRepository::class);
        $this->handler = new ListLoansHandler($this->loanRepository);
    }

    public function testListLoansSuccess(): void
    {
        $this->loanRepository->method('findBy')->willReturn([]);

        $query = new ListLoansQuery(page: 1, limit: 50);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
