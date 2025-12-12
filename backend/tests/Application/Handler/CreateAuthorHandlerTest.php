<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Author\CreateAuthorCommand;
use App\Application\Handler\Command\CreateAuthorHandler;
use App\Entity\Author;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateAuthorHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private CreateAuthorHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->handler = new CreateAuthorHandler($this->em);
    }

    public function testCreateAuthorSuccess(): void
    {
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Author::class));
        $this->em->expects($this->once())->method('flush');

        $command = new CreateAuthorCommand(name: 'J.K. Rowling');
        $result = ($this->handler)($command);

        $this->assertInstanceOf(Author::class, $result);
    }
}
