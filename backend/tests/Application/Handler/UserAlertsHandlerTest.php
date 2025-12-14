<?php
namespace App\Tests\Application\Handler;

use App\Application\Handler\Query\UserAlertsHandler;
use App\Application\Query\Alert\UserAlertsQuery;
use App\Entity\Fine;
use App\Entity\Loan;
use App\Entity\Reservation;
use App\Entity\Book;
use App\Entity\BookCopy;
use App\Repository\FineRepository;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserAlertsHandlerTest extends TestCase
{
    private LoanRepository|MockObject $loanRepo;
    private ReservationRepository|MockObject $reservationRepo;
    private FineRepository|MockObject $fineRepo;
    private UserAlertsHandler $handler;

    protected function setUp(): void
    {
        $this->loanRepo = $this->createMock(LoanRepository::class);
        $this->reservationRepo = $this->createMock(ReservationRepository::class);
        $this->fineRepo = $this->createMock(FineRepository::class);

        $this->handler = new UserAlertsHandler(
            $this->loanRepo,
            $this->reservationRepo,
            $this->fineRepo
        );
    }

    public function testBuildsDueSoonAndOverdueAlerts(): void
    {
        $book = (new Book())->setTitle('Test Book');
        $copy = (new BookCopy())->setBook($book);

        $dueSoon = (new Loan())->setBookCopy($copy)->setDueAt((new \DateTimeImmutable('+12 hours')));
        $overdue = (new Loan())->setBookCopy($copy)->setDueAt((new \DateTimeImmutable('-1 day')));

        $this->loanRepo->method('findBy')->willReturn([$dueSoon, $overdue]);
        $reservationQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $reservationQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $reservationQb->method('where')->willReturnSelf();
        $reservationQb->method('andWhere')->willReturnSelf();
        $reservationQb->method('setParameter')->willReturnSelf();
        $reservationQb->method('getQuery')->willReturn($reservationQuery);
        $reservationQuery->method('getResult')->willReturn([]);
        $this->reservationRepo->method('createQueryBuilder')->willReturn($reservationQb);

        $fineQb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $fineQuery = $this->createMock(\Doctrine\ORM\Query::class);
        $fineQb->method('join')->willReturnSelf();
        $fineQb->method('where')->willReturnSelf();
        $fineQb->method('andWhere')->willReturnSelf();
        $fineQb->method('setParameter')->willReturnSelf();
        $fineQb->method('getQuery')->willReturn($fineQuery);
        $fineQuery->method('getResult')->willReturn([]);
        $this->fineRepo->method('createQueryBuilder')->willReturn($fineQb);

        $alerts = ($this->handler)(new UserAlertsQuery(1));

        self::assertNotEmpty($alerts);
        self::assertArrayHasKey('type', $alerts[0]);
        $types = array_column($alerts, 'type');
        self::assertContains('due_soon', $types);
        self::assertContains('overdue', $types);
    }
}
