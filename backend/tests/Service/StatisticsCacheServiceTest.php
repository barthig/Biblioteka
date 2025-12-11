<?php

namespace App\Tests\Service;

use App\Service\StatisticsCacheService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class StatisticsCacheServiceTest extends TestCase
{
    private ArrayAdapter $cache;
    private StatisticsCacheService $service;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->service = new StatisticsCacheService($this->cache);
    }

    public function testGetDashboardStats(): void
    {
        $stats = [
            'totalBooks' => 1000,
            'activeLoans' => 50,
            'overdueLoans' => 5,
        ];

        $result = $this->service->getDashboardStats(fn() => $stats);

        $this->assertEquals($stats, $result);
        
        // Second call should use cache
        $result2 = $this->service->getDashboardStats(fn() => ['totalBooks' => 9999]);
        $this->assertEquals($stats, $result2);
    }

    public function testGetLoansStats(): void
    {
        $period = '2024-12';
        $loansStats = [
            'total' => 150,
            'returned' => 120,
            'active' => 30,
        ];

        $result = $this->service->getLoansStats($period, fn() => $loansStats);

        $this->assertEquals($loansStats, $result);
    }

    public function testGetUserStats(): void
    {
        $userId = 1;
        $userStats = [
            'totalLoans' => 25,
            'activeLoans' => 2,
            'overdueLoans' => 0,
        ];

        $result = $this->service->getUserStats($userId, fn() => $userStats);

        $this->assertEquals($userStats, $result);
    }

    public function testInvalidateAll(): void
    {
        $stats = ['totalBooks' => 1000];
        $this->service->getDashboardStats(fn() => $stats);
        
        $this->service->invalidateAll();
        
        // After invalidation, should fetch fresh data
        $newStats = ['totalBooks' => 2000];
        $result = $this->service->getDashboardStats(fn() => $newStats);
        $this->assertEquals($newStats, $result);
    }

    public function testInvalidateDashboard(): void
    {
        $stats = ['totalBooks' => 1000];
        $this->service->getDashboardStats(fn() => $stats);
        
        $this->service->invalidateDashboard();
        
        $newStats = ['totalBooks' => 1500];
        $result = $this->service->getDashboardStats(fn() => $newStats);
        $this->assertEquals($newStats, $result);
    }

    public function testInvalidateUserStats(): void
    {
        $userId = 1;
        $stats = ['totalLoans' => 10];
        
        $this->service->getUserStats($userId, fn() => $stats);
        $this->service->invalidateUserStats($userId);
        
        $newStats = ['totalLoans' => 20];
        $result = $this->service->getUserStats($userId, fn() => $newStats);
        $this->assertEquals($newStats, $result);
    }

    public function testGetMonthlyReport(): void
    {
        $month = '2024-12';
        $report = [
            'loans' => 150,
            'newUsers' => 20,
            'newBooks' => 50,
        ];

        $result = $this->service->getMonthlyReport($month, fn() => $report);

        $this->assertEquals($report, $result);
    }

    public function testGetPopularBooksStats(): void
    {
        $period = 'monthly';
        $stats = [
            ['id' => 1, 'title' => 'Book 1', 'count' => 50],
            ['id' => 2, 'title' => 'Book 2', 'count' => 45],
        ];

        $result = $this->service->getPopularBooksStats($period, fn() => $stats);

        $this->assertEquals($stats, $result);
    }
}
