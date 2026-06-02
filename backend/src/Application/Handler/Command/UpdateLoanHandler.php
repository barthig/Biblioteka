<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Loan\UpdateLoanCommand;
use App\Entity\BookCopy;
use App\Entity\Loan;
use App\Exception\BusinessLogicException;
use App\Exception\NotFoundException;
use App\Repository\BookCopyRepository;
use App\Repository\BookRepository;
use App\Repository\LoanRepository;
use App\Service\Book\BookService;
use Doctrine\DBAL\LockMode;
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

    public function __invoke(UpdateLoanCommand $command): Loan
    {
        $loan = $this->loanRepository->find($command->loanId);
        if (!$loan) {
            throw NotFoundException::forLoan($command->loanId);
        }

        $this->entityManager->beginTransaction();
        try {
            $this->entityManager->lock($loan, LockMode::PESSIMISTIC_WRITE);
            $this->entityManager->lock($loan->getBook(), LockMode::PESSIMISTIC_WRITE);
            if ($loan->getBookCopy()) {
                $this->entityManager->lock($loan->getBookCopy(), LockMode::PESSIMISTIC_WRITE);
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
                    $preferredCopy = $this->bookCopyRepository->find($command->bookCopyId, LockMode::PESSIMISTIC_WRITE);
                    if (!$preferredCopy) {
                        throw NotFoundException::forEntity('BookCopy', $command->bookCopyId);
                    }
                    $targetBook = $preferredCopy->getBook();
                }

                if ($command->bookId !== null) {
                    $targetBook = $this->bookRepository->find($command->bookId, LockMode::PESSIMISTIC_WRITE);
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

                $this->entityManager->lock($targetBook, LockMode::PESSIMISTIC_WRITE);

                $currentBook = $loan->getBook();
                $currentCopy = $loan->getBookCopy();

                if ($currentCopy) {
                    $this->bookService->restore($currentBook, $currentCopy, true, false);
                } else {
                    $currentBook->recalculateInventoryCounters();
                    $this->entityManager->persist($currentBook);
                }

                $newCopy = $this->bookService->borrow($targetBook, null, $preferredCopy, false, true);
                if (!$newCopy) {
                    throw BusinessLogicException::noCopiesAvailable();
                }

                $loan->setBook($targetBook);
                $loan->setBookCopy($newCopy);
                $this->entityManager->persist($newCopy);
            }

            $this->entityManager->persist($loan);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            if ($e instanceof \App\Exception\AppException) {
                throw $e;
            }
            throw BusinessLogicException::operationFailed('UpdateLoan', $e->getMessage());
        }

        return $loan;
    }
}
