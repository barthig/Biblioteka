<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Category\CreateCategoryCommand;
use App\Application\Handler\Command\CreateCategoryHandler;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateCategoryHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private CreateCategoryHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new CreateCategoryHandler($this->entityManager);
    }

    public function testCreateCategorySuccess(): void
    {
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CreateCategoryCommand(name: 'Science Fiction');
        $result = ($this->handler)($command);

        $this->assertInstanceOf(Category::class, $result);
    }
}
