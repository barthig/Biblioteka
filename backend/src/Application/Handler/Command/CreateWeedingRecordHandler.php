<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Weeding\CreateWeedingRecordCommand;
use App\Entity\BookCopy;
use App\Entity\Loan;
use App\Entity\Reservation;
use App\Entity\WeedingRecord;
use App\Repository\BookCopyRepository;
use App\Repository\BookRepository;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Service\BookService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateWeedingRecordHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookRepository $bookRepository,
        private readonly BookCopyRepository $bookCopyRepository,
        private readonly LoanRepository $loanRepository,
        private readonly ReservationRepository $reservationRepository,
        private readonly UserRepository $userRepository,
        private readonly BookService $bookService
    ) {
    }

    public function __invoke(CreateWeedingRecordCommand $command): WeedingRecord
    {
        $book = $this->bookRepository->find($command->bookId);
        if (!$book) {
            throw new \RuntimeException('Book not found');
        }

        $copy = null;
        if ($command->copyId !== null) {
            $copy = $this->bookCopyRepository->find($command->copyId);
            if (!$copy || $copy->getBook()->getId() !== $book->getId()) {
                throw new \RuntimeException('Copy does not belong to the book');
            }
            
            if ($copy->getStatus() === BookCopy::STATUS_BORROWED) {
                throw new \RuntimeException('Copy is currently borrowed');
            }
            if ($copy->getStatus() === BookCopy::STATUS_RESERVED) {
                throw new \RuntimeException('Copy is reserved');
            }
            if ($copy->getStatus() === BookCopy::STATUS_WITHDRAWN) {
                throw new \RuntimeException('Copy already withdrawn');
            }

            if ($this->loanRepository->findActiveByInventoryCode($copy->getInventoryCode()) !== null) {
                throw new \RuntimeException('Copy has an active loan');
            }

            if ($this->reservationRepository->findActiveByCopy($copy) !== null) {
                throw new \RuntimeException('Copy has an active reservation');
            }
        }

        $record = (new WeedingRecord())
            ->setBook($book)
            ->setBookCopy($copy)
            ->setReason($command->reason);

        if ($command->action) {
            $record->setAction($command->action);
        }
        if ($command->conditionState) {
            $record->setConditionState($command->conditionState);
        }
        if ($command->notes) {
            $record->setNotes($command->notes);
        }
        if ($command->removedAt && strtotime($command->removedAt)) {
            $record->setRemovedAt(new \DateTimeImmutable($command->removedAt));
        }

        if ($command->userId) {
            $user = $this->userRepository->find($command->userId);
            if ($user) {
                $record->setProcessedBy($user);
            }
        }

        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();
        try {
            if ($copy) {
                $this->bookService->withdrawCopy($book, $copy, $command->conditionState, false);
            } else {
                $book->recalculateInventoryCounters();
                $this->entityManager->persist($book);
            }

            $this->entityManager->persist($record);
            $this->entityManager->flush();
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw new \RuntimeException('Błąd podczas tworzenia rekordu selekcji');
        }

        return $record;
    }
}
