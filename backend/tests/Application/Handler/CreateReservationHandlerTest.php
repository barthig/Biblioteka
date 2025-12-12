<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Reservation\CreateReservationCommand;
use App\Application\Handler\Command\CreateReservationHandler;
use App\Entity\Book;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class CreateReservationHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private ReservationRepository $reservationRepository;
    private MessageBusInterface $bus;
    private LoggerInterface $logger;
    private CreateReservationHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new CreateReservationHandler(
            $this->em,
            $this->reservationRepository,
            $this->bus,
            $this->logger
        );
    }

    public function testCreateReservationSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@example.com');

        $book = $this->createMock(Book::class);
        $book->method('getCopies')->willReturn(0);
        $book->method('getId')->willReturn(1);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('find')->with(1)->willReturn($user);

        $bookRepo = $this->createMock(EntityRepository::class);
        $bookRepo->method('find')->with(1)->willReturn($book);

        $this->em->method('getRepository')->willReturnCallback(function ($class) use ($userRepo, $bookRepo) {
            return match ($class) {
                User::class => $userRepo,
                Book::class => $bookRepo,
            };
        });

        $this->reservationRepository->method('findFirstActiveForUserAndBook')->with($user, $book)->willReturn(null);

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Reservation::class));
        $this->em->expects($this->once())->method('flush');

        $command = new CreateReservationCommand(userId: 1, bookId: 1, expiresInDays: 14);
        $result = ($this->handler)($command);

        $this->assertInstanceOf(Reservation::class, $result);
    }

    public function testThrowsExceptionWhenUserNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User or book not found');

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('find')->with(999)->willReturn(null);

        $bookRepo = $this->createMock(EntityRepository::class);

        $this->em->method('getRepository')->willReturnCallback(function ($class) use ($userRepo, $bookRepo) {
            return match ($class) {
                User::class => $userRepo,
                Book::class => $bookRepo,
            };
        });

        $command = new CreateReservationCommand(userId: 999, bookId: 1, expiresInDays: 14);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenBookAvailable(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Book currently available, wypożycz zamiast rezerwować');

        $user = $this->createMock(User::class);
        $book = $this->createMock(Book::class);
        $book->method('getCopies')->willReturn(5);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('find')->with(1)->willReturn($user);

        $bookRepo = $this->createMock(EntityRepository::class);
        $bookRepo->method('find')->with(1)->willReturn($book);

        $this->em->method('getRepository')->willReturnCallback(function ($class) use ($userRepo, $bookRepo) {
            return match ($class) {
                User::class => $userRepo,
                Book::class => $bookRepo,
            };
        });

        $command = new CreateReservationCommand(userId: 1, bookId: 1, expiresInDays: 14);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenAlreadyReserved(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Masz już aktywną rezerwację na tę książkę');

        $user = $this->createMock(User::class);
        $book = $this->createMock(Book::class);
        $book->method('getCopies')->willReturn(0);

        $existingReservation = $this->createMock(Reservation::class);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('find')->with(1)->willReturn($user);

        $bookRepo = $this->createMock(EntityRepository::class);
        $bookRepo->method('find')->with(1)->willReturn($book);

        $this->em->method('getRepository')->willReturnCallback(function ($class) use ($userRepo, $bookRepo) {
            return match ($class) {
                User::class => $userRepo,
                Book::class => $bookRepo,
            };
        });

        $this->reservationRepository->method('findFirstActiveForUserAndBook')->with($user, $book)->willReturn($existingReservation);

        $command = new CreateReservationCommand(userId: 1, bookId: 1, expiresInDays: 14);
        ($this->handler)($command);
    }
}
