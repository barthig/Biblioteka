<?php
namespace App\Tests\Service;

use App\Repository\BookRepository;
use App\Service\Maintenance\WeedingAnalyticsService;
use PHPUnit\Framework\TestCase;

class WeedingAnalyticsServiceTest extends TestCase
{
    public function testSummarizeCalculatesTurnoverAndMonths(): void
    {
        $bookRepo = $this->createMock(BookRepository::class);

        $lastLoanAt = new \DateTimeImmutable('-40 days');
        $bookRepo->method('findWeedingCandidates')->willReturn([
            [
                'bookId' => 1,
                'title' => 'Old Book',
                'totalCopies' => 2,
                'availableCopies' => 1,
                'totalLoans' => 3,
                'activeReservations' => 0,
                'lastLoanAt' => $lastLoanAt,
            ]
        ]);

        $service = new WeedingAnalyticsService($bookRepo);
        $result = $service->summarize(new \DateTimeImmutable('-1 year'), 0, 10);

        $this->assertSame(1.5, $result[0]['turnover']);
        $this->assertNotNull($result[0]['monthsSinceLastLoan']);
    }
}
