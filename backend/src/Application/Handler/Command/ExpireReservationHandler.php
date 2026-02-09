<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Reservation\ExpireReservationCommand;
use App\Entity\BookCopy;
use App\Entity\Reservation;
use App\Event\ReservationExpiredEvent;
use App\Exception\BusinessLogicException;
use App\Exception\NotFoundException;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler(bus: 'command.bus')]
class ExpireReservationHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReservationRepository $reservationRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(ExpireReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->find($command->reservationId);
        if (!$reservation) {
            throw NotFoundException::forReservation($command->reservationId);
        }

        // Only expire active or prepared reservations that have passed their expiry date
        if (!in_array($reservation->getStatus(), [Reservation::STATUS_ACTIVE, Reservation::STATUS_PREPARED], true)) {
            throw BusinessLogicException::invalidState('Cannot expire reservation with status: ' . $reservation->getStatus());
        }

        // Verify expiration date has passed
        $now = new \DateTimeImmutable();
        if ($reservation->getExpiresAt() > $now) {
            throw BusinessLogicException::invalidState('Reservation has not expired yet');
        }

        // Mark as expired (different from cancelled)
        $reservation->expire();
        $copy = $reservation->getBookCopy();
        
        // Issue #12: expire() must release copy and update counters
        if ($copy) {
            $copy->setStatus(BookCopy::STATUS_AVAILABLE);
            $reservation->clearBookCopy();
            $reservation->getBook()->recalculateInventoryCounters();
            $this->entityManager->persist($copy);
            $this->entityManager->persist($reservation->getBook());
        }

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new ReservationExpiredEvent($reservation));
    }
}
