<?php
namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\FineRepository;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use App\Service\Maintenance\PatronAnonymizer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class PatronAnonymizerTest extends TestCase
{
    public function testDryRunAnonymizesEligibleUser(): void
    {
        $user = new User();
        $user->setEmail('user@example.com')->setName('User');
        $this->setEntityId($user, 42);

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([$user]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('andWhere')->willReturn($qb);
        $qb->method('orderBy')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $users = $this->createMock(UserRepository::class);
        $users->method('createQueryBuilder')->willReturn($qb);

        $loans = $this->createMock(LoanRepository::class);
        $loans->method('countActiveByUser')->willReturn(0);
        $reservations = $this->createMock(ReservationRepository::class);
        $reservations->method('countActiveByUser')->willReturn(0);
        $fines = $this->createMock(FineRepository::class);
        $fines->method('sumOutstandingByUser')->willReturn(0.0);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $service = new PatronAnonymizer($em, $users, $loans, $reservations, $fines);
        $result = $service->anonymize(new \DateTimeImmutable('-1 year'), 10, true);

        $this->assertSame(1, $result['anonymized']);
        $this->assertSame([42], $result['userIds']);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $ref = new \ReflectionProperty($entity, 'id');
        $ref->setAccessible(true);
        $ref->setValue($entity, $id);
    }
}
