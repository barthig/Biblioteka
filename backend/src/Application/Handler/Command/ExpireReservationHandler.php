<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Reservation\ExpireReservationCommand;
use App\Entity\BookCopy;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ExpireReservationHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReservationRepository $reservationRepository
    ) {
    }

    public function __invoke(ExpireReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->find($command->reservationId);
        if (!$reservation) {
            throw new \RuntimeException('Reservation not found');
        }

        // Only expire active reservations that have passed their expiry date
        if ($reservation->getStatus() !== Reservation::STATUS_ACTIVE) {
            throw new \RuntimeException('Cannot expire reservation with status: ' . $reservation->getStatus());
        }

        // Verify expiration date has passed
        $now = new \DateTimeImmutable();
        if ($reservation->getExpiresAt() > $now) {
            throw new \RuntimeException('Reservation has not expired yet');
        }

        // Mark as expired (different from cancelled)
        $reservation->expire();
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
