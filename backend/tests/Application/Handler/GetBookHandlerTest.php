<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\GetBookHandler;
use App\Application\Query\Book\GetBookQuery;
use App\Entity\Book;
use App\Repository\BookRepository;
use App\Repository\RatingRepository;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class GetBookHandlerTest extends TestCase
{
    private BookRepository $bookRepository;
    private ManagerRegistry $doctrine;
    private RatingRepository $ratingRepository;
    private GetBookHandler $handler;

    protected function setUp(): void
    {
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->ratingRepository = $this->createMock(RatingRepository::class);
        $this->ratingRepository->method('getAverageRatingForBook')->willReturn(null);
        $this->ratingRepository->method('getRatingCountForBook')->willReturn(0);
        $this->handler = new GetBookHandler($this->bookRepository, $this->doctrine, $this->ratingRepository);
    }

    public function testGetBookSuccess(): void
    {
        $book = $this->createMock(Book::class);
        $book->method('getId')->willReturn(1);
        $book->expects($this->once())->method('setRatingCount')->with(0);
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
