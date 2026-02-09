<?php
namespace App\Application\Handler\Command;

use App\Application\Command\BookInventory\DeleteBookCopyCommand;
use App\Entity\BookCopy;
use App\Exception\BusinessLogicException;
use App\Exception\NotFoundException;
use App\Repository\BookRepository;
use App\Repository\BookCopyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteBookCopyHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookRepository $bookRepository,
        private readonly BookCopyRepository $bookCopyRepository
    ) {
    }

    public function __invoke(DeleteBookCopyCommand $command): void
    {
        $book = $this->bookRepository->find($command->bookId);
        if (!$book) {
            throw NotFoundException::forBook($command->bookId);
        }

        $copy = $this->bookCopyRepository->find($command->copyId);
        if (!$copy || $copy->getBook()->getId() !== $book->getId()) {
            throw NotFoundException::forEntity('BookCopy', $command->copyId);
        }

        if ($copy->getStatus() === BookCopy::STATUS_BORROWED) {
            throw BusinessLogicException::invalidState('Cannot delete a borrowed copy');
        }

        if ($copy->getStatus() === BookCopy::STATUS_RESERVED) {
            throw BusinessLogicException::invalidState('Cannot delete a reserved copy');
        }

        $this->entityManager->remove($copy);
        $book->recalculateInventoryCounters();
        $this->entityManager->flush();
    }
}
