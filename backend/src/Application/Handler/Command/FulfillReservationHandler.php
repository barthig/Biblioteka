<?php
declare(strict_types=1);
namespace App\Application\Handler\Command;

use App\Application\Command\Reservation\FulfillReservationCommand;
use App\Entity\Reservation;
use App\Event\ReservationFulfilledEvent;
use App\Exception\BusinessLogicException;
use App\Exception\NotFoundException;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler(bus: 'command.bus')]
class FulfillReservationHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReservationRepository $reservationRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(FulfillReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->find($command->reservationId);
        if (!$reservation) {
            throw NotFoundException::forReservation($command->reservationId);
        }

        if (!in_array($reservation->getStatus(), [Reservation::STATUS_ACTIVE, Reservation::STATUS_PREPARED], true)) {
            throw BusinessLogicException::invalidState('Reservation must be active or prepared to fulfill');
        }

        if (!$reservation->getBookCopy()) {
            throw BusinessLogicException::invalidState('No book copy assigned to reservation');
        }

        // Mark reservation as fulfilled - do NOT release the book copy
        // The copy is now in LOANED status (set by CreateLoanHandler)
        $reservation->markFulfilled();
        
        // Book counters will be recalculated when loan is created
        
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new ReservationFulfilledEvent($reservation));
    }
}
