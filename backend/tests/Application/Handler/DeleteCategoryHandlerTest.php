<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Category\DeleteCategoryCommand;
use App\Application\Handler\Command\DeleteCategoryHandler;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DeleteCategoryHandlerTest extends TestCase
{
    private CategoryRepository $categoryRepository;
    private EntityManagerInterface $entityManager;
    private DeleteCategoryHandler $handler;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new DeleteCategoryHandler($this->entityManager, $this->categoryRepository);
    }

    public function testDeleteCategorySuccess(): void
    {
        $category = $this->createMock(Category::class);
        $this->categoryRepository->method('find')->with(1)->willReturn($category);
        $this->entityManager->expects($this->once())->method('remove')->with($category);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new DeleteCategoryCommand(categoryId: 1);
        ($this->handler)($command);

        $this->assertTrue(true);
    }
}
