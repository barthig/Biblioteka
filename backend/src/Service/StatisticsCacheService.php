<?php

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service for managing statistics and reports caching
 */
class StatisticsCacheService
{
    private const CACHE_KEY_DASHBOARD = 'dashboard_stats';
    private const CACHE_KEY_LOANS_STATS = 'loans_statistics_%s';
    private const CACHE_KEY_POPULAR_BOOKS_STATS = 'popular_books_stats_%s';
    private const CACHE_KEY_USER_STATS = 'user_stats_%d';
    private const CACHE_KEY_MONTHLY_REPORT = 'monthly_report_%s';

    public function __construct(
        private CacheInterface $statisticsCache
    ) {
    }

    /**
     * Get cached dashboard statistics
     */
    public function getDashboardStats(callable $callback): mixed
    {
        return $this->statisticsCache->get(
            self::CACHE_KEY_DASHBOARD,
            function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(300); // 5 minutes
                return $callback();
            }
        );
    }

    /**
     * Get cached loans statistics
     */
    public function getLoansStats(string $period, callable $callback): mixed
    {
        return $this->statisticsCache->get(
            sprintf(self::CACHE_KEY_LOANS_STATS, $period),
            function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(600); // 10 minutes
                return $callback();
            }
        );
    }

    /**
     * Get cached popular books statistics
     */
    public function getPopularBooksStats(string $period, callable $callback): mixed
    {
        return $this->statisticsCache->get(
            sprintf(self::CACHE_KEY_POPULAR_BOOKS_STATS, $period),
            function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(1800); // 30 minutes
                return $callback();
            }
        );
    }

    /**
     * Get cached user statistics
     */
    public function getUserStats(int $userId, callable $callback): mixed
    {
        return $this->statisticsCache->get(
            sprintf(self::CACHE_KEY_USER_STATS, $userId),
            function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(600); // 10 minutes
                return $callback();
            }
        );
    }

    /**
     * Get cached monthly report
     */
    public function getMonthlyReport(string $month, callable $callback): mixed
    {
        return $this->statisticsCache->get(
            sprintf(self::CACHE_KEY_MONTHLY_REPORT, $month),
            function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(3600); // 1 hour
                return $callback();
            }
        );
    }

    /**
     * Invalidate all statistics cache
     */
    public function invalidateAll(): void
    {
        // Delete common statistics keys
        $this->statisticsCache->delete(self::CACHE_KEY_DASHBOARD);
    }

    /**\n     * Invalidate dashboard cache
     */
    public function invalidateDashboard(): void
    {
        $this->statisticsCache->delete(self::CACHE_KEY_DASHBOARD);
    }

    /**
     * Invalidate user statistics
     */
    public function invalidateUserStats(int $userId): void
    {
        $this->statisticsCache->delete(sprintf(self::CACHE_KEY_USER_STATS, $userId));
    }
}
