<?php
namespace App\Service;

/**
 * Minimal placeholder kept for backward compatibility after removing reader orders.
 * Methods now no-op to prevent runtime errors if legacy wiring still calls them.
 */
final class OrderLifecycleService
{
    public function releaseCopy(object $order, ?object $em = null, bool $flush = false): bool
    {
        return false;
    }

    public function expireOrders(iterable $orders): int
    {
        return 0;
    }
}
