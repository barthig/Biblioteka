<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\GetCategoryHandler;
use App\Application\Query\Category\GetCategoryQuery;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use PHPUnit\Framework\TestCase;

class GetCategoryHandlerTest extends TestCase
{
    private CategoryRepository $categoryRepository;
    private GetCategoryHandler $handler;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->handler = new GetCategoryHandler($this->categoryRepository);
    }

    public function testGetCategorySuccess(): void
    {
        $category = $this->createMock(Category::class);
        $this->categoryRepository->method('find')->with(1)->willReturn($category);

        $query = new GetCategoryQuery(categoryId: 1);
        $result = ($this->handler)($query);

        $this->assertSame($category, $result);
    }
}
