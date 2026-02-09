<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Author\UpdateAuthorCommand;
use App\Entity\Author;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateAuthorHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthorRepository $authorRepository
    ) {
    }

    public function __invoke(UpdateAuthorCommand $command): Author
    {
        $author = $this->authorRepository->find($command->authorId);
        
        if (!$author) {
            throw new NotFoundHttpException('Author not found');
        }

        if ($command->name !== null) {
            $author->setName($command->name);
        }

        $this->entityManager->persist($author);
        $this->entityManager->flush();

        return $author;
    }
}
