<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Author\DeleteAuthorCommand;
use App\Application\Handler\Command\DeleteAuthorHandler;
use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DeleteAuthorHandlerTest extends TestCase
{
    private AuthorRepository $authorRepository;
    private EntityManagerInterface $entityManager;
    private DeleteAuthorHandler $handler;

    protected function setUp(): void
    {
        $this->authorRepository = $this->createMock(AuthorRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new DeleteAuthorHandler($this->authorRepository, $this->entityManager);
    }

    public function testDeleteAuthorSuccess(): void
    {
        $author = $this->createMock(Author::class);
        $this->authorRepository->method('find')->with(1)->willReturn($author);
        $this->entityManager->expects($this->once())->method('remove')->with($author);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new DeleteAuthorCommand(authorId: 1);
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
