<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Reservation\FulfillReservationCommand;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class FulfillReservationHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReservationRepository $reservationRepository
    ) {
    }

    public function __invoke(FulfillReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->find($command->reservationId);
        if (!$reservation) {
            throw new \RuntimeException('Reservation not found');
        }

        if ($reservation->getStatus() !== Reservation::STATUS_ACTIVE) {
            throw new \RuntimeException('Reservation is not active');
        }

        if (!$reservation->getBookCopy()) {
            throw new \RuntimeException('No book copy assigned to reservation');
        }

        // Mark reservation as fulfilled - do NOT release the book copy
        // The copy is now in LOANED status (set by CreateLoanHandler)
        $reservation->markFulfilled();
        
        // Book counters will be recalculated when loan is created
        
        $this->em->persist($reservation);
        $this->em->flush();
    }
}
