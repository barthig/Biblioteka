<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Reservation\ListReservationsQuery;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ListReservationsHandler
{
    public function __construct(
        private ReservationRepository $reservationRepository
    ) {
    }

    public function __invoke(ListReservationsQuery $query): array
    {
        $offset = ($query->page - 1) * $query->limit;

        $qb = $this->reservationRepository->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')->addSelect('u')
            ->leftJoin('r.book', 'b')->addSelect('b')
            ->leftJoin('r.bookCopy', 'bc')->addSelect('bc')
            ->orderBy('r.reservedAt', 'DESC');

        // Filter by user
        if (!$query->isLibrarian && $query->userId) {
            $qb->where('r.user = :userId')
                ->setParameter('userId', $query->userId);
            
            if (!$query->includeHistory) {
                $qb->andWhere('r.status = :status')
                    ->setParameter('status', Reservation::STATUS_ACTIVE);
            }
        }

        // Librarian filters
        if ($query->isLibrarian) {
            if ($query->status !== null && in_array(strtoupper($query->status), [
                Reservation::STATUS_ACTIVE,
                Reservation::STATUS_CANCELLED,
                Reservation::STATUS_FULFILLED,
                Reservation::STATUS_EXPIRED,
            ], true)) {
                $qb->andWhere('r.status = :status')
                    ->setParameter('status', strtoupper($query->status));
            }

            if ($query->filterUserId) {
                $qb->andWhere('r.user = :filterUserId')
                    ->setParameter('filterUserId', $query->filterUserId);
            }
        }

        // Count total (without orderBy for COUNT query)
        $countQb = $this->reservationRepository->createQueryBuilder('r');
        
        // Apply same filters as main query
        if (!$query->isLibrarian && $query->userId) {
            $countQb->where('r.user = :userId')
                ->setParameter('userId', $query->userId);
            
            if (!$query->includeHistory) {
                $countQb->andWhere('r.status = :status')
                    ->setParameter('status', Reservation::STATUS_ACTIVE);
            }
        }

        if ($query->isLibrarian) {
            if ($query->status !== null && in_array(strtoupper($query->status), [
                Reservation::STATUS_ACTIVE,
                Reservation::STATUS_CANCELLED,
                Reservation::STATUS_FULFILLED,
                Reservation::STATUS_EXPIRED,
            ], true)) {
                $countQb->andWhere('r.status = :status')
                    ->setParameter('status', strtoupper($query->status));
            }

            if ($query->filterUserId) {
                $countQb->andWhere('r.user = :filterUserId')
                    ->setParameter('filterUserId', $query->filterUserId);
            }
        }
        
        $countQb->select('COUNT(r.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        // Get paginated results
        $reservations = $qb->setMaxResults($query->limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return [
            'data' => $reservations,
            'meta' => [
                'page' => $query->page,
                'limit' => $query->limit,
                'total' => $total,
                'totalPages' => $total > 0 ? (int)ceil($total / $query->limit) : 0
            ]
        ];
    }
}
