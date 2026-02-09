<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Loan\UpdateLoanCommand;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Exception\BusinessLogicException;
use App\Exception\NotFoundException;
use App\Repository\BookCopyRepository;
use App\Repository\BookRepository;
use App\Repository\LoanRepository;
use App\Service\Book\BookService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
class UpdateLoanHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoanRepository $loanRepository,
        private readonly BookRepository $bookRepository,
        private readonly BookCopyRepository $bookCopyRepository,
        private readonly BookService $bookService
    ) {
    }

    public function __invoke(UpdateLoanCommand $command)
    {
        $loan = $this->loanRepository->find($command->loanId);
        if (!$loan) {
            throw NotFoundException::forLoan($command->loanId);
        }

        if ($command->dueAt !== null) {
            $dueAt = new \DateTimeImmutable($command->dueAt);
            $loan->setDueAt($dueAt);
        }

        if ($command->status === 'active' && $loan->getReturnedAt() !== null) {
            $loan->setReturnedAt(null);
            $copy = $loan->getBookCopy();
            if ($copy) {
                $copy->setStatus(BookCopy::STATUS_BORROWED);
                $loan->getBook()->recalculateInventoryCounters();
                $this->entityManager->persist($copy);
            }
        }

        $bookChangeRequested = $command->bookId !== null || $command->bookCopyId !== null;
        if ($bookChangeRequested) {
            if ($loan->getReturnedAt() !== null) {
                throw BusinessLogicException::invalidState('Cannot change book for returned loan');
            }

            $targetBook = null;
            $preferredCopy = null;

            if ($command->bookCopyId !== null) {
                $preferredCopy = $this->bookCopyRepository->find($command->bookCopyId);
                if (!$preferredCopy) {
                    throw NotFoundException::forEntity('BookCopy', $command->bookCopyId);
                }
                $targetBook = $preferredCopy->getBook();
            }

            if ($command->bookId !== null) {
                $targetBook = $this->bookRepository->find($command->bookId);
                if (!$targetBook) {
                    throw NotFoundException::forBook($command->bookId);
                }
            }

            if ($preferredCopy && $targetBook && $preferredCopy->getBook()->getId() !== $targetBook->getId()) {
                throw BusinessLogicException::invalidState('Book copy does not belong to the selected book');
            }

            if (!$targetBook) {
                throw NotFoundException::forBook($command->bookId);
            }

            $currentBook = $loan->getBook();
            $currentCopy = $loan->getBookCopy();

            if ($currentCopy) {
                $this->bookService->restore($currentBook, $currentCopy, true, false);
            } else {
                $currentBook->recalculateInventoryCounters();
                $this->entityManager->persist($currentBook);
            }

            $newCopy = $this->bookService->borrow($targetBook, null, $preferredCopy, false);
            if (!$newCopy) {
                throw BusinessLogicException::noCopiesAvailable();
            }

            $loan->setBook($targetBook);
            $loan->setBookCopy($newCopy);
            $this->entityManager->persist($newCopy);
        }

        $this->entityManager->persist($loan);
        $this->entityManager->flush();

        return $loan;
    }
}
