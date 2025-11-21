<?php
namespace App\Service\Maintenance;

use App\Repository\BookRepository;

class WeedingAnalyticsService
{
    public function __construct(private BookRepository $books)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function summarize(
        \DateTimeImmutable $cutoff,
        int $minLoans,
        int $limit
    ): array {
        $rows = $this->books->findWeedingCandidates($cutoff, $minLoans, $limit);

        return array_map(static function (array $row): array {
            $lastLoanAt = $row['lastLoanAt'];
            $monthsSince = null;
            if ($lastLoanAt instanceof \DateTimeInterface) {
                $now = new \DateTimeImmutable();
                $diff = $now->diff($lastLoanAt);
                $monthsSince = $diff->y * 12 + $diff->m + ($diff->d >= 15 ? 1 : 0);
            }

            $turnover = $row['totalCopies'] > 0
                ? round($row['totalLoans'] / max(1, $row['totalCopies']), 2)
                : 0.0;

            return [
                'bookId' => $row['bookId'],
                'title' => $row['title'],
                'totalCopies' => $row['totalCopies'],
                'availableCopies' => $row['availableCopies'],
                'totalLoans' => $row['totalLoans'],
                'activeReservations' => $row['activeReservations'],
                'lastLoanAt' => $lastLoanAt instanceof \DateTimeInterface ? $lastLoanAt->format(DATE_ATOM) : null,
                'monthsSinceLastLoan' => $monthsSince,
                'turnover' => $turnover,
            ];
        }, $rows);
    }
}
