<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Author\DeleteAuthorCommand;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteAuthorHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AuthorRepository $authorRepository
    ) {
    }

    public function __invoke(DeleteAuthorCommand $command): void
    {
        $author = $this->authorRepository->find($command->authorId);
        
        if (!$author) {
            throw new NotFoundHttpException('Author not found');
        }

        $this->entityManager->remove($author);
        $this->entityManager->flush();
    }
}
