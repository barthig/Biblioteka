<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\BookCopy\CreateBookCopyCommand;
use App\Application\Handler\Command\CreateBookCopyHandler;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateBookCopyHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private BookRepository $bookRepository;
    private CreateBookCopyHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->handler = new CreateBookCopyHandler($this->entityManager, $this->bookRepository);
    }

    public function testCreateBookCopySuccess(): void
    {
        $book = $this->createMock(Book::class);
        
        $this->bookRepository->method('find')->with(1)->willReturn($book);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CreateBookCopyCommand(bookId: 1, barcode: 'BC123');

        $result = ($this->handler)($command);

        $this->assertInstanceOf(BookCopy::class, $result);
    }
}
