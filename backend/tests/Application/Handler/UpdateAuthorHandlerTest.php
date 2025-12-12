<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Author\UpdateAuthorCommand;
use App\Application\Handler\Command\UpdateAuthorHandler;
use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateAuthorHandlerTest extends TestCase
{
    private AuthorRepository $authorRepository;
    private EntityManagerInterface $entityManager;
    private UpdateAuthorHandler $handler;

    protected function setUp(): void
    {
        $this->authorRepository = $this->createMock(AuthorRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new UpdateAuthorHandler($this->entityManager, $this->authorRepository);
    }

    public function testUpdateAuthorSuccess(): void
    {
        $author = $this->createMock(Author::class);
        $author->expects($this->once())->method('setName')->with('Updated Name');
        
        $this->authorRepository->method('find')->with(1)->willReturn($author);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new UpdateAuthorCommand(authorId: 1, name: 'Updated Name');
        $result = ($this->handler)($command);

        $this->assertSame($author, $result);
    }
}
