<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Reservation\CancelReservationCommand;
use App\Entity\BookCopy;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CancelReservationHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReservationRepository $reservationRepository
    ) {
    }

    public function __invoke(CancelReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->find($command->reservationId);
        if (!$reservation) {
            throw new \RuntimeException('Reservation not found');
        }

        // Authorization check: non-librarians can only cancel their own reservations
        if (!$command->isLibrarian && $reservation->getUser()->getId() !== $command->userId) {
            throw new \RuntimeException('Forbidden');
        }
        
        // Cannot cancel fulfilled reservations
        if ($reservation->getStatus() === Reservation::STATUS_FULFILLED) {
            throw new \RuntimeException('Reservation already fulfilled');
        }

        // Cannot cancel already cancelled reservations
        if ($reservation->getStatus() === Reservation::STATUS_CANCELLED) {
            throw new \RuntimeException('Reservation already cancelled');
        }

        // Expired reservations should use expire() method, not cancel()
        if ($reservation->getStatus() === Reservation::STATUS_EXPIRED) {
            throw new \RuntimeException('Reservation already expired');
        }

        // Only cancel active reservations
        if ($reservation->getStatus() !== Reservation::STATUS_ACTIVE) {
            throw new \RuntimeException('Cannot cancel reservation with status: ' . $reservation->getStatus());
        }

        $reservation->cancel();
        $copy = $reservation->getBookCopy();
        
        if ($copy) {
            // Issue #10: Validate copy status before releasing
            if ($copy->getStatus() === BookCopy::STATUS_BORROWED) {
                throw new \RuntimeException('Cannot release copy that is currently loaned');
            }
            
            $copy->setStatus(BookCopy::STATUS_AVAILABLE);
            $reservation->clearBookCopy();
            $reservation->getBook()->recalculateInventoryCounters();
            $this->em->persist($copy);
            $this->em->persist($reservation->getBook());
        }

        $this->em->persist($reservation);
        $this->em->flush();
    }
}
