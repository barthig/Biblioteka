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
            ->select('COALESCE(SUM(b.allocatedAmount), 0) AS allocated')
            ->addSelect('COALESCE(SUM(b.spentAmount), 0) AS spent')
            ->addSelect('COALESCE(MAX(b.currency), :defaultCurrency) AS currency')
            ->from(AcquisitionBudget::class, 'b')
            ->setParameter('defaultCurrency', 'PLN')
            ->getQuery()
            ->getSingleResult();

        $fineTotals = $this->entityManager->createQueryBuilder()
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

        return [
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
        ];
    }
}
