<?php
namespace App\Tests\Application\Handler;

use App\Application\Query\Loan\GetLoanQuery;
use App\Application\Handler\Query\GetLoanHandler;
use App\Entity\Loan;
use App\Repository\LoanRepository;
use PHPUnit\Framework\TestCase;

class GetLoanHandlerTest extends TestCase
{
    private LoanRepository $loanRepository;
    private GetLoanHandler $handler;

    protected function setUp(): void
    {
        $this->loanRepository = $this->createMock(LoanRepository::class);
        $this->handler = new GetLoanHandler($this->loanRepository);
    }

    public function testGetLoanSuccess(): void
    {
        $loan = $this->createMock(Loan::class);
        $this->loanRepository->method('find')->with(1)->willReturn($loan);

        $query = new GetLoanQuery(loanId: 1);
        $result = ($this->handler)($query);

        $this->assertSame($loan, $result);
    }

    public function testThrowsExceptionWhenLoanNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Loan not found');

        $this->loanRepository->method('find')->with(999)->willReturn(null);

        $query = new GetLoanQuery(loanId: 999);
        ($this->handler)($query);
    }
}
