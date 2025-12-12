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
        $userRepo = $this->em->getRepository(User::class);
        $bookRepo = $this->em->getRepository(Book::class);

        $user = $userRepo->find($command->userId);
        $book = $bookRepo->find($command->bookId);
        
        if (!$user || !$book) {
            throw new \RuntimeException('User or book not found');
        }

        if ($book->getCopies() > 0) {
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
