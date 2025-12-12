<?php
namespace App\Tests\Application\Handler;

use App\Application\Query\Book\ListBooksQuery;
use App\Application\Handler\Query\ListBooksHandler;
use App\Repository\BookRepository;
use PHPUnit\Framework\TestCase;

class ListBooksHandlerTest extends TestCase
{
    private BookRepository $bookRepository;
    private ListBooksHandler $handler;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->handler = new ListBooksHandler($this->bookRepository);
    }

    public function testListBooksSuccess(): void
    {
        $this->bookRepository->method('findBy')->willReturn([]);

        $query = new ListBooksQuery(page: 1, limit: 20);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }

    public function testListBooksWithPagination(): void
    {
        $this->bookRepository->expects($this->once())
            ->method('findBy')
            ->with([], ['title' => 'ASC'], 10, 20)
            ->willReturn([]);

        $query = new ListBooksQuery(page: 3, limit: 10);
        ($this->handler)($query);
    }
}
