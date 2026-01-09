<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Loan\ReturnLoanCommand;
use App\Entity\BookCopy;
use App\Entity\Fine;
use App\Entity\Loan;
use App\Event\BookReturnedEvent;
use App\Message\ReservationReadyMessage;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Service\BookService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
class ReturnLoanHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private BookService $bookService,
        private LoanRepository $loanRepository,
        private ReservationRepository $reservationRepository,
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function __invoke(ReturnLoanCommand $command): Loan
    {
        $loan = $this->loanRepository->find($command->loanId);
        if (!$loan) {
            throw new \RuntimeException('Loan not found');
        }

        // Authorization check happens in controller
        // Here we just execute the business logic

        if ($loan->getReturnedAt() !== null) {
            throw new \RuntimeException('Loan already returned');
        }

        // Check if loan is overdue and create fine
        $now = new \DateTimeImmutable();
        $dueDate = $loan->getDueAt();
        $isOverdue = $now > $dueDate;
        
        if ($isOverdue) {
            $interval = $now->diff($dueDate);
            $daysOverdue = $interval->days;
            
            if ($daysOverdue > 0) {
                // Create fine: 0.50 PLN per day
                $fineAmount = $daysOverdue * 0.50;
                
                $fine = new Fine();
                $fine->setLoan($loan);
                $fine->setAmount((string) $fineAmount);
                $fine->setCurrency('PLN');
                $fine->setReason("Zwrot po terminie ({$daysOverdue} dni spóźnienia)");
                
                $this->em->persist($fine);
                
                $this->logger->info('Fine created for overdue loan', [
                    'loanId' => $loan->getId(),
                    'daysOverdue' => $daysOverdue,
                    'fineAmount' => $fineAmount
                ]);
            }
        }

        $loan->setReturnedAt($now);

        // Check reservations waiting for this book
        $queue = $this->reservationRepository->findActiveByBook($loan->getBook());
        $copy = $loan->getBookCopy();
        $reservationForNotification = null;

        if ($copy && !empty($queue)) {
            $this->bookService->restore($loan->getBook(), $loan->getBookCopy(), false, false);
            
            $nextReservation = $queue[0];
            $copy->setStatus(BookCopy::STATUS_RESERVED);
            $nextReservation->assignBookCopy($copy);
            $nextReservation->setExpiresAt((new \DateTimeImmutable())->modify('+2 days'));
            $reservationForNotification = $nextReservation;

            $this->em->beginTransaction();
            try {
                $loan->getBook()->recalculateInventoryCounters();
                $this->em->persist($loan);
                $this->em->persist($nextReservation);
                // Fine was already persisted above
                $this->em->flush();
                $this->em->commit();
            } catch (\Exception $e) {
                $this->em->rollback();
                throw new \RuntimeException('Nie udało się zwrócić wypożyczenia: ' . $e->getMessage());
            }

            if ($reservationForNotification) {
                try {
                    $expiresAt = $reservationForNotification->getExpiresAt();
                    $expiresAtIso = $expiresAt->format(DATE_ATOM);
                    $this->bus->dispatch(new ReservationReadyMessage(
                        $reservationForNotification->getId(),
                        $reservationForNotification->getUser()->getId(),
                        $expiresAtIso
                    ));
                } catch (\Exception $e) {
                    $this->logger->error('Failed to dispatch ReservationReadyMessage', [
                        'reservationId' => $reservationForNotification->getId(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } else {
            $this->bookService->restore($loan->getBook(), $loan->getBookCopy());
            $this->em->persist($loan);
            // Fine was already persisted above
            $this->em->flush();
        }

        // Dispatch event
        $this->eventDispatcher->dispatch(new BookReturnedEvent($loan));

        return $loan;
    }
}
