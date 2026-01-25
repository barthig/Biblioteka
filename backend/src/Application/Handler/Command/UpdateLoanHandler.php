<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Loan\UpdateLoanCommand;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Repository\BookCopyRepository;
use App\Repository\BookRepository;
use App\Repository\LoanRepository;
use App\Service\Book\BookService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateLoanHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
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
            throw new NotFoundHttpException('Loan not found');
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
                $this->em->persist($copy);
            }
        }

        $bookChangeRequested = $command->bookId !== null || $command->bookCopyId !== null;
        if ($bookChangeRequested) {
            if ($loan->getReturnedAt() !== null) {
                throw new \RuntimeException('Cannot change book for returned loan');
            }

            $targetBook = null;
            $preferredCopy = null;

            if ($command->bookCopyId !== null) {
                $preferredCopy = $this->bookCopyRepository->find($command->bookCopyId);
                if (!$preferredCopy) {
                    throw new \RuntimeException('Egzemplarz nie znaleziony');
                }
                $targetBook = $preferredCopy->getBook();
            }

            if ($command->bookId !== null) {
                $targetBook = $this->bookRepository->find($command->bookId);
                if (!$targetBook) {
                    throw new \RuntimeException('Book not found');
                }
            }

            if ($preferredCopy && $targetBook && $preferredCopy->getBook()->getId() !== $targetBook->getId()) {
                throw new \RuntimeException('Egzemplarz nie pasuje do wybranej książki');
            }

            if (!$targetBook) {
                throw new \RuntimeException('Book not found');
            }

            $currentBook = $loan->getBook();
            $currentCopy = $loan->getBookCopy();

            if ($currentCopy) {
                $this->bookService->restore($currentBook, $currentCopy, true, false);
            } else {
                $currentBook->recalculateInventoryCounters();
                $this->em->persist($currentBook);
            }

            $newCopy = $this->bookService->borrow($targetBook, null, $preferredCopy, false);
            if (!$newCopy) {
                throw new \RuntimeException('Brak dostępnych egzemplarzy');
            }

            $loan->setBook($targetBook);
            $loan->setBookCopy($newCopy);
            $this->em->persist($newCopy);
        }

        $this->em->persist($loan);
        $this->em->flush();

        return $loan;
    }
}
