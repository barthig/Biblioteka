<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Loan\CreateLoanCommand;
use App\Application\Command\Reservation\FulfillReservationCommand;
use App\Application\Command\Reservation\FulfillReservationWorkflowCommand;
use App\Application\Handler\Command\FulfillReservationWorkflowHandler;
use App\Entity\BookCopy;
use App\Entity\Loan;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FulfillReservationWorkflowHandlerTest extends TestCase
{
    private ReservationRepository|MockObject $reservationRepository;
    private EntityManagerInterface|MockObject $em;
    private MessageBusInterface|MockObject $commandBus;
    private FulfillReservationWorkflowHandler $handler;

    protected function setUp(): void
    {
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->commandBus = $this->createMock(MessageBusInterface::class);

        $this->handler = new FulfillReservationWorkflowHandler(
            $this->reservationRepository,
            $this->em,
            $this->commandBus
        );
    }

    public function testThrowsWhenReservationMissing(): void
    {
        $this->reservationRepository->method('find')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        ($this->handler)(new FulfillReservationWorkflowCommand(1, 1));
    }

    public function testThrowsWhenReservationNotActive(): void
    {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_CANCELLED);

        $this->reservationRepository->method('find')->willReturn($reservation);

        $this->expectException(BadRequestHttpException::class);
        ($this->handler)(new FulfillReservationWorkflowCommand(1, 1));
    }

    public function testThrowsWhenNoCopyOrExpired(): void
    {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_ACTIVE);
        $reservation->method('getExpiresAt')->willReturn((new \DateTimeImmutable('-1 day')));
        $this->reservationRepository->method('find')->willReturn($reservation);

        $this->expectException(BadRequestHttpException::class);
        ($this->handler)(new FulfillReservationWorkflowCommand(1, 1));
    }

    public function testThrowsWhenCopyNotReserved(): void
    {
        $copy = $this->createMock(BookCopy::class);
        $copy->method('getStatus')->willReturn(BookCopy::STATUS_AVAILABLE);

        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_ACTIVE);
        $reservation->method('getExpiresAt')->willReturn((new \DateTimeImmutable('+1 day')));
        $reservation->method('getBookCopy')->willReturn($copy);

        $this->reservationRepository->method('find')->willReturn($reservation);

        $this->expectException(BadRequestHttpException::class);
        ($this->handler)(new FulfillReservationWorkflowCommand(1, 1));
    }

    public function testCreatesLoanAndFulfillsReservation(): void
    {
        $copy = $this->createMock(BookCopy::class);
        $copy->method('getStatus')->willReturn(BookCopy::STATUS_RESERVED);
        $copy->method('getId')->willReturn(5);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(10);

        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getStatus')->willReturn(Reservation::STATUS_ACTIVE);
        $reservation->method('getExpiresAt')->willReturn((new \DateTimeImmutable('+1 day')));
        $reservation->method('getBookCopy')->willReturn($copy);
        $reservation->method('getUser')->willReturn($user);
        $reservation->method('getId')->willReturn(3);

        $this->reservationRepository->method('find')->willReturn($reservation);

        $loan = $this->createMock(Loan::class);
        $loan->method('getId')->willReturn(99);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('commit');

        $this->commandBus->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($cmd) use ($loan) {
                if ($cmd instanceof CreateLoanCommand) {
                    return new Envelope($cmd, [new HandledStamp($loan, 'handler')]);
                }
                if ($cmd instanceof FulfillReservationCommand) {
                    return new Envelope($cmd, [new HandledStamp(null, 'handler')]);
                }
                return new Envelope($cmd);
            });

        ($this->handler)(new FulfillReservationWorkflowCommand(3, 10));
    }
}
