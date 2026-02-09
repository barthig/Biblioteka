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
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Service\Book\BookService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler(bus: 'command.bus')]
class ReturnLoanHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
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
            throw NotFoundException::forLoan($command->loanId);
        }

        // Authorization check happens in controller
        // Here we just execute the business logic

        if ($loan->getReturnedAt() !== null) {
            throw BusinessLogicException::loanAlreadyReturned();
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
                
                $this->entityManager->persist($fine);
                
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

            $this->entityManager->beginTransaction();
            try {
                $loan->getBook()->recalculateInventoryCounters();
                $this->entityManager->persist($loan);
                $this->entityManager->persist($nextReservation);
                // Fine was already persisted above
                $this->entityManager->flush();
                $this->entityManager->commit();
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                throw BusinessLogicException::operationFailed('ReturnLoan', $e->getMessage());
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
            $this->entityManager->persist($loan);
            // Fine was already persisted above
            $this->entityManager->flush();
        }

        // Dispatch event
        $this->eventDispatcher->dispatch(new BookReturnedEvent($loan));

        return $loan;
    }
}
