<?php
declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ReservationQueuedNotification;
use App\Repository\ReservationRepository;
use App\Service\User\NotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ReservationQueuedDeliveryHandler
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ReservationQueuedNotification $message): void
    {
        $reservation = $this->reservationRepository->find($message->getReservationId());
        if ($reservation === null) {
            $this->logger->warning('Queued reservation notification skipped - reservation missing', [
                'reservationId' => $message->getReservationId(),
            ]);
            return;
        }

        $this->notificationService->notifyReservationQueued($reservation);
    }
}
