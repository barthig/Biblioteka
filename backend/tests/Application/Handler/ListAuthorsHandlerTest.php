<?php
namespace App\Tests\Application\Handler;

use App\Application\Query\Author\ListAuthorsQuery;
use App\Application\Handler\Query\ListAuthorsHandler;
use App\Repository\AuthorRepository;
use PHPUnit\Framework\TestCase;

class ListAuthorsHandlerTest extends TestCase
{
    private AuthorRepository $authorRepository;
    private ListAuthorsHandler $handler;

    protected function setUp(): void
    {
        $this->authorRepository = $this->createMock(AuthorRepository::class);
        $this->handler = new ListAuthorsHandler($this->authorRepository);
    }

    public function testListAuthorsSuccess(): void
    {
        $this->authorRepository->method('findBy')->willReturn([]);

        $query = new ListAuthorsQuery(page: 1, limit: 20);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }

    public function testListAuthorsWithSearch(): void
    {
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([]);

        $this->authorRepository->method('createQueryBuilder')->willReturn($qb);

        $listQuery = new ListAuthorsQuery(page: 1, limit: 20, search: 'Rowling');
        $result = ($this->handler)($listQuery);

        $this->assertIsArray($result);
    }
}
