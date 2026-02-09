<?php
declare(strict_types=1);
namespace App\Message;

class ReservationReadyMessage implements NotificationMessageInterface
{
    public function __construct(
        private int $reservationId,
        private int $userId,
        private string $expiresAtIso
    ) {
    }

    public function getReservationId(): int
    {
        return $this->reservationId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getExpiresAtIso(): string
    {
        return $this->expiresAtIso;
    }

    public function getType(): string
    {
        return NotificationMessageInterface::TYPE_RESERVATION_READY;
    }

    public function getFingerprint(): string
    {
        return sprintf('reservation_ready_%d_%s', $this->reservationId, $this->expiresAtIso);
    }

    public function getPayload(): array
    {
        return [
            'reservationId' => $this->reservationId,
            'expiresAt' => $this->expiresAtIso,
        ];
    }
}
