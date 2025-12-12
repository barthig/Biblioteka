<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\BookInventory\DeleteBookCopyCommand;
use App\Application\Handler\Command\DeleteBookCopyHandler;
use App\Entity\BookCopy;
use App\Repository\BookCopyRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DeleteBookCopyHandlerTest extends TestCase
{
    private BookCopyRepository $bookCopyRepository;
    private BookRepository $bookRepository;
    private EntityManagerInterface $entityManager;
    private DeleteBookCopyHandler $handler;

    protected function setUp(): void
    {
        $this->bookCopyRepository = $this->createMock(BookCopyRepository::class);
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new DeleteBookCopyHandler($this->entityManager, $this->bookRepository, $this->bookCopyRepository);
    }

    public function testDeleteBookCopySuccess(): void
    {
        $book = $this->createMock(\App\Entity\Book::class);
        $bookCopy = $this->createMock(BookCopy::class);
        
        $this->bookRepository->method('find')->with(1)->willReturn($book);
        $this->bookCopyRepository->method('find')->with(1)->willReturn($bookCopy);
        $this->entityManager->expects($this->once())->method('remove')->with($bookCopy);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new DeleteBookCopyCommand(bookId: 1, copyId: 1);
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
