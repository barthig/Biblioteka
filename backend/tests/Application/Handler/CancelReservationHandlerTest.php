<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Reservation\CancelReservationCommand;
use App\Application\Handler\Command\CancelReservationHandler;
use App\Entity\BookCopy;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CancelReservationHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private ReservationRepository $reservationRepository;
    private CancelReservationHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->reservationRepository = $this->createMock(ReservationRepository::class);

        $this->handler = new CancelReservationHandler(
            $this->em,
            $this->reservationRepository
        );
    }

    public function testCancelReservationSuccess(): void
    {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_ACTIVE);
        $reservation->method('getBookCopy')->willReturn(null);
        $reservation->expects($this->once())->method('cancel');

        $this->reservationRepository->method('find')->with(1)->willReturn($reservation);

        $this->em->expects($this->once())->method('persist')->with($reservation);
        $this->em->expects($this->once())->method('flush');

        $command = new CancelReservationCommand(reservationId: 1, userId: 1);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenReservationNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Reservation not found');

        $this->reservationRepository->method('find')->with(999)->willReturn(null);

        $command = new CancelReservationCommand(reservationId: 999, userId: 1);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenReservationAlreadyFulfilled(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Reservation already fulfilled');

        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_FULFILLED);

        $this->reservationRepository->method('find')->with(1)->willReturn($reservation);

        $command = new CancelReservationCommand(reservationId: 1, userId: 1);
        ($this->handler)($command);
    }
}
