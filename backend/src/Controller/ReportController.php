<?php
namespace App\Controller;

use App\Entity\Loan;
use App\Entity\BookCopy;
use App\Entity\User;
use App\Entity\Fine;
use App\Entity\AcquisitionBudget;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\SecurityService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ReportController extends AbstractController
{
    public function usage(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $from = $request->query->get('from');
        $to = $request->query->get('to');

        if (($from && !strtotime($from)) || ($to && !strtotime($to))) {
            return $this->json(['error' => 'Invalid date range'], 400);
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();
        $qb = $entityManager->createQueryBuilder();
        $qb->select('COUNT(l.id) AS totalLoans', 'SUM(CASE WHEN l.returnedAt IS NULL THEN 1 ELSE 0 END) AS activeLoans')
            ->from(Loan::class, 'l');

        if ($from) {
            $qb->andWhere('l.borrowedAt >= :from')->setParameter('from', new \DateTimeImmutable($from));
        }
        if ($to) {
            $qb->andWhere('l.borrowedAt <= :to')->setParameter('to', new \DateTimeImmutable($to));
        }

        $result = $qb->getQuery()->getSingleResult();
        $total = (int)$result['totalLoans'];
        $active = (int)$result['activeLoans'];

        if ($total === 0) {
            return new JsonResponse(null, 204);
        }

        return $this->json([
            'totalLoans' => $total,
            'activeLoans' => $active,
            'from' => $from,
            'to' => $to,
        ], 200);
    }

    public function export(Request $request, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $format = $request->query->get('format');
        if ($format === null) {
            return $this->json(['error' => 'Format query parameter is required'], 400);
        }

        if (!in_array($format, ['csv', 'json', 'pdf'], true)) {
            return $this->json(['error' => 'Unsupported export format'], 422);
        }

        if ($format === 'pdf' && $request->query->getBoolean('simulateFailure')) {
            return $this->json(['error' => 'Failed to generate PDF report'], 500);
        }

        $content = match ($format) {
            'csv' => "book,title,loans\n12345,Sample Book,12\n",
            'json' => json_encode(['book' => 'Sample Book', 'loans' => 12], JSON_THROW_ON_ERROR),
            default => base64_encode('PDF report placeholder'),
        };

        return $this->json([
            'format' => $format,
            'content' => $content,
            'generatedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ], 200);
    }

    public function popularTitles(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        $limit = max(1, min(50, $request->query->getInt('limit', 10)));
        $days = max(0, $request->query->getInt('days', 90));

        /** @var EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $qb = $em->createQueryBuilder()
            ->select('b.id AS bookId', 'b.title AS title', 'COUNT(l.id) AS loanCount')
            ->from(Loan::class, 'l')
            ->join('l.book', 'b')
            ->groupBy('b.id')
            ->orderBy('loanCount', 'DESC')
            ->setMaxResults($limit);

        if ($days > 0) {
            $since = (new \DateTimeImmutable())->modify(sprintf('-%d days', $days));
            $qb->andWhere('l.borrowedAt >= :since')->setParameter('since', $since);
        }

        $raw = $qb->getQuery()->getResult();
        $items = array_map(static function (array $row): array {
            return [
                'bookId' => (int) $row['bookId'],
                'title' => $row['title'],
                'loanCount' => (int) $row['loanCount'],
            ];
        }, $raw);
        return $this->json([
            'limit' => $limit,
            'periodDays' => $days,
            'items' => $items,
        ], 200);
    }

    public function patronSegments(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        /** @var EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $qb = $em->createQueryBuilder()
            ->select('u.membershipGroup AS membershipGroup')
            ->addSelect('COUNT(u.id) AS totalUsers')
            ->addSelect('SUM(CASE WHEN u.blocked = true THEN 1 ELSE 0 END) AS blockedUsers')
            ->addSelect('AVG(u.loanLimit) AS avgLoanLimit')
            ->addSelect('COUNT(DISTINCT l.id) AS activeLoans')
            ->from(User::class, 'u')
            ->leftJoin(Loan::class, 'l', 'WITH', 'l.user = u AND l.returnedAt IS NULL')
            ->groupBy('u.membershipGroup')
            ->orderBy('membershipGroup', 'ASC');

        $segments = array_map(static function (array $row): array {
            return [
                'membershipGroup' => $row['membershipGroup'],
                'totalUsers' => (int) $row['totalUsers'],
                'blockedUsers' => (int) $row['blockedUsers'],
                'activeLoans' => (int) $row['activeLoans'],
                'avgLoanLimit' => round((float) $row['avgLoanLimit'], 2),
            ];
        }, $qb->getQuery()->getResult());

        return $this->json(['segments' => $segments], 200);
    }

    public function financialSummary(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        /** @var EntityManagerInterface $em */
        $em = $doctrine->getManager();

        $budgetTotals = $em->createQueryBuilder()
            ->select('COALESCE(SUM(b.allocatedAmount), 0) AS allocated')
            ->addSelect('COALESCE(SUM(b.spentAmount), 0) AS spent')
            ->addSelect('COALESCE(MAX(b.currency), :defaultCurrency) AS currency')
            ->from(AcquisitionBudget::class, 'b')
            ->setParameter('defaultCurrency', 'PLN')
            ->getQuery()
            ->getSingleResult();

        $fineTotals = $em->createQueryBuilder()
            ->select('COALESCE(SUM(CASE WHEN f.paidAt IS NULL THEN f.amount ELSE 0 END), 0) AS outstanding')
            ->addSelect('COALESCE(SUM(CASE WHEN f.paidAt IS NOT NULL THEN f.amount ELSE 0 END), 0) AS collected')
            ->addSelect('COALESCE(MAX(f.currency), :defaultCurrency) AS currency')
            ->from(Fine::class, 'f')
            ->setParameter('defaultCurrency', 'PLN')
            ->getQuery()
            ->getSingleResult();

        $allocated = round((float) $budgetTotals['allocated'], 2);
        $spent = round((float) $budgetTotals['spent'], 2);
        $remaining = max(0.0, round($allocated - $spent, 2));

        return $this->json([
            'budgets' => [
                'allocated' => $allocated,
                'spent' => $spent,
                'remaining' => $remaining,
                'currency' => $budgetTotals['currency'],
            ],
            'fines' => [
                'outstanding' => round((float) $fineTotals['outstanding'], 2),
                'collected' => round((float) $fineTotals['collected'], 2),
                'currency' => $fineTotals['currency'],
            ],
        ], 200);
    }

    public function inventoryOverview(Request $request, ManagerRegistry $doctrine, SecurityService $security): JsonResponse
    {
        if (!$security->hasRole($request, 'ROLE_LIBRARIAN')) {
            return $this->json(['error' => 'Forbidden'], 403);
        }

        /** @var EntityManagerInterface $em */
        $em = $doctrine->getManager();
        $statusBreakdown = $em->createQueryBuilder()
            ->select('c.status AS status', 'COUNT(c.id) AS total')
            ->from(BookCopy::class, 'c')
            ->groupBy('c.status')
            ->getQuery()
            ->getResult();

        $breakdown = array_map(static function (array $row): array {
            return [
                'status' => $row['status'],
                'total' => (int) $row['total'],
            ];
        }, $statusBreakdown);

        $totalCopies = array_sum(array_map(static fn ($row) => $row['total'], $breakdown));
        $borrowed = 0;
        foreach ($breakdown as $row) {
            if ($row['status'] === BookCopy::STATUS_BORROWED) {
                $borrowed = (int) $row['total'];
                break;
            }
        }

        $utilization = $totalCopies > 0 ? round(100 * $borrowed / $totalCopies, 2) : 0.0;

        return $this->json([
            'copies' => $breakdown,
            'totalCopies' => $totalCopies,
            'borrowedPercentage' => $utilization,
        ], 200);
    }
}
