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
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ReturnLoanHandlerTest extends TestCase
{
    private EntityManagerInterface $em;
    private BookService $bookService;
    private LoanRepository $loanRepository;
    private ReservationRepository $reservationRepository;
    private MessageBusInterface $bus;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;
    private ReturnLoanHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->loanRepository = $this->createMock(LoanRepository::class);
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->bookService = $this->createMock(BookService::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->handler = new ReturnLoanHandler(
            $this->em,
            $this->bookService,
            $this->loanRepository,
            $this->reservationRepository,
            $this->bus,
            $this->logger,
            $this->eventDispatcher
        );
    }

    public function testReturnLoanSuccess(): void
    {
        $book = new Book();
        $copy = new BookCopy();

        $loan = new Loan();
        $loan->setBook($book)
            ->setBookCopy($copy)
            ->setDueAt(new \DateTimeImmutable('+1 day'));

        $this->loanRepository->method('find')->with(1)->willReturn($loan);
        $this->reservationRepository->method('findActiveByBook')->with($book)->willReturn([]);
        $this->bookService->expects($this->once())->method('restore')->with($book, $copy);

        $this->em->expects($this->once())->method('persist')->with($loan);
        $this->em->expects($this->once())->method('flush');

        $command = new ReturnLoanCommand(loanId: 1, userId: 1);
        $result = ($this->handler)($command);

        $this->assertSame($loan, $result);
        $this->assertInstanceOf(\DateTimeImmutable::class, $loan->getReturnedAt());
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

        $loan = (new Loan())
            ->setBook(new Book())
            ->setBookCopy(new BookCopy())
            ->setDueAt(new \DateTimeImmutable())
            ->setReturnedAt(new \DateTimeImmutable());

        $this->loanRepository->method('find')->with(1)->willReturn($loan);

        $command = new ReturnLoanCommand(loanId: 1, userId: 1);
        ($this->handler)($command);
    }
}
