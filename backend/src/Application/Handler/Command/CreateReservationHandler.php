<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Reservation\CreateReservationCommand;
use App\Entity\Book;
use App\Entity\Reservation;
use App\Entity\User;
use App\Message\ReservationQueuedNotification;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class CreateReservationHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private ReservationRepository $reservationRepository,
        private MessageBusInterface $bus,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(CreateReservationCommand $command): Reservation
    {
        // Issue #14: Validate expiresInDays
        if ($command->expiresInDays < 1 || $command->expiresInDays > 14) {
            throw new \InvalidArgumentException('Reservation expiry must be between 1 and 14 days');
        }

        $userRepo = $this->em->getRepository(User::class);
        $bookRepo = $this->em->getRepository(Book::class);

        $user = $userRepo->find($command->userId);
        $book = $bookRepo->find($command->bookId);
        
        if (!$user || !$book) {
            throw new \RuntimeException('User or book not found');
        }

        // Issue #22: Check user's active reservation limit (max 5)
        $activeCount = $this->reservationRepository->countActiveByUser($user);
        if ($activeCount >= 5) {
            throw new \RuntimeException('Osiągnięto limit aktywnych rezerwacji (max 5)');
        }

        // Issue #15: Check actual availability, not just copy count
        $availableCopies = $book->getAvailable();
        if ($availableCopies > 0) {
            throw new \RuntimeException('Book currently available, wypożycz zamiast rezerwować');
        }

        if ($this->reservationRepository->findFirstActiveForUserAndBook($user, $book)) {
            throw new \RuntimeException('Masz już aktywną rezerwację na tę książkę');
        }

        $reservation = (new Reservation())
            ->setBook($book)
            ->setUser($user)
            ->setExpiresAt((new \DateTimeImmutable())->modify("+{$command->expiresInDays} days"));

        $this->em->persist($reservation);
        $this->em->flush();

        try {
            $this->bus->dispatch(new ReservationQueuedNotification(
                $reservation->getId(),
                $book->getId(),
                $user->getEmail()
            ));
        } catch (\Throwable $e) {
            $this->logger->warning('Reservation notification dispatch failed', [
                'reservationId' => $reservation->getId(),
                'error' => $e->getMessage()
            ]);
        }

        return $reservation;
    }
}
