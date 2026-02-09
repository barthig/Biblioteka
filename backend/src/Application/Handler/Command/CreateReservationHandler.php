<?php
namespace App\Application\Handler\Command;

use App\Application\Command\Reservation\CreateReservationCommand;
use App\Entity\Book;
use App\Entity\Reservation;
use App\Entity\User;
use App\Event\ReservationCreatedEvent;
use App\Exception\BusinessLogicException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Message\ReservationQueuedNotification;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler]
class CreateReservationHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReservationRepository $reservationRepository,
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(CreateReservationCommand $command): Reservation
    {
        // Issue #14: Validate expiresInDays
        if ($command->expiresInDays < 1 || $command->expiresInDays > 14) {
            throw ValidationException::forField('expiresInDays', 'Reservation expiry must be between 1 and 14 days');
        }

        $userRepo = $this->entityManager->getRepository(User::class);
        $bookRepo = $this->entityManager->getRepository(Book::class);

        $user = $userRepo->find($command->userId);
        $book = $bookRepo->find($command->bookId);
        
        if (!$user) {
            throw NotFoundException::forUser($command->userId);
        }
        if (!$book) {
            throw NotFoundException::forBook($command->bookId);
        }

        // Issue #22: Check user's active reservation limit (max 5)
        $activeCount = $this->reservationRepository->countActiveByUser($user);
        if ($activeCount >= 5) {
            throw BusinessLogicException::maxReservationsReached(5);
        }

        // Issue #15: Check actual availability, not just copy count
        $availableCopies = $book->getCopies();
        if ($availableCopies > 0) {
            throw BusinessLogicException::invalidState('Book is currently available — borrow instead of reserving.');
        }

        if ($this->reservationRepository->findFirstActiveForUserAndBook($user, $book)) {
            throw BusinessLogicException::bookAlreadyReserved();
        }

        $reservation = (new Reservation())
            ->setBook($book)
            ->setUser($user)
            ->setExpiresAt((new \DateTimeImmutable())->modify("+{$command->expiresInDays} days"));

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new ReservationCreatedEvent($reservation));

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
