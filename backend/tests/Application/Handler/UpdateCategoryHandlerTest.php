<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Category\UpdateCategoryCommand;
use App\Application\Handler\Command\UpdateCategoryHandler;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateCategoryHandlerTest extends TestCase
{
    private CategoryRepository $categoryRepository;
    private EntityManagerInterface $entityManager;
    private UpdateCategoryHandler $handler;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->handler = new UpdateCategoryHandler($this->entityManager, $this->categoryRepository);
    }

    public function testUpdateCategorySuccess(): void
    {
        $category = $this->createMock(Category::class);
        $category->expects($this->once())->method('setName')->with('Updated Category');
        
        $this->categoryRepository->method('find')->with(1)->willReturn($category);
        $this->entityManager->expects($this->once())->method('flush');

        $command = new UpdateCategoryCommand(categoryId: 1, name: 'Updated Category');
        $result = ($this->handler)($command);

        $this->assertSame($category, $result);
    }
}
