<?php
namespace App\Tests\Application\Handler;

use App\Application\Query\Category\ListCategoriesQuery;
use App\Application\Handler\Query\ListCategoriesHandler;
use App\Repository\CategoryRepository;
use PHPUnit\Framework\TestCase;

class ListCategoriesHandlerTest extends TestCase
{
    private CategoryRepository $categoryRepository;
    private ListCategoriesHandler $handler;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->handler = new ListCategoriesHandler($this->categoryRepository);
    }

    public function testListCategoriesSuccess(): void
    {
        $this->categoryRepository->method('findBy')->willReturn([]);

        $query = new ListCategoriesQuery(page: 1, limit: 50);
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
