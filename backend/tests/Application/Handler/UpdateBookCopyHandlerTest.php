<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\BookInventory\UpdateBookCopyCommand;
use App\Application\Handler\Command\UpdateBookCopyHandler;
use App\Entity\BookCopy;
use App\Repository\BookCopyRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateBookCopyHandlerTest extends TestCase
{
    private BookCopyRepository $bookCopyRepository;
    private BookRepository $bookRepository;
    private EntityManagerInterface $entityManager;
    private UpdateBookCopyHandler $handler;

    protected function setUp(): void
    {
        $this->bookCopyRepository = $this->createMock(BookCopyRepository::class);
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new UpdateBookCopyHandler($this->entityManager, $this->bookRepository, $this->bookCopyRepository);
    }

    public function testUpdateBookCopySuccess(): void
    {
        $book = $this->createMock(\App\Entity\Book::class);
        $bookCopy = $this->createMock(BookCopy::class);
        // Handler converts status to uppercase
        $bookCopy->expects($this->once())->method('setStatus')->with('AVAILABLE');
        
        $this->bookRepository->method('find')->with(1)->willReturn($book);
        $this->bookCopyRepository->method('find')->with(1)->willReturn($bookCopy);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new UpdateBookCopyCommand(bookId: 1, copyId: 1, status: 'available');
        $result = ($this->handler)($command);

        $this->assertSame($bookCopy, $result);
    }
}
