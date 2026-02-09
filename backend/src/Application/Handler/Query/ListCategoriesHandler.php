<?php
declare(strict_types=1);
namespace App\Application\Handler\Query;

use App\Application\Query\Category\ListCategoriesQuery;
use App\Repository\CategoryRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListCategoriesHandler
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    public function __invoke(ListCategoriesQuery $query): array
    {
        $offset = ($query->page - 1) * $query->limit;
        return $this->categoryRepository->findBy([], ['name' => 'ASC'], $query->limit, $offset);
    }
}
