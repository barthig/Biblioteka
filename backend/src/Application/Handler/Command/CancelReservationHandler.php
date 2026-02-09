<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Reservation\CancelReservationCommand;
use App\Entity\BookCopy;
use App\Entity\Reservation;
use App\Exception\AuthorizationException;
use App\Exception\BusinessLogicException;
use App\Exception\NotFoundException;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CancelReservationHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReservationRepository $reservationRepository
    ) {
    }

    public function __invoke(CancelReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->find($command->reservationId);
        if (!$reservation) {
            throw NotFoundException::forReservation($command->reservationId);
        }

        // Authorization check: non-librarians can only cancel their own reservations
        if (!$command->isLibrarian && $reservation->getUser()->getId() !== $command->userId) {
            throw AuthorizationException::notOwner();
        }
        
        // Cannot cancel fulfilled reservations
        if ($reservation->getStatus() === Reservation::STATUS_FULFILLED) {
            throw BusinessLogicException::cannotCancelReservation('reservation is already fulfilled');
        }

        // Cannot cancel already cancelled reservations
        if ($reservation->getStatus() === Reservation::STATUS_CANCELLED) {
            throw BusinessLogicException::cannotCancelReservation('reservation is already cancelled');
        }

        // Expired reservations should use expire() method, not cancel()
        if ($reservation->getStatus() === Reservation::STATUS_EXPIRED) {
            throw BusinessLogicException::cannotCancelReservation('reservation has already expired');
        }

        // Can cancel active or prepared reservations
        if (!in_array($reservation->getStatus(), [Reservation::STATUS_ACTIVE, Reservation::STATUS_PREPARED], true)) {
            throw BusinessLogicException::cannotCancelReservation('invalid status: ' . $reservation->getStatus());
        }

        $reservation->cancel();
        $copy = $reservation->getBookCopy();
        
        if ($copy) {
            // Issue #10: Validate copy status before releasing
            if ($copy->getStatus() === BookCopy::STATUS_BORROWED) {
                throw BusinessLogicException::invalidState('Cannot release copy that is currently loaned');
            }
            
            $copy->setStatus(BookCopy::STATUS_AVAILABLE);
            $reservation->clearBookCopy();
            $reservation->getBook()->recalculateInventoryCounters();
            $this->entityManager->persist($copy);
            $this->entityManager->persist($reservation->getBook());
        }

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();
    }
}
