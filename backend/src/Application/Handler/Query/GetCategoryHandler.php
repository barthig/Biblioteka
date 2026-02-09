<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Category\GetCategoryQuery;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetCategoryHandler
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    public function __invoke(GetCategoryQuery $query): Category
    {
        $category = $this->categoryRepository->find($query->categoryId);
        
        if (!$category) {
            throw new NotFoundHttpException('Category not found');
        }

        return $category;
    }
}
