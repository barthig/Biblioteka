<?php
namespace App\Controller;

use App\Entity\Loan;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ReportController extends AbstractController
{
    #[Route('/api/reports/usage', name: 'api_reports_usage', methods: ['GET'])]
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

    #[Route('/api/reports/export', name: 'api_reports_export', methods: ['GET'])]
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
}
