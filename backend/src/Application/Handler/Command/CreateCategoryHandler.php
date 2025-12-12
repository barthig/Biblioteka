<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Category\CreateCategoryCommand;
use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateCategoryHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(CreateCategoryCommand $command): Category
    {
        $category = (new Category())->setName($command->name);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }
}
