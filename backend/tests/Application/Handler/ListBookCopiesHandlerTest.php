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
        $this->handler = new ListBookCopiesHandler($this->createMock(\App\Repository\BookRepository::class));
    }

    public function testListBookCopiesSuccess(): void
    {
        $book = $this->createMock(\App\Entity\Book::class);
        
        // BookRepository mock that returns the book
        $bookRepo = $this->createMock(\App\Repository\BookRepository::class);
        $bookRepo->method('find')->with(1)->willReturn($book);
        
        // Use the actual BookRepository passed to handler
        $handler = new ListBookCopiesHandler($bookRepo);

        $query = new \App\Application\Query\BookInventory\ListBookCopiesQuery(bookId: 1);
        $result = $handler($query);

        $this->assertIsArray($result);
    }
}
