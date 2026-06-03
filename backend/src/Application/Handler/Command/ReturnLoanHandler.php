<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Loan\ReturnLoanCommand;
use App\Entity\BookCopy;
use App\Entity\Fine;
use App\Entity\Loan;
use App\Event\BookReturnedEvent;
use App\Exception\BusinessLogicException;
use App\Exception\NotFoundException;
use App\Message\ReservationReadyMessage;
use App\Repository\FineRepository;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Service\Book\BookService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler(bus: 'command.bus')]
class ReturnLoanHandler
{
    private const DAILY_OVERDUE_FINE = 0.50;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private BookService $bookService,
        private LoanRepository $loanRepository,
        private ReservationRepository $reservationRepository,
        private FineRepository $fineRepository,
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function __invoke(ReturnLoanCommand $command): Loan
    {
        $loan = $this->loanRepository->find($command->loanId);
        if (!$loan) {
            throw NotFoundException::forLoan($command->loanId);
        }

        // Authorization check happens in controller.
        $now = new \DateTimeImmutable();
        $reservationForNotification = null;

        $this->entityManager->beginTransaction();
        try {
            $this->entityManager->lock($loan, LockMode::PESSIMISTIC_WRITE);
            $this->entityManager->lock($loan->getBook(), LockMode::PESSIMISTIC_WRITE);

            $copy = $loan->getBookCopy();
            if ($copy) {
                $this->entityManager->lock($copy, LockMode::PESSIMISTIC_WRITE);
            }

            if ($loan->getReturnedAt() !== null) {
                throw BusinessLogicException::loanAlreadyReturned();
            }

            $daysOverdue = $this->calculateStartedOverdueDays($loan->getDueAt(), $now);
            if ($daysOverdue > 0) {
                $this->applyOverdueFine($loan, $daysOverdue);
            }

            $loan->setReturnedAt($now);

            $queue = $this->reservationRepository->findActiveByBook($loan->getBook());

            if ($copy && !empty($queue)) {
                $this->bookService->restore($loan->getBook(), $copy, false, false);

                $nextReservation = $queue[0];
                $this->entityManager->lock($nextReservation, LockMode::PESSIMISTIC_WRITE);
                $copy->setStatus(BookCopy::STATUS_RESERVED);
                $nextReservation->assignBookCopy($copy);
                $nextReservation->setExpiresAt((new \DateTimeImmutable())->modify('+2 days'));
                $reservationForNotification = $nextReservation;

                $loan->getBook()->recalculateInventoryCounters();
                $this->entityManager->persist($nextReservation);
            } else {
                $this->bookService->restore($loan->getBook(), $copy, true, false);
            }

            $this->entityManager->persist($loan);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            if ($e instanceof \App\Exception\AppException) {
                throw $e;
            }
            throw BusinessLogicException::operationFailed('ReturnLoan', $e->getMessage());
        }

        if ($reservationForNotification) {
            try {
                $expiresAt = $reservationForNotification->getExpiresAt();
                $this->bus->dispatch(new ReservationReadyMessage(
                    $reservationForNotification->getId(),
                    $reservationForNotification->getUser()->getId(),
                    $expiresAt->format(DATE_ATOM)
                ));
            } catch (\Exception $e) {
                $this->logger->error('Failed to dispatch ReservationReadyMessage', [
                    'reservationId' => $reservationForNotification->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            $this->eventDispatcher->dispatch(new BookReturnedEvent($loan));
        } catch (\Throwable $eventError) {
            $this->logger->error('BookReturnedEvent dispatch failed after return commit', [
                'loanId' => $loan->getId(),
                'error' => $eventError->getMessage(),
            ]);
        }

        return $loan;
    }

    private function calculateStartedOverdueDays(\DateTimeImmutable $dueAt, \DateTimeImmutable $returnedAt): int
    {
        $secondsLate = $returnedAt->getTimestamp() - $dueAt->getTimestamp();
        if ($secondsLate <= 0) {
            return 0;
        }

        return max(1, (int) ceil($secondsLate / 86400));
    }

    private function applyOverdueFine(Loan $loan, int $daysOverdue): void
    {
        $fineAmount = number_format($daysOverdue * self::DAILY_OVERDUE_FINE, 2, '.', '');
        $fine = $this->fineRepository->findActiveOverdueFine($loan);
        $created = false;

        if (!$fine) {
            $fine = new Fine();
            $fine->setLoan($loan);
            $created = true;
        }

        $fine->setAmount($fineAmount);
        $fine->setCurrency('PLN');
        $fine->setReason(sprintf('Zwrot po terminie (%d dni spóźnienia)', $daysOverdue));

        $this->entityManager->persist($fine);

        $this->logger->info($created ? 'Fine created for overdue loan return' : 'Fine updated for overdue loan return', [
            'loanId' => $loan->getId(),
            'daysOverdue' => $daysOverdue,
            'fineAmount' => $fineAmount,
        ]);
    }
}
