<?php
namespace App\Application\Handler\Query;

use App\Application\Query\Report\GetFinancialSummaryQuery;
use App\Entity\AcquisitionBudget;
use App\Entity\Fine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetFinancialSummaryHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(GetFinancialSummaryQuery $query): array
    {
        $budgetTotals = $this->entityManager->createQueryBuilder()
            ->select('SUM(b.allocatedAmount) AS allocated')
            ->addSelect('SUM(b.spentAmount) AS spent')
            ->addSelect('MAX(b.currency) AS currency')
            ->from(AcquisitionBudget::class, 'b')
            ->setParameter('defaultCurrency', 'PLN')
            ->getQuery()
            ->getSingleResult();

        $fineTotals = $this->entityManager->createQueryBuilder()
            ->select('SUM(CASE WHEN f.paidAt IS NULL THEN f.amount ELSE 0 END) AS outstanding')
            ->addSelect('SUM(CASE WHEN f.paidAt IS NOT NULL THEN f.amount ELSE 0 END) AS collected')
            ->addSelect('MAX(f.currency) AS currency')
            ->from(Fine::class, 'f')
            ->setParameter('defaultCurrency', 'PLN')
            ->getQuery()
            ->getSingleResult();

        $allocated = round((float) ($budgetTotals['allocated'] ?? 0), 2);
        $spent = round((float) ($budgetTotals['spent'] ?? 0), 2);
        $remaining = max(0.0, round($allocated - $spent, 2));

        return [
            'budgets' => [
                'allocated' => $allocated,
                'spent' => $spent,
                'remaining' => $remaining,
                'currency' => $budgetTotals['currency'] ?? 'PLN',
            ],
            'fines' => [
                'outstanding' => round((float) ($fineTotals['outstanding'] ?? 0), 2),
                'collected' => round((float) ($fineTotals['collected'] ?? 0), 2),
                'currency' => $fineTotals['currency'] ?? 'PLN',
            ],
        ];
    }
}
