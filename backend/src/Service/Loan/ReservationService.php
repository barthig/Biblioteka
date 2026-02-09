<?php

declare(strict_types=1);

namespace App\Service\Loan;

use App\Entity\Book;
use App\Message\ReservationReadyMessage;
use App\Repository\ReservationRepository;
use App\Service\Book\BookService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class ReservationService
{
    public function __construct(
        private readonly ReservationRepository $reservations,
        private readonly BookService $bookService,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $bus,
        private readonly LoggerInterface $logger
    ) {
    }

    public function processNextReservation(Book $book): void
    {
        $queue = $this->reservations->findActiveByBook($book);
        if (empty($queue)) {
            return;
        }

        $nextReservation = null;
        foreach ($queue as $reservation) {
            if ($reservation->getBookCopy() === null) {
                $nextReservation = $reservation;
                break;
            }
        }

        if (!$nextReservation) {
            return;
        }

        $copy = $this->bookService->reserveCopy($book, null, true);
        if (!$copy) {
            return;
        }

        $nextReservation->assignBookCopy($copy);
        $nextReservation->setExpiresAt((new \DateTimeImmutable())->modify('+2 days'));

        $this->entityManager->persist($nextReservation);
        $this->entityManager->flush();

        try {
            $expiresAtIso = $nextReservation->getExpiresAt()->format(DATE_ATOM);
            $this->bus->dispatch(new ReservationReadyMessage(
                $nextReservation->getId(),
                $nextReservation->getUser()->getId(),
                $expiresAtIso
            ));
        } catch (\Throwable $e) {
            $this->logger->error('Failed to dispatch ReservationReadyMessage', [
                'reservationId' => $nextReservation->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}

