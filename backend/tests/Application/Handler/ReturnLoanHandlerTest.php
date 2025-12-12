<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Loan\ReturnLoanCommand;
use App\Application\Handler\Command\ReturnLoanHandler;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\Loan;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Service\BookService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use PHPUnit\Framework\TestCase;

class ReturnLoanHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private BookService $bookService;
    private LoanRepository $loanRepository;
    private ReservationRepository $reservationRepository;
    private MessageBusInterface $bus;
    private LoggerInterface $logger;
    private ReturnLoanHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->bookService = $this->createMock(BookService::class);
        $this->loanRepository = $this->createMock(LoanRepository::class);
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new ReturnLoanHandler(
            $this->em,
            $this->bookService,
            $this->loanRepository,
            $this->reservationRepository,
            $this->bus,
            $this->logger
        );
    }

    public function testReturnLoanSuccess(): void
    {
        $book = $this->createMock(Book::class);
        $copy = $this->createMock(BookCopy::class);
        
        $loan = $this->createMock(Loan::class);
        $loan->method('getReturnedAt')->willReturn(null);
        $loan->method('getBook')->willReturn($book);
        $loan->method('getBookCopy')->willReturn($copy);
        $loan->expects($this->once())->method('setReturnedAt')->with($this->isInstanceOf(\DateTimeImmutable::class));

        $this->loanRepository->method('find')->with(1)->willReturn($loan);
        $this->reservationRepository->method('findActiveByBook')->with($book)->willReturn([]);
        $this->bookService->expects($this->once())->method('restore')->with($book, $copy);

        $this->em->expects($this->once())->method('persist')->with($loan);
        $this->em->expects($this->once())->method('flush');

        $command = new ReturnLoanCommand(loanId: 1, userId: 1);
        $result = ($this->handler)($command);

        $this->assertSame($loan, $result);
    }

    public function testThrowsExceptionWhenLoanNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Loan not found');

        $this->loanRepository->method('find')->with(999)->willReturn(null);

        $command = new ReturnLoanCommand(loanId: 999, userId: 1);
        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenLoanAlreadyReturned(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Loan already returned');

        $loan = $this->createMock(Loan::class);
        $loan->method('getReturnedAt')->willReturn(new \DateTimeImmutable());

        $this->loanRepository->method('find')->with(1)->willReturn($loan);

        $command = new ReturnLoanCommand(loanId: 1, userId: 1);
        ($this->handler)($command);
    }
}
