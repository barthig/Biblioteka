<?php
namespace App\Tests\Service;

use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\Reservation;
use App\Message\ReservationReadyMessage;
use App\Repository\ReservationRepository;
use App\Service\BookService;
use App\Service\ReservationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Doctrine\ORM\EntityManagerInterface;

class ReservationServiceTest extends TestCase
{
    public function testProcessNextReservationDoesNothingWhenQueueEmpty(): void
    {
        $reservations = $this->createMock(ReservationRepository::class);
        $bookService = $this->createMock(BookService::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $bus = $this->createMock(MessageBusInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $reservations->expects($this->once())->method('findActiveByBook')->willReturn([]);
        $bookService->expects($this->never())->method('reserveCopy');

        $service = new ReservationService($reservations, $bookService, $em, $bus, $logger);
        $service->processNextReservation($this->createMock(Book::class));
    }

    public function testProcessNextReservationAssignsCopyAndDispatches(): void
    {
        $reservation = $this->createMock(Reservation::class);
        $reservation->expects($this->once())->method('getBookCopy')->willReturn(null);

        $copy = $this->createMock(BookCopy::class);
        $reservation->expects($this->once())->method('assignBookCopy')->with($copy)->willReturn($reservation);

        $expiresAt = null;
        $reservation->expects($this->once())->method('setExpiresAt')->willReturnCallback(
            function ($value) use (&$expiresAt, $reservation) {
                $expiresAt = $value;
                return $reservation;
            }
        );
        $reservation->method('getExpiresAt')->willReturnCallback(
            function () use (&$expiresAt) {
                return $expiresAt ?? new \DateTimeImmutable();
            }
        );
        $reservation->method('getId')->willReturn(10);

        $user = $this->getMockBuilder(\App\Entity\User::class)->onlyMethods(['getId'])->getMock();
        $user->method('getId')->willReturn(5);
        $reservation->method('getUser')->willReturn($user);

        $reservations = $this->createMock(ReservationRepository::class);
        $reservations->expects($this->once())->method('findActiveByBook')->willReturn([$reservation]);

        $bookService = $this->createMock(BookService::class);
        $bookService->expects($this->once())->method('reserveCopy')->willReturn($copy);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($reservation);
        $em->expects($this->once())->method('flush');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())->method('dispatch')->with($this->callback(
            function ($message) {
                return $message instanceof ReservationReadyMessage
                    && $message->getReservationId() === 10
                    && $message->getUserId() === 5;
            }
        ));

        $logger = $this->createMock(LoggerInterface::class);

        $service = new ReservationService($reservations, $bookService, $em, $bus, $logger);
        $service->processNextReservation($this->createMock(Book::class));
    }

    public function testProcessNextReservationLogsDispatchFailure(): void
    {
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getBookCopy')->willReturn(null);
        $reservation->method('assignBookCopy')->willReturn($reservation);
        $reservation->method('setExpiresAt')->willReturn($reservation);
        $reservation->method('getExpiresAt')->willReturn(new \DateTimeImmutable());
        $reservation->method('getId')->willReturn(10);

        $user = $this->getMockBuilder(\App\Entity\User::class)->onlyMethods(['getId'])->getMock();
        $user->method('getId')->willReturn(5);
        $reservation->method('getUser')->willReturn($user);

        $reservations = $this->createMock(ReservationRepository::class);
        $reservations->method('findActiveByBook')->willReturn([$reservation]);

        $bookService = $this->createMock(BookService::class);
        $bookService->method('reserveCopy')->willReturn($this->createMock(BookCopy::class));

        $em = $this->createMock(EntityManagerInterface::class);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willThrowException(new \RuntimeException('fail'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $service = new ReservationService($reservations, $bookService, $em, $bus, $logger);
        $service->processNextReservation($this->createMock(Book::class));
    }
}
