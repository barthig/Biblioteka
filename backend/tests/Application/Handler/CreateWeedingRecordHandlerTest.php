<?php
namespace App\Tests\Application\Handler;

use App\Application\Command\Weeding\CreateWeedingRecordCommand;
use App\Application\Handler\Command\CreateWeedingRecordHandler;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Entity\WeedingRecord;
use App\Repository\BookCopyRepository;
use App\Repository\BookRepository;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Service\BookService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateWeedingRecordHandlerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private BookRepository $bookRepository;
    private BookCopyRepository $bookCopyRepository;
    private LoanRepository $loanRepository;
    private ReservationRepository $reservationRepository;
    private UserRepository $userRepository;
    private BookService $bookService;
    private CreateWeedingRecordHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->bookRepository = $this->createMock(BookRepository::class);
        $this->bookCopyRepository = $this->createMock(BookCopyRepository::class);
        $this->loanRepository = $this->createMock(LoanRepository::class);
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->bookService = $this->createMock(BookService::class);
        
        $this->handler = new CreateWeedingRecordHandler(
            $this->entityManager,
            $this->bookRepository,
            $this->bookCopyRepository,
            $this->loanRepository,
            $this->reservationRepository,
            $this->userRepository,
            $this->bookService
        );
    }

    public function testCreateWeedingRecordSuccess(): void
    {
        $book = $this->createMock(Book::class);
        $book->expects($this->once())->method('recalculateInventoryCounters');
        
        $user = $this->createMock(\App\Entity\User::class);
        $connection = $this->createMock(\Doctrine\DBAL\Connection::class);
        
        $this->bookRepository->method('find')->with(1)->willReturn($book);
        $this->userRepository->method('find')->with(1)->willReturn($user);
        $this->entityManager->method('getConnection')->willReturn($connection);
        
        $this->entityManager->expects($this->exactly(2))->method('persist'); // book + record
        $this->entityManager->expects($this->once())->method('flush');

        $command = new CreateWeedingRecordCommand(
            bookId: 1,
            copyId: null,
            reason: 'Damaged',
            action: null,
            conditionState: null,
            notes: null,
            removedAt: null,
            userId: 1
        );
        
        $result = ($this->handler)($command);

        $this->assertInstanceOf(WeedingRecord::class, $result);
    }
}
