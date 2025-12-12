<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Book\DeleteBookCommand;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteBookHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookRepository $bookRepository
    ) {
    }

    public function __invoke(DeleteBookCommand $command): void
    {
        $book = $this->bookRepository->find($command->bookId);
        
        if (!$book) {
            throw new NotFoundHttpException('Book not found');
        }

        $this->entityManager->remove($book);
        $this->entityManager->flush();
    }
}
