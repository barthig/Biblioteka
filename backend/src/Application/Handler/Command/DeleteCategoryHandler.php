<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Category\DeleteCategoryCommand;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class DeleteCategoryHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryRepository $categoryRepository
    ) {
    }

    public function __invoke(DeleteCategoryCommand $command): void
    {
        $category = $this->categoryRepository->find($command->categoryId);
        
        if (!$category) {
            throw new NotFoundHttpException('Category not found');
        }

        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }
}
