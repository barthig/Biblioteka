<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Alert\UserAlertsQuery;
use App\Entity\BookCopy;
use App\Entity\Reservation;
use App\Repository\FineRepository;
use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class UserAlertsHandler
{
    public function __construct(
        private LoanRepository $loanRepository,
        private ReservationRepository $reservationRepository,
        private FineRepository $fineRepository
    ) {
    }

    public function __invoke(UserAlertsQuery $query): array
    {
        $alerts = [];
        $now = new \DateTimeImmutable();
        $tomorrow = $now->modify('+1 day');

        $loans = $this->loanRepository->findBy(['user' => $query->userId, 'returnedAt' => null]);
        foreach ($loans as $loan) {
            $dueDate = $loan->getDueAt();
            if ($dueDate && $dueDate <= $tomorrow && $dueDate >= $now) {
                $alerts[] = [
                    'type' => 'due_soon',
                    'title' => 'Zbliża się termin zwrotu!',
                    'message' => sprintf(
                        'Książka "%s" ma termin zwrotu %s',
                        $loan->getBookCopy()?->getBook()?->getTitle() ?? 'Nieznana książka',
                        $dueDate->format('d.m.Y')
                    ),
                    'action' => [
                        'label' => 'Przedłuż',
                        'link' => '/my-loans'
                    ]
                ];
            }

            if ($dueDate && $dueDate < $now) {
                $alerts[] = [
                    'type' => 'overdue',
                    'title' => 'Książka przetrzymana!',
                    'message' => sprintf(
                        'Książka "%s" miała być zwrócona %s',
                        $loan->getBookCopy()?->getBook()?->getTitle() ?? 'Nieznana książka',
                        $dueDate->format('d.m.Y')
                    ),
                    'action' => [
                        'label' => 'Sprawdź',
                        'link' => '/my-loans'
                    ]
                ];
            }
        }

        $reservations = $this->reservationRepository->createQueryBuilder('r')
            ->where('r.user = :userId')
            ->andWhere('r.status = :status')
            ->setParameter('userId', $query->userId)
            ->setParameter('status', Reservation::STATUS_ACTIVE)
            ->getQuery()
            ->getResult();

        foreach ($reservations as $reservation) {
            if ($reservation->getAssignedCopy() !== null) {
                $expiresAt = $reservation->getExpiresAt();
                $alerts[] = [
                    'type' => 'ready',
                    'title' => 'Rezerwacja gotowa do odbioru!',
                    'message' => sprintf(
                        'Książka "%s" czeka na Ciebie do %s',
                        $reservation->getBook()?->getTitle() ?? 'Nieznana książka',
                        $expiresAt ? $expiresAt->format('d.m.Y') : 'odwołania'
                    ),
                    'action' => [
                        'label' => 'Zobacz',
                        'link' => '/reservations'
                    ]
                ];
            }
        }

        $fines = $this->fineRepository->createQueryBuilder('f')
            ->join('f.loan', 'l')
            ->where('l.user = :userId')
            ->andWhere('f.paidAt IS NULL')
            ->setParameter('userId', $query->userId)
            ->getQuery()
            ->getResult();

        if (count($fines) > 0) {
            $totalAmount = array_reduce($fines, fn($sum, $fine) => $sum + $fine->getAmount(), 0);
            $alerts[] = [
                'type' => 'fine',
                'title' => 'Masz nieuregulowane kary',
                'message' => sprintf('Łączna kwota: %.2f zł za %d %s',
                    $totalAmount,
                    count($fines),
                    count($fines) === 1 ? 'karę' : (count($fines) < 5 ? 'kary' : 'kar')
                ),
                'action' => [
                    'label' => 'Ureguluj',
                    'link' => '/my-loans'
                ]
            ];
        }

        return $alerts;
    }
}
