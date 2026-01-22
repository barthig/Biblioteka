<?php

declare(strict_types=1);

namespace App\Application\Command\Reservation;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PrepareReservationCommandHandler
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private EntityManagerInterface $em,
        private NotificationService $notificationService,
    ) {
    }

    public function __invoke(PrepareReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->find($command->reservationId);

        if (!$reservation) {
            throw new \RuntimeException('Reservation not found');
        }

        if ($reservation->getStatus() !== Reservation::STATUS_ACTIVE) {
            throw new \RuntimeException('Only active reservations can be marked as prepared');
        }

        $reservation->markPrepared();
        $this->em->flush();

        // Send notification to user
        $this->notificationService->notifyReservationPrepared($reservation);
    }
}
