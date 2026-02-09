<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Category\UpdateCategoryCommand;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateCategoryHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    public function __invoke(UpdateCategoryCommand $command): Category
    {
        $category = $this->categoryRepository->find($command->categoryId);
        
        if (!$category) {
            throw new NotFoundHttpException('Category not found');
        }

        if ($command->name !== null) {
            $category->setName($command->name);
        }

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }
}
