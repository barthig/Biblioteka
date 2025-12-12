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
        $this->reservationRepository->method('findAll')->willReturn([]);

        $query = new ListReservationsQuery();
        $result = ($this->handler)($query);

        $this->assertIsArray($result);
    }
}
