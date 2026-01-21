<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Loan\CreateLoanCommand;
use App\Application\Handler\Command\CreateLoanHandler;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\Loan;
use App\Entity\User;
use App\Repository\BookCopyRepository;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Service\BookService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CreateLoanHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private BookService $bookService;
    private LoanRepository $loanRepository;
    private ReservationRepository $reservationRepository;
    private BookCopyRepository $bookCopyRepository;
    private \App\Service\SystemSettingsService $settingsService;
    private EventDispatcherInterface $eventDispatcher;
    private CreateLoanHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->bookService = $this->createMock(BookService::class);
        $this->loanRepository = $this->createMock(LoanRepository::class);
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->bookCopyRepository = $this->createMock(BookCopyRepository::class);
        $this->settingsService = $this->createMock(\App\Service\SystemSettingsService::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->handler = new CreateLoanHandler(
            $this->em,
            $this->bookService,
            $this->loanRepository,
            $this->reservationRepository,
            $this->bookCopyRepository,
            $this->settingsService,
            $this->eventDispatcher
        );
    }

    public function testCreateLoanSuccess(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('isBlocked')->willReturn(false);
        $user->method('getLoanLimit')->willReturn(5);

        $book = $this->createMock(Book::class);
        $book->method('getId')->willReturn(10);

        $copy = $this->createMock(BookCopy::class);

        $userRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $userRepo->method('find')->with(1)->willReturn($user);

        $bookRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $bookRepo->method('find')->with(10)->willReturn($book);

        $interactionRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $interactionRepo->method('findOneBy')->willReturn(null);

        $this->em->method('getRepository')
            ->willReturnCallback(function ($class) use ($userRepo, $bookRepo, $interactionRepo) {
                if ($class === User::class) return $userRepo;
                if ($class === Book::class) return $bookRepo;
                if ($class === \App\Entity\UserBookInteraction::class) return $interactionRepo;
                return $this->createMock(\Doctrine\ORM\EntityRepository::class);
            });

        $this->loanRepository->method('countActiveByUser')->with($user)->willReturn(2);
        $this->reservationRepository->method('findFirstActiveForUserAndBook')->willReturn(null);
        $this->bookService->method('borrow')->with($book, null, null, false)->willReturn($copy);
        $this->settingsService->method('getLoanDurationDays')->willReturn(14);

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->once())->method('flush');
        $this->em->expects($this->once())->method('commit');

        $command = new CreateLoanCommand(userId: 1, bookId: 10);
        $loan = ($this->handler)($command);

        $this->assertInstanceOf(Loan::class, $loan);
    }

    public function testThrowsExceptionWhenUserNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not found');

        $userRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $userRepo->method('find')->with(999)->willReturn(null);

        $bookRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);

        $this->em->method('getRepository')->willReturnCallback(function ($class) use ($userRepo, $bookRepo) {
            return match ($class) {
                User::class => $userRepo,
                Book::class => $bookRepo,
                default => $this->createMock(\Doctrine\ORM\EntityRepository::class),
            };
        });

        $this->settingsService->method('getLoanDurationDays')->willReturn(14);

        $command = new CreateLoanCommand(userId: 999, bookId: 10);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenUserIsBlocked(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Konto czytelnika jest zablokowane');

        $user = $this->createMock(User::class);
        $user->method('isBlocked')->willReturn(true);

        $userRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $userRepo->method('find')->with(1)->willReturn($user);

        $bookRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);

        $this->em->method('getRepository')->willReturnCallback(function ($class) use ($userRepo, $bookRepo) {
            return match ($class) {
                User::class => $userRepo,
                Book::class => $bookRepo,
                default => $this->createMock(\Doctrine\ORM\EntityRepository::class),
            };
        });

        $command = new CreateLoanCommand(userId: 1, bookId: 10);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenLoanLimitReached(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Limit wypożyczeń został osiągnięty');

        $user = $this->createMock(User::class);
        $user->method('isBlocked')->willReturn(false);
        $user->method('getLoanLimit')->willReturn(3);

        $userRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $userRepo->method('find')->with(1)->willReturn($user);

        $bookRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);

        $this->em->method('getRepository')->willReturnCallback(function ($class) use ($userRepo, $bookRepo) {
            return match ($class) {
                User::class => $userRepo,
                Book::class => $bookRepo,
                default => $this->createMock(\Doctrine\ORM\EntityRepository::class),
            };
        });

        $this->loanRepository->method('countActiveByUser')->with($user)->willReturn(3);
        $this->settingsService->method('getLoanDurationDays')->willReturn(14);

        $command = new CreateLoanCommand(userId: 1, bookId: 10);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenBookNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Book not found');

        $user = $this->createMock(User::class);
        $user->method('isBlocked')->willReturn(false);
        $user->method('getLoanLimit')->willReturn(5);

        $userRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $userRepo->method('find')->with(1)->willReturn($user);

        $bookRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $bookRepo->method('find')->with(999)->willReturn(null);

        $this->em->method('getRepository')
            ->willReturnCallback(function ($class) use ($userRepo, $bookRepo) {
                if ($class === User::class) return $userRepo;
                if ($class === Book::class) return $bookRepo;
                return $this->createMock(\Doctrine\ORM\EntityRepository::class);
            });

        $this->loanRepository->method('countActiveByUser')->with($user)->willReturn(0);
        $this->settingsService->method('getLoanDurationDays')->willReturn(14);

        $command = new CreateLoanCommand(userId: 1, bookId: 999);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenNoCopiesAvailable(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No copies available');

        $user = $this->createMock(User::class);
        $user->method('isBlocked')->willReturn(false);
        $user->method('getLoanLimit')->willReturn(5);

        $book = $this->createMock(Book::class);

        $userRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $userRepo->method('find')->with(1)->willReturn($user);

        $bookRepo = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $bookRepo->method('find')->with(10)->willReturn($book);

        $this->em->method('getRepository')
            ->willReturnCallback(function ($class) use ($userRepo, $bookRepo) {
                if ($class === User::class) return $userRepo;
                if ($class === Book::class) return $bookRepo;
                return $this->createMock(\Doctrine\ORM\EntityRepository::class);
            });

        $this->loanRepository->method('countActiveByUser')->with($user)->willReturn(0);
        $this->reservationRepository->method('findFirstActiveForUserAndBook')->willReturn(null);
        $this->reservationRepository->method('findActiveByBook')->willReturn([]);
        $this->bookService->method('borrow')->with($book, null, null, false)->willReturn(null);
        $this->settingsService->method('getLoanDurationDays')->willReturn(14);

        $command = new CreateLoanCommand(userId: 1, bookId: 10);
        ($this->handler)($command);
    }
}
