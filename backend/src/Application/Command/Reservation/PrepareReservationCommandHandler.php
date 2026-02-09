<?php

declare(strict_types=1);

namespace App\Application\Command\Reservation;

use App\Entity\Reservation;
use App\Exception\BusinessLogicException;
use App\Exception\NotFoundException;
use App\Repository\ReservationRepository;
use App\Service\User\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PrepareReservationCommandHandler
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
    ) {
    }

    public function __invoke(PrepareReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->find($command->reservationId);

        if (!$reservation) {
            throw NotFoundException::forReservation($command->reservationId);
        }

        if ($reservation->getStatus() !== Reservation::STATUS_ACTIVE) {
            throw BusinessLogicException::invalidState('Only active reservations can be marked as prepared');
        }

        $reservation->markPrepared();
        $this->entityManager->flush();

        // Send notification to user
        $this->notificationService->notifyReservationPrepared($reservation);
    }
}
