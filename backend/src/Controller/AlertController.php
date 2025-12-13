<?php
namespace App\Controller;

use App\Repository\LoanRepository;
use App\Repository\ReservationRepository;
use App\Repository\FineRepository;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AlertController extends AbstractController
{
    public function __construct(
        private readonly SecurityService $security,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function getAlerts(Request $request): JsonResponse
    {
        $userId = $this->security->getCurrentUserId($request);
        if ($userId === null) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $loanRepo = $this->entityManager->getRepository(\App\Entity\Loan::class);
        $reservationRepo = $this->entityManager->getRepository(\App\Entity\Reservation::class);
        $fineRepo = $this->entityManager->getRepository(\App\Entity\Fine::class);

        $alerts = [];
        $now = new \DateTime();
        $tomorrow = (new \DateTime())->modify('+1 day');

        // Check loans due soon (within 24 hours)
        $loans = $loanRepo->findBy(['user' => $userId, 'returnedAt' => null]);
        foreach ($loans as $loan) {
            $dueDate = $loan->getDueAt();
            if ($dueDate && $dueDate <= $tomorrow && $dueDate >= $now) {
                $alerts[] = [
                    'type' => 'due_soon',
                    'title' => 'Zbliża się termin zwrotu!',
                    'message' => sprintf('Książka "%s" ma termin zwrotu %s', 
                        $loan->getBookCopy()?->getBook()?->getTitle() ?? 'Nieznana książka',
                        $dueDate->format('d.m.Y')
                    ),
                    'action' => [
                        'label' => 'Przedłuż',
                        'link' => '/my-loans'
                    ]
                ];
            }

            // Check overdue loans
            if ($dueDate && $dueDate < $now) {
                $alerts[] = [
                    'type' => 'overdue',
                    'title' => 'Książka przetrzymana!',
                    'message' => sprintf('Książka "%s" miała być zwrócona %s', 
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

        // Check ready reservations
        $reservations = $reservationRepo->createQueryBuilder('r')
            ->where('r.user = :userId')
            ->andWhere('r.status = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', 'ACTIVE')
            ->getQuery()
            ->getResult();

        foreach ($reservations as $reservation) {
            if ($reservation->getAssignedCopy() !== null) {
                $expiresAt = $reservation->getExpiresAt();
                $alerts[] = [
                    'type' => 'ready',
                    'title' => 'Rezerwacja gotowa do odbioru!',
                    'message' => sprintf('Książka "%s" czeka na Ciebie do %s', 
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

        // Check unpaid fines
        $fines = $fineRepo->createQueryBuilder('f')
            ->join('f.loan', 'l')
            ->where('l.user = :userId')
            ->andWhere('f.paidAt IS NULL')
            ->setParameter('userId', $userId)
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

        return $this->json($alerts);
    }

    public function getLibraryHours(Request $request): JsonResponse
    {
        // Library hours are public information - no authentication required
        
        // W prawdziwej aplikacji te dane byłyby w bazie danych lub konfiguracji
        $hours = [
            'Poniedziałek' => '9:00 - 19:00',
            'Wtorek' => '9:00 - 19:00',
            'Środa' => '9:00 - 19:00',
            'Czwartek' => '9:00 - 19:00',
            'Piątek' => '9:00 - 17:00',
            'Sobota' => '10:00 - 15:00',
            'Niedziela' => 'Nieczynne'
        ];

        return $this->json($hours);
    }
}
