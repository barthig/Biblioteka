<?php
namespace App\Tests\Application\Handler;

use App\Application\Query\Reservation\ListReservationsQuery;
use App\Application\Handler\Query\ListReservationsHandler;
use App\Repository\ReservationRepository;
use PHPUnit\Framework\TestCase;

class ListReservationsHandlerTest extends TestCase
{
    private ReservationRepository $reservationRepository;
    private ListReservationsHandler $handler;

    protected function setUp(): void
    {
        $this->reservationRepository = $this->createMock(ReservationRepository::class);
        $this->handler = new ListReservationsHandler($this->reservationRepository);
    }

    public function testListReservationsSuccess(): void
    {
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $doctrineQuery = $this->createMock(\Doctrine\ORM\Query::class);
        
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('addSelect')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('select')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('setFirstResult')->willReturnSelf();
        $qb->method('getQuery')->willReturn($doctrineQuery);
        $doctrineQuery->method('getResult')->willReturn([]);
        $doctrineQuery->method('getSingleScalarResult')->willReturn(0);

        $this->reservationRepository->method('createQueryBuilder')->willReturn($qb);

        $query = new ListReservationsQuery();
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
