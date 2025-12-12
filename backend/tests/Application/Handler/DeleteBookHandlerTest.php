<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Book\DeleteBookCommand;
use App\Application\Handler\Command\DeleteBookHandler;
use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeleteBookHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private BookRepository $bookRepository;
    private DeleteBookHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->bookRepository = $this->createMock(BookRepository::class);

        $this->handler = new DeleteBookHandler(
            $this->em,
            $this->bookRepository
        );
    }

    public function testDeleteBookSuccess(): void
    {
        $book = $this->createMock(Book::class);

        $this->bookRepository->method('find')->with(1)->willReturn($book);

        $this->em->expects($this->once())->method('remove')->with($book);
        $this->em->expects($this->once())->method('flush');

        $command = new DeleteBookCommand(bookId: 1);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenBookNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Book not found');

        $this->bookRepository->method('find')->with(999)->willReturn(null);

        $command = new DeleteBookCommand(bookId: 999);
        ($this->handler)($command);
    }
}
