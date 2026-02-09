<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Book\DeleteBookCommand;
use App\Event\BookDeletedEvent;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
class DeleteBookHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookRepository $bookRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(DeleteBookCommand $command): void
    {
        $book = $this->bookRepository->find($command->bookId);
        
        if (!$book) {
            throw new NotFoundHttpException('Book not found');
        }

        $bookId = $book->getId();
        $bookTitle = $book->getTitle();

        $this->entityManager->remove($book);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new BookDeletedEvent($bookId, $bookTitle));
    }
}
