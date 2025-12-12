<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\ListBookCopiesHandler;
use App\Application\Query\BookCopy\ListBookCopiesQuery;
use App\Repository\BookCopyRepository;
use PHPUnit\Framework\TestCase;

class ListBookCopiesHandlerTest extends TestCase
{
    private BookCopyRepository $bookCopyRepository;
    private ListBookCopiesHandler $handler;

    protected function setUp(): void
    {
        $this->bookCopyRepository = $this->createMock(BookCopyRepository::class);
        $this->handler = new ListBookCopiesHandler($this->bookCopyRepository);
    }

    public function testListBookCopiesSuccess(): void
    {
        $this->bookCopyRepository->method('findBy')->willReturn([]);

        $query = new ListBookCopiesQuery(bookId: 1);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
