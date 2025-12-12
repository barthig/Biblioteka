<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\GetBookHandler;
use App\Application\Query\Book\GetBookQuery;
use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class GetBookHandlerTest extends TestCase
{
    private BookRepository $bookRepository;
    private ManagerRegistry $doctrine;
    private GetBookHandler $handler;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->handler = new GetBookHandler($this->bookRepository, $this->doctrine);
    }

    public function testGetBookSuccess(): void
    {
        $book = $this->createMock(Book::class);
        $this->bookRepository->method('find')->with(1)->willReturn($book);

        $query = new GetBookQuery(bookId: 1);
        $result = ($this->handler)($query);

        $this->assertSame($book, $result);
    }

    public function testThrowsExceptionWhenBookNotFound(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->bookRepository->method('find')->with(999)->willReturn(null);

        $query = new GetBookQuery(bookId: 999);
        ($this->handler)($query);
    }
}
