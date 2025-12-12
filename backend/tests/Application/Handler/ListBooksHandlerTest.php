<?php
namespace App\Tests\Application\Handler;

use App\Application\Query\Book\ListBooksQuery;
use App\Application\Handler\Query\ListBooksHandler;
use App\Repository\BookRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class ListBooksHandlerTest extends TestCase
{
    private BookRepository $bookRepository;
    private ManagerRegistry $doctrine;
    private ListBooksHandler $handler;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->handler = new ListBooksHandler($this->bookRepository, $this->doctrine);
    }

    public function testListBooksSuccess(): void
    {
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $doctrineQuery = $this->createMock(\Doctrine\ORM\Query::class);
        
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('addSelect')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('getQuery')->willReturn($doctrineQuery);
        $doctrineQuery->method('getResult')->willReturn([]);

        $this->bookRepository->method('createQueryBuilder')->willReturn($qb);

        $query = new ListBooksQuery(page: 1, limit: 20);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }

    public function testListBooksWithPagination(): void
    {
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $doctrineQuery = $this->createMock(\Doctrine\ORM\Query::class);
        
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('addSelect')->willReturnSelf();
        $qb->method('setFirstResult')->with(20)->willReturnSelf();
        $qb->method('setMaxResults')->with(10)->willReturnSelf();
        $qb->method('getQuery')->willReturn($doctrineQuery);
        $doctrineQuery->method('getResult')->willReturn([]);

        $this->bookRepository->method('createQueryBuilder')->willReturn($qb);

        $query = new ListBooksQuery(page: 3, limit: 10);
        $result = ($this->handler)($query);
        
        $this->assertIsArray($result);
    }
}
