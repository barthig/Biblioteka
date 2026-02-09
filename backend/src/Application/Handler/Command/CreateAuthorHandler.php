<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Author\CreateAuthorCommand;
use App\Entity\Author;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateAuthorHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(CreateAuthorCommand $command): Author
    {
        $author = (new Author())->setName($command->name);

        $this->entityManager->persist($author);
        $this->entityManager->flush();

        return $author;
    }
}
