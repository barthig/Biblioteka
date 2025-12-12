<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\BookCopy\DeleteBookCopyCommand;
use App\Application\Handler\Command\DeleteBookCopyHandler;
use App\Entity\BookCopy;
use App\Repository\BookCopyRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DeleteBookCopyHandlerTest extends TestCase
{
    private BookCopyRepository $bookCopyRepository;
    private EntityManagerInterface $entityManager;
    private DeleteBookCopyHandler $handler;

    protected function setUp(): void
    {
        $this->bookCopyRepository = $this->createMock(BookCopyRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new DeleteBookCopyHandler($this->bookCopyRepository, $this->entityManager);
    }

    public function testDeleteBookCopySuccess(): void
    {
        $bookCopy = $this->createMock(BookCopy::class);
        $this->bookCopyRepository->method('find')->with(1)->willReturn($bookCopy);
        $this->entityManager->expects($this->once())->method('remove')->with($bookCopy);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new DeleteBookCopyCommand(copyId: 1);
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
