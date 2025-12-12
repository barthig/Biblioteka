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

        // Authorization check (happens in controller)
        
        if ($reservation->getStatus() === Reservation::STATUS_FULFILLED) {
            throw new \RuntimeException('Reservation already fulfilled');
        }

        $reservation->cancel();
        $copy = $reservation->getBookCopy();
        
        if ($copy) {
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
