<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListUserLoansHandler;
use App\Application\Query\Loan\ListUserLoansQuery;
use App\Repository\LoanRepository;
use PHPUnit\Framework\TestCase;

class ListUserLoansHandlerTest extends TestCase
{
    private LoanRepository $loanRepository;
    private ListUserLoansHandler $handler;

    protected function setUp(): void
    {
        $this->loanRepository = $this->createMock(LoanRepository::class);
        $this->handler = new ListUserLoansHandler($this->loanRepository);
    }

    public function testListUserLoansSuccess(): void
    {
        $this->loanRepository->method('findBy')->willReturn([]);

        $query = new ListUserLoansQuery(userId: 1);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
