<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\BookInventory\CreateBookCopyCommand;
use App\Application\Handler\Command\CreateBookCopyHandler;
use App\Entity\BookCopy;
use App\Repository\BookCopyRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateBookCopyHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private BookRepository $bookRepository;
    private BookCopyRepository $bookCopyRepository;
    private CreateBookCopyHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->bookCopyRepository = $this->createMock(BookCopyRepository::class);
        $this->handler = new CreateBookCopyHandler($this->entityManager, $this->bookRepository, $this->bookCopyRepository);
    }

    public function testCreateBookCopySuccess(): void
    {
        $book = $this->createMock(\App\Entity\Book::class);
        
        $this->bookRepository->method('find')->with(1)->willReturn($book);
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CreateBookCopyCommand(bookId: 1);

        $result = ($this->handler)($command);

        $this->assertInstanceOf(BookCopy::class, $result);
    }
}
