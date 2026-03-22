<?php

namespace App\Tests\Unit\Handler\Command;

use App\Application\Command\Reservation\FulfillReservationCommand;
use App\Application\Handler\Command\FulfillReservationHandler;
use App\Entity\BookCopy;
use App\Entity\Reservation;
use App\Event\ReservationFulfilledEvent;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FulfillReservationHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private ReservationRepository $reservationRepository;
    private EventDispatcherInterface $eventDispatcher;
    private FulfillReservationHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->handler = new FulfillReservationHandler($this->em, $this->reservationRepository, $this->eventDispatcher);
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

        $this->em->expects($this->once())->method('persist')->with($reservation);
        $this->em->expects($this->once())->method('flush');
        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ReservationFulfilledEvent::class));

        ($this->handler)($command);
    }

    public function testFulfillDoesNotReleaseCopy(): void
    {
        $copy = $this->createMock(BookCopy::class);
        $copy->expects($this->never())->method('setStatus');

        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_ACTIVE);
        $reservation->method('getBookCopy')->willReturn($copy);
        $reservation->expects($this->never())->method('clearBookCopy');
        $reservation->expects($this->once())->method('markFulfilled');

        $this->reservationRepository->method('find')->willReturn($reservation);

        $command = new FulfillReservationCommand(
            reservationId: 1,
            loanId: 100
        );

        $this->em->expects($this->once())->method('persist')->with($reservation);
        $this->em->expects($this->once())->method('flush');
        $this->eventDispatcher->expects($this->once())->method('dispatch');

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
        $this->expectExceptionMessage('No book copy assigned to reservation');

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
