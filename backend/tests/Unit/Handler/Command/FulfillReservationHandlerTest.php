<?php

namespace App\Tests\Unit\Handler\Command;

use App\Application\Command\Reservation\FulfillReservationCommand;
use App\Application\Handler\Command\FulfillReservationHandler;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class FulfillReservationHandlerTest extends TestCase
{
    private $em;
    private $reservationRepository;
    private $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->handler = new FulfillReservationHandler($this->em, $this->reservationRepository);
    }

    public function testFulfillMarksReservationAsFulfilled(): void
    {
        $copy = $this->createMock(BookCopy::class);

        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_ACTIVE);
        $reservation->method('getBookCopy')->willReturn($copy);
        $reservation->expects($this->once())->method('markFulfilled');

        $this->reservationRepository->method('find')->willReturn($reservation);

        $command = new FulfillReservationCommand(
            reservationId: 1,
            loanId: 100
        );

        $this->em->expects($this->once())->method('flush');

        ($this->handler)($command);
    }

    public function testFulfillDoesNotReleaseCopy(): void
    {
        $copy = $this->createMock(BookCopy::class);
        // Copy status should NOT be changed to AVAILABLE
        $copy->expects($this->never())->method('setStatus');

        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_ACTIVE);
        $reservation->method('getBookCopy')->willReturn($copy);
        // Copy should NOT be cleared from reservation
        $reservation->expects($this->never())->method('clearBookCopy');

        $this->reservationRepository->method('find')->willReturn($reservation);

        $command = new FulfillReservationCommand(
            reservationId: 1,
            loanId: 100
        );

        ($this->handler)($command);
    }

    public function testCannotFulfillNonActiveReservation(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Reservation must be active or prepared to fulfill');

        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_CANCELLED);

        $this->reservationRepository->method('find')->willReturn($reservation);

        $command = new FulfillReservationCommand(
            reservationId: 1,
            loanId: 100
        );

        ($this->handler)($command);
    }

    public function testCannotFulfillWithoutAssignedCopy(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No book copy assigned');

        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_ACTIVE);
        $reservation->method('getBookCopy')->willReturn(null);

        $this->reservationRepository->method('find')->willReturn($reservation);

        $command = new FulfillReservationCommand(
            reservationId: 1,
            loanId: 100
        );

        ($this->handler)($command);
    }
}
