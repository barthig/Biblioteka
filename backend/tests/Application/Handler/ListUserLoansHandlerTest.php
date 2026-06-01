<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListUserLoansHandler;
use App\Application\Query\Loan\ListUserLoansQuery;
use App\Repository\LoanRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
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
        $listQuery = $this->createMock(AbstractQuery::class);
        $listQuery->method('getResult')->willReturn([]);

        $countQuery = $this->createMock(AbstractQuery::class);
        $countQuery->method('getSingleScalarResult')->willReturn(0);

        $listQb = $this->createMock(QueryBuilder::class);
        $listQb->method('leftJoin')->willReturnSelf();
        $listQb->method('addSelect')->willReturnSelf();
        $listQb->method('where')->willReturnSelf();
        $listQb->method('setParameter')->willReturnSelf();
        $listQb->method('orderBy')->willReturnSelf();
        $listQb->method('setMaxResults')->willReturnSelf();
        $listQb->method('setFirstResult')->willReturnSelf();
        $listQb->method('getQuery')->willReturn($listQuery);

        $countQb = $this->createMock(QueryBuilder::class);
        $countQb->method('select')->willReturnSelf();
        $countQb->method('where')->willReturnSelf();
        $countQb->method('setParameter')->willReturnSelf();
        $countQb->method('getQuery')->willReturn($countQuery);

        $this->loanRepository->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->with('l')
            ->willReturnOnConsecutiveCalls($listQb, $countQb);

        $query = new ListUserLoansQuery(userId: 1);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
