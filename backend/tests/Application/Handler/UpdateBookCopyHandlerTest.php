<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\BookCopy\UpdateBookCopyCommand;
use App\Application\Handler\Command\UpdateBookCopyHandler;
use App\Entity\BookCopy;
use App\Repository\BookCopyRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateBookCopyHandlerTest extends TestCase
{
    private BookCopyRepository $bookCopyRepository;
    private EntityManagerInterface $entityManager;
    private UpdateBookCopyHandler $handler;

    protected function setUp(): void
    {
        $this->bookCopyRepository = $this->createMock(BookCopyRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new UpdateBookCopyHandler($this->bookCopyRepository, $this->entityManager);
    }

    public function testUpdateBookCopySuccess(): void
    {
        $bookCopy = $this->createMock(BookCopy::class);
        $bookCopy->expects($this->once())->method('setStatus')->with('available');
        
        $this->bookCopyRepository->method('find')->with(1)->willReturn($bookCopy);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new UpdateBookCopyCommand(copyId: 1, status: 'available');
        $result = ($this->handler)($command);

        $this->assertSame($bookCopy, $result);
    }
}
