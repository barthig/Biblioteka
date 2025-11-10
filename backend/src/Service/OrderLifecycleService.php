<?php
namespace App\Service;

use App\Entity\BookCopy;
use App\Entity\OrderRequest;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class OrderLifecycleService
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Release the reserved copy linked to an order, if any.
     *
     * @param OrderRequest $order The order whose copy should be released.
    * @param EntityManagerInterface|null $em Optional EntityManager to reuse.
    * @param bool $flush Whether to flush the EntityManager after the change.
     *
     * @return bool True when a copy was released, false otherwise.
     */
    public function releaseCopy(OrderRequest $order, ?EntityManagerInterface $em = null, bool $flush = false): bool
    {
        $copy = $order->getBookCopy();
        if ($copy === null) {
            return false;
        }

        $em ??= $this->doctrine->getManagerForClass(OrderRequest::class);

        if ($copy->getStatus() === BookCopy::STATUS_RESERVED) {
            $copy->setStatus(BookCopy::STATUS_AVAILABLE);
            $order->getBook()->recalculateInventoryCounters();
        }

        $order->setBookCopy(null);
        $em->persist($copy);
        $em->persist($order->getBook());
        $em->persist($order);

        if ($flush) {
            $em->flush();
        }

        return true;
    }

    /**
     * Expire every overdue order in the provided collection.
     *
     * @param iterable<OrderRequest> $orders
     *
     * @return int Number of orders marked as expired.
     */
    public function expireOrders(iterable $orders): int
    {
        $em = $this->doctrine->getManagerForClass(OrderRequest::class);
        $now = new \DateTimeImmutable();
        $expiredCount = 0;

        foreach ($orders as $order) {
            if (!$order instanceof OrderRequest) {
                continue;
            }

            if ($order->getStatus() !== OrderRequest::STATUS_READY) {
                continue;
            }

            $deadline = $order->getPickupDeadline();
            if ($deadline === null || $deadline >= $now) {
                continue;
            }

            $this->releaseCopy($order, $em, false);
            $order->expire();
            $em->persist($order);
            ++$expiredCount;
        }

        if ($expiredCount > 0) {
            $em->flush();
        }

        return $expiredCount;
    }
}
