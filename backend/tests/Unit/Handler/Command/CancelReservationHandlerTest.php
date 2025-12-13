<?php

namespace App\Tests\Unit\Handler\Command;

use App\Application\Command\Reservation\CancelReservationCommand;
use App\Application\Handler\Command\CancelReservationHandler;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CancelReservationHandlerTest extends TestCase
{
    private $em;
    private $reservationRepository;
    private $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->handler = new CancelReservationHandler($this->em, $this->reservationRepository);
    }

    public function testOwnershipVerificationForNonLibrarian(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Forbidden');

        $owner = $this->createMock(User::class);
        $owner->method('getId')->willReturn(1);

        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_ACTIVE);
        $reservation->method('getUser')->willReturn($owner);

        $this->reservationRepository->method('find')->willReturn($reservation);

        $command = new CancelReservationCommand(
            reservationId: 1,
            userId: 2, // Different user
            isLibrarian: false
        );

        ($this->handler)($command);
    }

    public function testLibrarianCanCancelAnyReservation(): void
    {
        $owner = $this->createMock(User::class);
        $owner->method('getId')->willReturn(1);

        $book = $this->createMock(Book::class);
        $book->method('recalculateInventoryCounters')->willReturn(null);

        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_ACTIVE);
        $reservation->method('getUser')->willReturn($owner);
        $reservation->method('getBookCopy')->willReturn(null);
        $reservation->method('getBook')->willReturn($book);

        $this->reservationRepository->method('find')->willReturn($reservation);

        $command = new CancelReservationCommand(
            reservationId: 1,
            userId: 2, // Different user
            isLibrarian: true // But is librarian
        );

        $this->em->expects($this->once())->method('flush');

        ($this->handler)($command);
    }

    public function testCannotCancelFulfilledReservation(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Reservation already fulfilled');

        $owner = $this->createMock(User::class);
        $owner->method('getId')->willReturn(1);

        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_FULFILLED);
        $reservation->method('getUser')->willReturn($owner);

        $this->reservationRepository->method('find')->willReturn($reservation);

        $command = new CancelReservationCommand(
            reservationId: 1,
            userId: 1,
            isLibrarian: false
        );

        ($this->handler)($command);
    }

    public function testCannotCancelAlreadyCancelledReservation(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Reservation already cancelled');

        $owner = $this->createMock(User::class);
        $owner->method('getId')->willReturn(1);

        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_CANCELLED);
        $reservation->method('getUser')->willReturn($owner);

        $this->reservationRepository->method('find')->willReturn($reservation);

        $command = new CancelReservationCommand(
            reservationId: 1,
            userId: 1,
            isLibrarian: false
        );

        ($this->handler)($command);
    }

    public function testCannotCancelExpiredReservation(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Reservation already expired');

        $owner = $this->createMock(User::class);
        $owner->method('getId')->willReturn(1);

        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_EXPIRED);
        $reservation->method('getUser')->willReturn($owner);

        $this->reservationRepository->method('find')->willReturn($reservation);

        $command = new CancelReservationCommand(
            reservationId: 1,
            userId: 1,
            isLibrarian: false
        );

        ($this->handler)($command);
    }

    public function testCopyIsReleasedWhenCancelling(): void
    {
        $owner = $this->createMock(User::class);
        $owner->method('getId')->willReturn(1);

        $book = $this->createMock(Book::class);
        $book->expects($this->once())->method('recalculateInventoryCounters');

        $copy = $this->createMock(BookCopy::class);
        $copy->expects($this->once())->method('setStatus')->with(BookCopy::STATUS_AVAILABLE);

        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_ACTIVE);
        $reservation->method('getUser')->willReturn($owner);
        $reservation->method('getBookCopy')->willReturn($copy);
        $reservation->method('getBook')->willReturn($book);
        $reservation->expects($this->once())->method('cancel');
        $reservation->expects($this->once())->method('clearBookCopy');

        $this->reservationRepository->method('find')->willReturn($reservation);

        $command = new CancelReservationCommand(
            reservationId: 1,
            userId: 1,
            isLibrarian: false
        );

        $this->em->expects($this->once())->method('flush');

        ($this->handler)($command);
    }
}
