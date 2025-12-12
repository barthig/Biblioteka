<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Loan\ReturnLoanCommand;
use App\Entity\BookCopy;
use App\Entity\Loan;
use App\Message\ReservationReadyMessage;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Service\BookService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ReturnLoanHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private BookService $bookService,
        private LoanRepository $loanRepository,
        private ReservationRepository $reservationRepository,
        private MessageBusInterface $bus,
        private LoggerInterface $logger
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

        $loan->setReturnedAt(new \DateTimeImmutable());
        $this->bookService->restore($loan->getBook(), $loan->getBookCopy());

        // Check reservations waiting for this book
        $queue = $this->reservationRepository->findActiveByBook($loan->getBook());
        $copy = $loan->getBookCopy();
        $reservationForNotification = null;

        if ($copy && !empty($queue)) {
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
                $this->em->flush();
                $this->em->commit();
            } catch (\Exception $e) {
                $this->em->rollback();
                throw new \RuntimeException('Nie udało się zwrócić wypożyczenia');
            }

            if ($reservationForNotification) {
                try {
                    $this->bus->dispatch(new ReservationReadyMessage(
                        $reservationForNotification->getId(),
                        $reservationForNotification->getUser()->getId(),
                        $reservationForNotification->getBook()->getId()
                    ));
                } catch (\Exception $e) {
                    $this->logger->error('Failed to dispatch ReservationReadyMessage', [
                        'reservationId' => $reservationForNotification->getId(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } else {
            $this->em->persist($loan);
            $this->em->flush();
        }

        return $loan;
    }
}
