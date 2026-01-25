<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Loan\ExtendLoanCommand;
use App\Application\Handler\Command\ExtendLoanHandler;
use App\Entity\Book;
use App\Entity\Loan;
use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Service\System\SystemSettingsService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExtendLoanHandlerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private LoanRepository&MockObject $loanRepository;
    private ReservationRepository&MockObject $reservationRepository;
    private SystemSettingsService&MockObject $settingsService;
    private ExtendLoanHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->loanRepository = $this->createMock(LoanRepository::class);
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->settingsService = $this->createMock(SystemSettingsService::class);

        $this->handler = new ExtendLoanHandler(
            $this->em,
            $this->loanRepository,
            $this->reservationRepository,
            $this->settingsService
        );
    }

    public function testExtendLoanSuccess(): void
    {
        $book = $this->createMock(Book::class);
        $originalDue = new \DateTimeImmutable('2025-01-15');
        
        $loan = $this->createMock(Loan::class);
        $loan->method('getReturnedAt')->willReturn(null);
        $loan->method('getExtensionsCount')->willReturn(0);
        $loan->method('getBook')->willReturn($book);
        $loan->method('getDueAt')->willReturn($originalDue);
        
        $loan->expects($this->once())->method('setDueAt')->with($this->callback(function ($newDue) use ($originalDue) {
            return $newDue->getTimestamp() === $originalDue->modify('+14 days')->getTimestamp();
        }));
        $loan->expects($this->once())->method('incrementExtensions');
        $loan->expects($this->once())->method('setLastExtendedAt')->with($this->isInstanceOf(\DateTimeImmutable::class));

        $this->loanRepository->method('find')->with(1)->willReturn($loan);
        $this->reservationRepository->method('findActiveByBook')->with($book)->willReturn([]);
        $this->settingsService->method('getLoanDurationDays')->willReturn(14);

        $this->em->expects($this->once())->method('persist')->with($loan);
        $this->em->expects($this->once())->method('flush');

        $command = new ExtendLoanCommand(loanId: 1, userId: 1);
        $result = ($this->handler)($command);

        $this->assertSame($loan, $result);
    }

    public function testThrowsExceptionWhenLoanNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Loan not found');

        $this->loanRepository->method('find')->with(999)->willReturn(null);
        $this->settingsService->method('getLoanDurationDays')->willReturn(14);

        $command = new ExtendLoanCommand(loanId: 999, userId: 1);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenLoanAlreadyReturned(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot extend returned loan');

        $loan = $this->createMock(Loan::class);
        $loan->method('getReturnedAt')->willReturn(new \DateTimeImmutable());

        $this->loanRepository->method('find')->with(1)->willReturn($loan);
        $this->settingsService->method('getLoanDurationDays')->willReturn(14);

        $command = new ExtendLoanCommand(loanId: 1, userId: 1);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenAlreadyExtended(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Wypożyczenie zostało już przedłużone');

        $loan = $this->createMock(Loan::class);
        $loan->method('getReturnedAt')->willReturn(null);
        $loan->method('getExtensionsCount')->willReturn(1);

        $this->loanRepository->method('find')->with(1)->willReturn($loan);
        $this->settingsService->method('getLoanDurationDays')->willReturn(14);

        $command = new ExtendLoanCommand(loanId: 1, userId: 1);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenBookIsReserved(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Book reserved by another reader');

        $book = $this->createMock(Book::class);
        $otherUser = $this->createMock(User::class);
        $otherUser->method('getId')->willReturn(999);
        $reservation = $this->createMock(Reservation::class);
        $reservation->method('getUser')->willReturn($otherUser);
        
        $loan = $this->createMock(Loan::class);
        $loan->method('getReturnedAt')->willReturn(null);
        $loan->method('getExtensionsCount')->willReturn(0);
        $loan->method('getBook')->willReturn($book);

        $this->loanRepository->method('find')->with(1)->willReturn($loan);
        $this->reservationRepository->method('findActiveByBook')->with($book)->willReturn([$reservation]);
        $this->settingsService->method('getLoanDurationDays')->willReturn(14);

        $command = new ExtendLoanCommand(loanId: 1, userId: 1);
        ($this->handler)($command);
    }
}

